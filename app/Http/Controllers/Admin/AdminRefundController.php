<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundBatch;
use App\Models\EODebt;
use App\Models\Event;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\RefundXenditExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\TicketWalletService;
use App\Services\MerchWalletService;
use App\Services\XenditPayoutService;
use App\Services\RefundSettlementService;
use Illuminate\Support\Facades\Log;

class AdminRefundController extends Controller
{
    public function __construct()
    {
        // Proteksi middleware agar hanya Admin Utama (role: admin) yang bisa masuk
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || auth()->user()->role !== 'admin') {
                abort(403, 'Aksi ini hanya diizinkan untuk Admin Utama.');
            }
            return $next($request);
        });
    }

    /**
     * 📊 1. Halaman Utama Dashboard Refund Admin
     */
    public function index(Request $request)
    {
        $filterEventId = $request->input('filter_event_id');
        $activeTab = $request->input('tab', 'ticket');

        // Master List Event untuk Pilihan Dropdown Filter (Hanya event yang memiliki batch sesuai tab aktif)
        $allEventsWithBatches = Event::whereHas('refundBatches', function ($q) use ($activeTab) {
                $q->where('type', $activeTab);
            })
            ->orderBy('title', 'asc')
            ->get();

        // Ambil daftar batch yang sesuai dengan tipe tab aktif
        $batches = RefundBatch::with(['event', 'eo'])
            ->withCount('refunds as total_pengajuan')
            ->where('type', $activeTab)
            ->when($filterEventId, function ($query) use ($filterEventId) {
                return $query->where('event_id', $filterEventId);
            })
            ->latest()
            ->get();

        // Kondisional data untuk pembukaan batch baru & log berita berdasarkan komoditas tab
        if ($activeTab === 'ticket') {
            $eligibleEvents = Event::where(function ($query) {
                    $query->where('status', 'cancelled')
                          ->orWhere(function ($q) {
                              $q->where('status', 'approved')->where('is_rescheduled', '>', 0);
                          });
                })
                ->whereDoesntHave('refundBatches', function ($query) {
                    $query->where('type', 'ticket')->whereIn('status', ['open', 'closed']);
                })
                ->with(['eo', 'eventWallet'])
                ->get();

            $eventNewsLogs = Event::where(function ($query) {
                    $query->where('status', 'cancelled')
                          ->orWhere(function ($q) {
                              $q->where('status', 'approved')->where('is_rescheduled', '>', 0);
                          });
                })
                ->with('eo')
                ->latest('updated_at')
                ->take(5)
                ->get();
        } else {
            $eligibleEvents = Event::where('status', 'cancelled')
                ->where('merch_cancel_decision', 'refund')
                ->whereDoesntHave('refundBatches', function ($query) {
                    $query->where('type', 'merch')->whereIn('status', ['open', 'closed']);
                })
                ->with(['eo', 'merchWallet'])
                ->get();

            $eventNewsLogs = Event::where('status', 'cancelled')
                ->where('merch_cancel_decision', 'refund')
                ->with('eo')
                ->latest('updated_at')
                ->take(5)
                ->get();
        }

        // 📥 LOG PEMBERITAHUAN: pengajuan refund dari pembeli yang masih 'waiting'
        // (belum terserap ke batch mana pun). Ini penanda agar admin membuka batch untuk
        // event terkait sehingga pengajuan tersebut masuk antrean pemrosesan.
        if ($activeTab === 'ticket') {
            $waitingRefundLogs = DB::table('refunds')
                ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
                ->join('events', 'transactions.event_id', '=', 'events.id')
                ->leftJoin('eo', 'events.eo_id', '=', 'eo.id')
                ->where('refunds.status', 'waiting')
                ->select(
                    'refunds.id',
                    'refunds.grand_total_refunded',
                    'refunds.bank_name',
                    'refunds.created_at',
                    'events.title as event_title',
                    'eo.nama_badan_usaha as eo_name',
                    'transactions.email as buyer_email'
                )
                ->orderByDesc('refunds.created_at')
                ->get();
        } else {
            $waitingRefundLogs = DB::table('refunds')
                ->join('transaction_merch', 'refunds.transaction_merch_id', '=', 'transaction_merch.id')
                ->join('events', 'transaction_merch.event_id', '=', 'events.id')
                ->leftJoin('eo', 'events.eo_id', '=', 'eo.id')
                ->where('refunds.status', 'waiting')
                ->select(
                    'refunds.id',
                    'refunds.grand_total_refunded',
                    'refunds.bank_name',
                    'refunds.created_at',
                    'events.title as event_title',
                    'eo.nama_badan_usaha as eo_name',
                    'transaction_merch.email as buyer_email'
                )
                ->orderByDesc('refunds.created_at')
                ->get();
        }

        return view('admin.refunds.index', compact(
            'batches',
            'eligibleEvents',
            'allEventsWithBatches',
            'eventNewsLogs',
            'waitingRefundLogs',
            'activeTab'
        ));
    }

    /**
     * 🔨 2. Membuat Berkas Antrean Batch Refund Baru (Tiket / Merch)
     */
    public function storeBatch(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'type'     => 'required|in:ticket,merch'
        ]);

        $event = Event::findOrFail($request->event_id);

        // Proteksi Kunci Ketat Khusus Per Tipe Komoditas
        $exists = RefundBatch::where('event_id', $event->id)
            ->where('type', $request->type)
            ->whereIn('status', ['open', 'closed'])
            ->exists();
            
        if ($exists) {
            return redirect()->back()->with('error', 'Gagal! Batch refund aktif untuk komoditas ini sudah ada.');
        }

        $batchCount = RefundBatch::where('event_id', $event->id)
            ->where('type', $request->type)
            ->count() + 1;
            
        $labelType = $request->type === 'ticket' ? 'Tiket' : 'Merchandise';

        DB::beginTransaction();
        try {
            $batch = RefundBatch::create([
                'eo_id'      => $event->eo_id,
                'event_id'   => $event->id,
                'type'       => $request->type,
                'name'       => "Refund " . $labelType . " " . $event->title . " - Batch " . $batchCount,
                'start_date' => now()->toDateString(),
                'end_date'   => now()->addDays(14)->toDateString(),
                'status'     => 'open',
            ]);

            // Serap data ke dalam batch berdasarkan tipe komoditas secara terpisah & ketat
            if ($request->type === 'ticket') {
                Refund::whereIn('transaction_id', function ($query) use ($event) {
                        $query->select('id')->from('transactions')->where('event_id', $event->id);
                    })
                    ->whereNull('refund_batch_id')
                    ->where('status', 'waiting')
                    ->update([
                        'refund_batch_id' => $batch->id,
                        'status'          => 'pending',
                        'updated_at'      => now()
                    ]);
            } else {
                Refund::whereIn('transaction_merch_id', function ($query) use ($event) {
                        $query->select('id')->from('transaction_merch')->where('event_id', $event->id);
                    })
                    ->whereNull('refund_batch_id')
                    ->where(function ($q) {
                        $q->where('status', 'waiting')
                          ->orWhere('status', 'pending')
                          ->orWhereNull('status');
                    })
                    ->update([
                        'refund_batch_id' => $batch->id,
                        'status'          => 'pending',
                        'updated_at'      => now()
                    ]);
            }

            DB::commit();
            
            return redirect()->route('admin.refunds.index', ['tab' => $request->type])
                ->with('success', 'Batch Refund baru untuk ' . $labelType . ' berhasil diaktifkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membuka batch baru: ' . $e->getMessage());
        }
    }

    /**
     * 🔍 3. Halaman Detail Pengajuan Refund di dalam suatu Batch
     */
 public function show($id)
    {
        $batch = RefundBatch::with(['event', 'eo'])->findOrFail($id);
        
        $refunds = Refund::where('refund_batch_id', $batch->id)
            ->with(['transaction', 'transactionMerch'])
            ->latest()
            ->get();

        $totalDanaRefund = $refunds->sum('grand_total_refunded');
        $jumlahAntreanPending = $refunds->where('status', 'pending')->count();
        $estimasiBiayaXendit = $jumlahAntreanPending * 2500;

        // Ambil info saldo wallet tujuan berdasarkan tipe komoditas batch
        $walletTable = $batch->type === 'ticket' ? 'event_wallets' : 'merch_wallets';
        $wallet = DB::table($walletTable)->where('event_id', $batch->event_id)->first();
        $availableBalance = $wallet ? ($wallet->available_balance + $wallet->held_balance) : 0;

        // Hitung SERVICE TAX Global Event
        $transactionTable = $batch->type === 'ticket' ? 'transactions' : 'transaction_merch';
        $totalTaxSemuaTransaksi = DB::table($transactionTable)
            ->where('event_id', $batch->event_id)
            ->whereIn('payment_status', ['paid', 'refunded'])
            ->sum('service_tax');

        $taxSudahDirefundSelesai = Refund::join('refund_batches', 'refunds.refund_batch_id', '=', 'refund_batches.id')
            ->where('refund_batches.event_id', $batch->event_id)
            ->where('refund_batches.type', $batch->type)
            ->where('refund_batches.status', 'completed')
            ->where('refunds.status', 'refunded')
            ->sum('refunds.refunds_tax');

        $totalServiceTaxEvent = max(0, $totalTaxSemuaTransaksi - $taxSudahDirefundSelesai);

        // 🎯 PERUBAHAN HANYA DI SINI: Menambahkan 'wallet' ke dalam compact agar dilempar ke blade
        return view('admin.refunds.show', compact(
            'batch', 
            'refunds', 
            'wallet', // <-- INI YANG DITAMBAHKAN
            'totalDanaRefund', 
            'availableBalance', 
            'totalServiceTaxEvent', 
            'estimasiBiayaXendit'
        ));
    }
    
    /**
     * 🔄 4. Mengubah Status Batch (Open <=> Closed)
     */
    public function toggleStatus($id)
    {
        $batch = RefundBatch::findOrFail($id);

        if ($batch->status === 'completed') {
            return redirect()->back()->with('error', 'Batch yang sudah selesai tidak dapat diubah statusnya kembali.');
        }

        DB::beginTransaction();
        try {
            if ($batch->status === 'open') {
                $batch->update(['status' => 'closed']);
                $message = 'Batch berhasil dikunci! Pembeli baru otomatis berada di luar antrean batch ini.';
            } else {
                $batch->update(['status' => 'open']);

                // Tarik kembali data waiting sesuai jenis komoditas
                if ($batch->type === 'ticket') {
                    Refund::whereIn('transaction_id', function ($query) use ($batch) {
                            $query->select('id')->from('transactions')->where('event_id', $batch->event_id);
                        })
                        ->whereNull('refund_batch_id')
                        ->where('status', 'waiting')
                        ->update([
                            'refund_batch_id' => $batch->id,
                            'status'          => 'pending',
                            'updated_at'      => now()
                        ]);
                } else {
                    Refund::whereIn('transaction_merch_id', function ($query) use ($batch) {
                            $query->select('id')->from('transaction_merch')->where('event_id', $batch->event_id);
                        })
                        ->whereNull('refund_batch_id')
                        ->where(function ($q) {
                            $q->where('status', 'waiting')
                              ->orWhere('status', 'pending')
                              ->orWhereNull('status');
                        })
                        ->update([
                            'refund_batch_id' => $batch->id,
                            'status'          => 'pending',
                            'updated_at'      => now()
                        ]);
                }

                $message = 'Batch dibuka kembali! Data antrean waiting telah ditarik masuk.';
            }

            DB::commit();
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengubah status gerbang: ' . $e->getMessage());
        }
    }

    /**
     * 🏁 5. Aksi Tombol: Menyelesaikan Batch Refund
     */
    // public function completeBatch(Request $request, $id)
    // {
    //     $batch = RefundBatch::with('event')->findOrFail($id);

    //     if ($batch->status !== 'closed') {
    //         return redirect()->back()->with('error', 'Batch wajib dikunci/ditutup terlebih dahulu sebelum diselesaikan.');
    //     }

    //     $pendingRefunds = Refund::where('refund_batch_id', $batch->id)
    //         ->where('status', 'pending')
    //         ->with(['transaction', 'transactionMerch'])
    //         ->get();

    //     if ($pendingRefunds->isEmpty()) {
    //         $batch->update(['status' => 'completed']);
    //         return redirect()->route('admin.refunds.index', ['tab' => $batch->type])->with('success', 'Batch diselesaikan tanpa antrean transfer.');
    //     }

    //     // Kalkulasi total beban EO berdasarkan jenis komoditas masing-masing
    //     $totalBebanEO = 0;
    //     foreach ($pendingRefunds as $refund) {
    //         $relation = $batch->type === 'ticket' ? $refund->transaction : $refund->transactionMerch;
    //         if ($relation) {
    //             $totalBebanEO += $relation->total_amount;
    //         }
    //     }

    //     if ($totalBebanEO <= 0) {
    //         $batch->update(['status' => 'completed']);
    //         return redirect()->route('admin.refunds.index', ['tab' => $batch->type])->with('success', 'Batch ditutup tanpa pemotongan saldo.');
    //     }

    //     $biayaOperasionalXendit = $pendingRefunds->sum('refunds_tax');
    //     $walletTable = $batch->type === 'ticket' ? 'event_wallets' : 'merch_wallets';

    //     // Pastikan angka fresh sebelum uang benar-benar dipotong
    //     if ($batch->type === 'ticket') {
    //         TicketWalletService::recalculate($batch->event_id);
    //     } else {
    //         MerchWalletService::recalculate($batch->event_id);
    //     }

    //     $wallet = DB::table($walletTable)->where('event_id', $batch->event_id)->first();

    //     // Sumber dana refund = SELURUH kas riil event (available + held), bukan hanya plafon
    //     // available (50%). Untuk event cancelled available memang 0 sehingga otomatis setara held.
    //     // Utang HANYA muncul bila refund melebihi kas riil (mis. EO sudah menarik dananya duluan).
    //     $sumberSaldoUang = $wallet ? ($wallet->available_balance + $wallet->held_balance) : 0;

    //     DB::beginTransaction();
    //     try {
    //         if ($sumberSaldoUang < $totalBebanEO) {
    //             $kekuranganDana = $totalBebanEO - $sumberSaldoUang;

    //             if ($wallet) {
    //                 // Kosongkan kas riil; kekurangan menjadi utang (sekali catat), bukan saldo minus ganda.
    //                 DB::table($walletTable)->where('event_id', $batch->event_id)->update([
    //                     'available_balance' => 0,
    //                     'held_balance'      => 0,
    //                 ]);
    //                 DB::table($walletTable)->where('event_id', $batch->event_id)->increment('negative_balance', $kekuranganDana);
    //                 DB::table($walletTable)->where('event_id', $batch->event_id)->update(['withdraw_locked' => 1]);
    //             }

    //             // Catat utang EO (dengan tipe komoditas agar pelunasan diarahkan ke dompet yang benar)
    //             EODebt::create([
    //                 'eo_id'          => $batch->eo_id,
    //                 'event_id'       => $batch->event_id,
    //                 'type'           => $batch->type,
    //                 'total_debt'     => $kekuranganDana,
    //                 'remaining_debt' => $kekuranganDana,
    //                 'status'         => 'unpaid',
    //             ]);

    //             DB::table('eo')->where('id', $batch->eo_id)->increment('total_debt', $kekuranganDana);
    //         }
    //         // Bila kas mencukupi tidak ada pemotongan manual: recalculate() di akhir menyusun
    //         // ulang available/held dari paidTotal setelah transaksi di-flip menjadi 'refunded'.

    //         // Potong wallet platform untuk biaya mass transfer
    //         DB::table('platform_wallets')->where('id', 1)->update([
    //             'total_refund_fees_spent' => DB::raw("total_refund_fees_spent + $biayaOperasionalXendit"),
    //             'current_balance'         => DB::raw("current_balance - $biayaOperasionalXendit")
    //         ]);

    //         // Update status item refund
    //         foreach ($pendingRefunds as $refund) {
    //             $relation = $batch->type === 'ticket' ? $refund->transaction : $refund->transactionMerch;
    //             $pureAmountToBuyer = $relation ? $relation->total_amount : $refund->grand_total_refunded;

    //             $refund->update([
    //                 'grand_total_refunded' => $pureAmountToBuyer, 
    //                 'status'               => 'refunded',
    //                 'processed_at'         => now(),
    //             ]);
    //         }

    //         // Sinkronisasi payment_status transaksi asli menjadi 'refunded'
    //         $targetTable = $batch->type === 'ticket' ? 'transactions' : 'transaction_merch';
    //         $foreignKey = $batch->type === 'ticket' ? 'transaction_id' : 'transaction_merch_id';
    //         $ids = $pendingRefunds->pluck($foreignKey)->filter()->toArray();

    //         if (!empty($ids)) {
    //             DB::table($targetTable)->whereIn('id', $ids)->update([
    //                 'payment_status' => 'refunded',
    //                 'updated_at'     => now()
    //             ]);
    //         }

    //         $batch->update(['status' => 'completed']);

    //         DB::commit();

    //         if ($batch->type === 'ticket') {
    //             TicketWalletService::recalculate($batch->event_id);
    //         } else {
    //             MerchWalletService::recalculate($batch->event_id);
    //         }
    //         return redirect()->route('admin.refunds.index', ['tab' => $batch->type])
    //             ->with('success', 'Batch berhasil ditutup sepenuhnya dan finansial disinkronkan.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', 'Gagal memproses penyelesaian akibat kesalahan database: ' . $e->getMessage());
    //     }
    // }

    /**
     * 🏁 5. Aksi Tombol: Menyelesaikan Batch Refund
     * Sudah TIDAK melakukan pemotongan saldo di sini — itu terjadi per-item
     * di webhook payout.succeeded. Method ini murni penutup administratif.
     */
    public function completeBatch(Request $request, $id)
    {
        $batch = RefundBatch::with('event')->findOrFail($id);

        if ($batch->status !== 'closed') {
            return redirect()->back()->with('error', 'Batch wajib dikunci/ditutup terlebih dahulu sebelum diselesaikan.');
        }

        $belumFinal = Refund::where('refund_batch_id', $batch->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        if ($belumFinal > 0) {
            return redirect()->back()->with('error', "Masih ada {$belumFinal} refund yang belum selesai diproses (pending/processing). Kirim ke Xendit dulu atau tunggu webhook masuk.");
        }

        // Refund yang gagal transfer / perlu ditinjau WAJIB ditangani dulu: kirim ulang
        // (retry) sampai berhasil, atau tandai gagal permanen (reject). Batch tidak boleh
        // ditutup selama masih ada yang menggantung — supaya tidak ada pembeli yang
        // dananya tak jelas tanpa penanda apa pun.
        $belumDitangani = Refund::where('refund_batch_id', $batch->id)
            ->whereIn('status', ['failed', 'needs_review'])
            ->count();

        if ($belumDitangani > 0) {
            return redirect()->back()->with('error', "Masih ada {$belumDitangani} refund gagal/perlu ditinjau. Kirim ulang (retry) sampai berhasil, atau tandai gagal permanen (reject) dulu sebelum menyelesaikan batch.");
        }

        $jumlahGagal = Refund::where('refund_batch_id', $batch->id)->where('status', 'rejected')->count();

        $batch->update(['status' => 'completed']);

        $message = 'Batch berhasil diselesaikan.';
        if ($jumlahGagal > 0) {
            $message .= " Catatan: {$jumlahGagal} refund gagal permanen dan perlu diajukan ulang oleh pembeli.";
        }

        return redirect()->route('admin.refunds.index', ['tab' => $batch->type])->with('success', $message);
    }

    /**
     * 🧾 6. Ekspor data mass transfer Xendit
     */
    public function exportXendit($batchId)
    {
        $batch = DB::table('refund_batches')->where('id', $batchId)->first();

        if (!$batch) {
            return redirect()->back()->with('error', 'Batch refund tidak ditemukan.');
        }

        if ($batch->status !== 'closed') {
            return redirect()->back()->with('error', 'Proteksi Gagal: Anda harus mengunci status batch ini terlebih dahulu.');
        }

        $query = DB::table('refunds')
            ->where('refunds.refund_batch_id', $batchId)
            ->where('refunds.status', 'pending');

        if ($batch->type === 'ticket') {
            $query->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
                  ->join('events', 'transactions.event_id', '=', 'events.id')
                  ->select(
                      'refunds.id',
                      'transactions.id as refund_code', 
                      'refunds.grand_total_refunded as amount', 
                      'refunds.bank_name',
                      'refunds.account_number',
                      'refunds.account_name',
                      'transactions.email as user_email', 
                      'events.title as event_name' 
                  );
        } else {
            $query->join('transaction_merch', 'refunds.transaction_merch_id', '=', 'transaction_merch.id')
                  ->join('events', 'transaction_merch.event_id', '=', 'events.id')
                  ->select(
                      'refunds.id',
                      'transaction_merch.id as refund_code', 
                      'refunds.grand_total_refunded as amount', 
                      'refunds.bank_name',
                      'refunds.account_number',
                      'refunds.account_name',
                      'transaction_merch.email as user_email', 
                      'events.title as event_name' 
                  );
        }

        $refundItems = $query->get();

        if ($refundItems->isEmpty()) {
            return redirect()->back()->with('warning', 'Tidak ada data antrean rekening di dalam batch yang siap diekspor.');
        }

        $cleanBatchName = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $batch->name));
        $fileName = 'XENDIT_TEMPLATE_' . strtoupper($cleanBatchName) . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new RefundXenditExport($refundItems), $fileName);
    }


/**
     * 🚀 7. Kirim seluruh refund pending/failed di batch ke Xendit Payouts API
     */
public function sendToXendit($id, XenditPayoutService $payoutService)
{
    $batch = RefundBatch::findOrFail($id);

    if ($batch->status !== 'closed') {
        return redirect()->back()->with('error', 'Batch wajib dikunci (closed) terlebih dahulu sebelum dikirim ke Xendit.');
    }

    $pendingRefunds = Refund::where('refund_batch_id', $batch->id)
        ->whereIn('status', ['pending', 'failed'])
        ->get();

    if ($pendingRefunds->isEmpty()) {
        return redirect()->back()->with('warning', 'Tidak ada antrean refund yang perlu dikirim di batch ini.');
    }

    // Batch TETAP 'closed'. Yang bergerak hanya status per-item refund.
    $successCount = 0;
    $failedCount  = 0;
    $failedItems  = [];

    foreach ($pendingRefunds as $refund) {
        $result = $payoutService->createPayout($refund);
        if ($result['success']) {
            $successCount++;
        } else {
            $failedCount++;
            $failedItems[] = '#' . $refund->id . ': ' . $result['message'];
        }
    }

    $message = "{$successCount} payout berhasil dikirim ke Xendit dan sedang diproses.";
    if ($failedCount > 0) {
        $message .= " {$failedCount} gagal dibuat: " . implode('; ', array_slice($failedItems, 0, 5));
        return redirect()->route('admin.refunds.show', $batch->id)->with('warning', $message);
    }

    return redirect()->route('admin.refunds.show', $batch->id)->with('success', $message);
}

    /**
     * 🔁 8. Kirim ulang 1 refund yang gagal (opsional: koreksi data rekening dulu)
     */
    public function retryRefund(Request $request, $refundId, XenditPayoutService $payoutService)
    {
        $refund = Refund::findOrFail($refundId);

        if ($refund->status !== 'failed') {
            return redirect()->back()->with('error', 'Hanya refund berstatus gagal yang bisa dikirim ulang.');
        }

        // Admin boleh koreksi data rekening sebelum retry, kalau memang itu penyebab gagalnya
        if ($request->filled('bank_name') || $request->filled('account_number') || $request->filled('account_name')) {
            $request->validate([
                'bank_name'      => 'required|string',
                'account_number' => 'required|string|max:50',
                'account_name'   => 'required|string|max:150',
            ]);

            $refund->update([
                'bank_name'      => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name'   => $request->account_name,
            ]);
        }

        $result = $payoutService->createPayout($refund);

        return redirect()->back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Refund berhasil dikirim ulang ke Xendit.' : ('Gagal kirim ulang: ' . $result['message'])
        );
    }

    /**
     * ❌ 9. Tandai refund gagal permanen — pembeli wajib cek data & ajukan ulang
     */
    public function rejectRefund($refundId)
    {
        $refund = Refund::findOrFail($refundId);

        if ($refund->status !== 'failed') {
            return redirect()->back()->with('error', 'Hanya refund berstatus gagal yang bisa ditandai gagal permanen.');
        }

        $refund->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'Refund #' . $refund->id . ' ditandai gagal permanen. Pembeli akan diminta mengajukan ulang.');
    }

    /**
     * 🔄 10. Sinkronkan status payout langsung dari Xendit (fallback bila webhook
     * tidak/terlambat diterima — mis. saat tunnel down, atau di sandbox yang butuh
     * simulasi manual). Menarik status real lalu menerapkan aksi yang sama dgn webhook.
     */
    public function syncStatus($refundId, XenditPayoutService $payoutService)
    {
        $refund = Refund::findOrFail($refundId);

        if ($refund->status !== 'processing') {
            return redirect()->back()->with('error', 'Hanya refund berstatus "processing" yang perlu disinkronkan. Status saat ini: ' . $refund->status . '.');
        }

        $result = $payoutService->fetchPayoutStatus($refund);

        if (!$result['success']) {
            Log::warning('Sinkronisasi status payout gagal menghubungi Xendit.', [
                'refund_id' => $refund->id,
                'message'   => $result['message'],
            ]);
            return redirect()->back()->with('error', 'Gagal menghubungi Xendit: ' . $result['message']);
        }

        $status = $result['status'];
        Log::info('🔄 Sinkronisasi manual status payout oleh admin.', [
            'refund_id'    => $refund->id,
            'xendit_status'=> $status,
        ]);

        if ($status === 'SUCCEEDED') {
            try {
                $r = RefundSettlementService::settleSuccessfulPayout($refund->id);
                $msg = $r === 'already'
                    ? 'Refund ini ternyata sudah selesai diproses sebelumnya.'
                    : 'Status Xendit: SUKSES. Saldo & pembukuan berhasil disinkronkan, refund kini selesai.';
                return redirect()->back()->with('success', $msg);
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', 'Status Xendit sukses, tetapi gagal memproses pembukuan: ' . $e->getMessage());
            }
        }

        if ($status === 'FAILED') {
            Refund::where('id', $refund->id)->where('status', 'processing')->update([
                'status'               => 'failed',
                'xendit_payout_status' => 'FAILED',
                'failure_code'         => $result['raw']['failure_code'] ?? 'UNKNOWN',
                'failure_message'      => $result['raw']['failure_code'] ?? 'Payout gagal (hasil sinkronisasi manual).',
                'updated_at'           => now(),
            ]);
            return redirect()->back()->with('warning', 'Status Xendit: GAGAL. Refund ditandai "failed" — silakan Retry atau tandai gagal permanen.');
        }

        // ACCEPTED / REQUESTED / PENDING — payout belum tuntas di Xendit.
        Refund::where('id', $refund->id)->update(['xendit_payout_status' => $status]);
        return redirect()->back()->with('info', "⏳ Payout masih diproses Xendit (status: {$status}) — transfer belum tuntas, jadi belum ada yang perlu dicatat. Ini normal. Jika Anda memakai mode sandbox, simulasikan dulu payout-nya di menu Payouts Xendit, lalu klik Sinkronkan Status lagi. Di mode live, cukup tunggu — status akan otomatis tuntas.");
    }
}
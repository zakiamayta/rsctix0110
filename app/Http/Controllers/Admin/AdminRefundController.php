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

        return view('admin.refunds.index', compact(
            'batches', 
            'eligibleEvents', 
            'allEventsWithBatches', 
            'eventNewsLogs',
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
    public function completeBatch(Request $request, $id)
    {
        $batch = RefundBatch::with('event')->findOrFail($id);

        if ($batch->status !== 'closed') {
            return redirect()->back()->with('error', 'Batch wajib dikunci/ditutup terlebih dahulu sebelum diselesaikan.');
        }

        $pendingRefunds = Refund::where('refund_batch_id', $batch->id)
            ->where('status', 'pending')
            ->with(['transaction', 'transactionMerch'])
            ->get();

        if ($pendingRefunds->isEmpty()) {
            $batch->update(['status' => 'completed']);
            return redirect()->route('admin.refunds.index', ['tab' => $batch->type])->with('success', 'Batch diselesaikan tanpa antrean transfer.');
        }

        // Kalkulasi total beban EO berdasarkan jenis komoditas masing-masing
        $totalBebanEO = 0;
        foreach ($pendingRefunds as $refund) {
            $relation = $batch->type === 'ticket' ? $refund->transaction : $refund->transactionMerch;
            if ($relation) {
                $totalBebanEO += $relation->total_amount;
            }
        }

        if ($totalBebanEO <= 0) {
            $batch->update(['status' => 'completed']);
            return redirect()->route('admin.refunds.index', ['tab' => $batch->type])->with('success', 'Batch ditutup tanpa pemotongan saldo.');
        }

        $biayaOperasionalXendit = $pendingRefunds->sum('refunds_tax');
        $walletTable = $batch->type === 'ticket' ? 'event_wallets' : 'merch_wallets';

        // Pastikan angka fresh sebelum uang benar-benar dipotong
        if ($batch->type === 'ticket') {
            TicketWalletService::recalculate($batch->event_id);
        } else {
            MerchWalletService::recalculate($batch->event_id);
        }

        $wallet = DB::table($walletTable)->where('event_id', $batch->event_id)->first();
        
        $isCancelled = ($batch->event->status === 'cancelled');
        $sumberSaldoUang = $wallet ? ($isCancelled ? $wallet->held_balance : $wallet->available_balance) : 0;

        DB::beginTransaction();
        try {
            if ($sumberSaldoUang >= $totalBebanEO) {
                $fieldToDecrement = $isCancelled ? 'held_balance' : 'available_balance';
                DB::table($walletTable)->where('event_id', $batch->event_id)->decrement($fieldToDecrement, $totalBebanEO);
            } else {
                $kekuranganDana = $totalBebanEO - $sumberSaldoUang;

                if ($sumberSaldoUang > 0) {
                    $fieldToZero = $isCancelled ? 'held_balance' : 'available_balance';
                    DB::table($walletTable)->where('event_id', $batch->event_id)->update([$fieldToZero => 0]);
                }

                // Catat utang EO
                EODebt::create([
                    'eo_id'          => $batch->eo_id,
                    'event_id'       => $batch->event_id,
                    'total_debt'     => $kekuranganDana,
                    'remaining_debt' => $kekuranganDana,
                    'status'         => 'unpaid',
                ]);

                if ($wallet) {
                    DB::table($walletTable)->where('event_id', $batch->event_id)->increment('negative_balance', $kekuranganDana);
                    DB::table($walletTable)->where('event_id', $batch->event_id)->update(['withdraw_locked' => 1]);
                }

                DB::table('eo')->where('id', $batch->eo_id)->increment('total_debt', $kekuranganDana);
            }

            // Potong wallet platform untuk biaya mass transfer
            DB::table('platform_wallets')->where('id', 1)->update([
                'total_refund_fees_spent' => DB::raw("total_refund_fees_spent + $biayaOperasionalXendit"),
                'current_balance'         => DB::raw("current_balance - $biayaOperasionalXendit")
            ]);

            // Update status item refund
            foreach ($pendingRefunds as $refund) {
                $relation = $batch->type === 'ticket' ? $refund->transaction : $refund->transactionMerch;
                $pureAmountToBuyer = $relation ? $relation->total_amount : $refund->grand_total_refunded;

                $refund->update([
                    'grand_total_refunded' => $pureAmountToBuyer, 
                    'status'               => 'refunded',
                    'processed_at'         => now(),
                ]);
            }

            // Sinkronisasi payment_status transaksi asli menjadi 'refunded'
            $targetTable = $batch->type === 'ticket' ? 'transactions' : 'transaction_merch';
            $foreignKey = $batch->type === 'ticket' ? 'transaction_id' : 'transaction_merch_id';
            $ids = $pendingRefunds->pluck($foreignKey)->filter()->toArray();

            if (!empty($ids)) {
                DB::table($targetTable)->whereIn('id', $ids)->update([
                    'payment_status' => 'refunded',
                    'updated_at'     => now()
                ]);
            }

            $batch->update(['status' => 'completed']);

            DB::commit();

            if ($batch->type === 'ticket') {
                TicketWalletService::recalculate($batch->event_id);
            } else {
                MerchWalletService::recalculate($batch->event_id);
            }
            return redirect()->route('admin.refunds.index', ['tab' => $batch->type])
                ->with('success', 'Batch berhasil ditutup sepenuhnya dan finansial disinkronkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses penyelesaian akibat kesalahan database: ' . $e->getMessage());
        }
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
}
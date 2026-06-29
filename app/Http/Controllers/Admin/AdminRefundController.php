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

class AdminRefundController extends Controller
{
    public function __construct()
    {
        // Berikan proteksi middleware agar hanya Admin Utama (role: admin) yang bisa masuk
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || auth()->user()->role !== 'admin') {
                abort(403, 'Aksi ini hanya diizinkan untuk Admin Utama.');
            }
            return $next($request);
        });
    }

/**
     * 📊 1. Halaman Utama Dashboard Refund Admin (Mendukung Tab Navigasi & Proteksi Kondisional)
     */
    public function index(Request $request)
    {
        // Tangkap ID Event yang ingin difilter dari URL
        $filterEventId = $request->input('filter_event_id');
        
        // 🛍️ Ambil jenis tab aktif (default: ticket)
        $activeTab = $request->input('tab', 'ticket');

        // Master List Event: Mengambil daftar event unik yang memiliki data batch untuk isi pilihan di Dropdown Filter sesuai tab tipe
        $allEventsWithBatches = Event::whereHas('refundBatches', function($q) use ($activeTab) {
                $q->where('type', $activeTab);
            })
            ->orderBy('title', 'asc')
            ->get();

        // Ambil daftar batch dengan kondisi filter tipe tab aktif & event_id jika ditentukan
        $batches = RefundBatch::with(['event', 'eo'])
            ->withCount(['refunds as total_pengajuan'])
            ->where('type', $activeTab) // Hanya tarik batch yang sesuai dengan tab aktif
            ->when($filterEventId, function ($query) use ($filterEventId) {
                return $query->where('event_id', $filterEventId);
            })
            ->latest()
            ->get();

        // 🔥 KONDISIONAL DATA DROPDOWN PEMBUAT BATCH BARU & LOG BERITA BERDASARKAN KOMODITAS TAB
        if ($activeTab === 'ticket') {
            // Dropdown Batch Tiket Baru (Event Canceled atau Reschedule)
            $eligibleEvents = Event::where(function($query) {
                    $query->where('status', 'cancelled')
                          ->orWhere(function($q) {
                              $q->where('status', 'approved')
                                ->where('is_rescheduled', '>', 0);
                          });
                })
                ->whereDoesntHave('refundBatches', function($query) {
                    $query->where('type', 'ticket')->whereIn('status', ['open', 'closed']);
                })
                ->with(['eo', 'eventWallet'])
                ->get();

            // 📰 Riwayat Log Berita Khusus Tiket (Event Batal / Reschedule)
            $eventNewsLogs = Event::where(function($query) {
                    $query->where('status', 'cancelled')
                          ->orWhere(function($q) {
                              $q->where('status', 'approved')
                                ->where('is_rescheduled', '>', 0);
                          });
                })
                ->with('eo')
                ->latest('updated_at')
                ->take(5)
                ->get();

        } else {
            // Dropdown Batch Merch Baru: HANYA jika status canceled DAN keputusan EO adalah 'refund'
            $eligibleEvents = Event::where('status', 'cancelled')
                ->where('merch_cancel_decision', 'refund') // 🔒 Gerbang pengunci utama
                ->whereDoesntHave('refundBatches', function($query) {
                    $query->where('type', 'merch')->whereIn('status', ['open', 'closed']);
                })
                ->with(['eo', 'merchWallet'])
                ->get();

            // 📰 Riwayat Log Berita Khusus Merchandise (Hanya yang berhak refund)
            $eventNewsLogs = Event::where('status', 'cancelled')
                ->where('merch_cancel_decision', 'refund') // 🔒 Menyembunyikan event merchandise yang statusnya 'ship_independently'
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
     * 🔨 2. Membuat Berkas Antrean Batch Refund Baru secara Mandiri (Tiket / Merch)
     */
    public function storeBatch(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'type'     => 'required|in:ticket,merch' // Validasi tipe penampung batch
        ]);

        $event = Event::findOrFail($request->event_id);

        // Keamanan ganda: pastikan event tidak punya batch aktif sejenis yang belum rampung
        $exists = RefundBatch::where('event_id', $event->id)
            ->where('type', $request->type)
            ->whereIn('status', ['open', 'closed'])
            ->exists();
            
        if ($exists) {
            return redirect()->back()->with('error', 'Batch refund aktif untuk komoditas ini sudah ada.');
        }

        // Hitung total batch dari event ini berdasarkan tipe komoditas untuk keperluan penamaan otomatis
        $batchCount = RefundBatch::where('event_id', $event->id)->where('type', $request->type)->count() + 1;
        $labelType = $request->type === 'ticket' ? 'Tiket' : 'Merchandise';

        DB::beginTransaction();
        try {
            // 1. Buat Batch Baru dengan melampirkan data jenis type komoditas
            $batch = RefundBatch::create([
                'eo_id'      => $event->eo_id,
                'event_id'   => $event->id,
                'type'       => $request->type,
                'name'       => "Refund " . $labelType . " " . $event->title . " - Batch " . $batchCount,
                'start_date' => now()->toDateString(),
                'end_date'   => now()->addDays(14)->toDateString(),
                'status'     => 'open',
            ]);

            // 2. Serap data 'waiting' ke dalam batch berdasarkan tipe komoditas secara terpisah
            if ($request->type === 'ticket') {
                DB::table('refunds')
                    ->whereIn('transaction_id', function($query) use ($event) {
                        $query->select('id')
                            ->from('transactions')
                            ->where('event_id', $event->id);
                    })
                    ->whereNull('refund_batch_id')
                    ->where('status', 'waiting')
                    ->update([
                        'refund_batch_id' => $batch->id,
                        'status'          => 'pending',
                        'updated_at'      => now()
                    ]);
            } else {
                DB::table('refunds')
                    ->whereIn('transaction_merch_id', function($query) use ($event) {
                        $query->select('id')
                            ->from('transaction_merch')
                            ->where('event_id', $event->id);
                    })
                    ->whereNull('refund_batch_id')
                    ->where('status', 'waiting')
                    ->update([
                        'refund_batch_id' => $batch->id,
                        'status'          => 'pending',
                        'updated_at'      => now()
                    ]);
            }

            DB::commit();
            return redirect()->route('admin.refunds.index', ['tab' => $request->type])
                ->with('success', 'Batch Refund baru untuk ' . $labelType . ' berhasil diaktifkan secara mandiri!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membuka batch baru: ' . $e->getMessage());
        }
    }

    /**
     * 🔍 3. Halaman Detail Pengajuan Refund di dalam suatu Batch (Mendukung Tiket & Merch)
     */
    public function show($id)
    {
        $batch = RefundBatch::with(['event', 'eo'])->findOrFail($id);
        
        // Ambil data refund khusus untuk batch saat ini
        $refunds = Refund::where('refund_batch_id', $batch->id)
            ->with(['transaction', 'transactionMerch']) // Eager load dua relasi opsional
            ->latest()
            ->get();

        // Hitung total akumulasi dana refund murni di batch ini
        $totalDanaRefund = $refunds->sum('grand_total_refunded');

        // Hitung Estimasi Potongan Biaya Mass Transfer Xendit (Rp2.500 per Antrean 'pending')
        $jumlahAntreanPending = $refunds->where('status', 'pending')->count();
        $estimasiBiayaXendit = $jumlahAntreanPending * 2500;

        // Ambil info saldo wallet tujuan berdasarkan tipe komoditas batch
        if ($batch->type === 'ticket') {
            $wallet = DB::table('event_wallets')->where('event_id', $batch->event_id)->first();
        } else {
            $wallet = DB::table('merch_wallets')->where('event_id', $batch->event_id)->first();
        }
        $availableBalance = $wallet ? ($wallet->available_balance + $wallet->held_balance) : 0;

        // =========================================================================
        // 🔥 LOGIKA HITUNG SERVICE TAX GLOBAL EVENT (MENYESUAIKAN TIPE TIKET / MERCH)
        // =========================================================================
        if ($batch->type === 'ticket') {
            $totalTaxSemuaTransaksi = DB::table('transactions')
                ->where('event_id', $batch->event_id)
                ->whereIn('payment_status', ['paid', 'refunded'])
                ->sum('service_tax');
        } else {
            $totalTaxSemuaTransaksi = DB::table('transaction_merch')
                ->where('event_id', $batch->event_id)
                ->whereIn('payment_status', ['paid', 'refunded'])
                ->sum('service_tax');
        }

        // Hitung total SERVICE TAX yang SUDAH HANGUS karena batch masa lalu yang sudah selesai ('completed')
        $taxSudahDirefundSelesai = DB::table('refunds')
            ->join('refund_batches', 'refunds.refund_batch_id', '=', 'refund_batches.id')
            ->where('refund_batches.event_id', $batch->event_id)
            ->where('refund_batches.type', $batch->type)
            ->where('refund_batches.status', 'completed')
            ->where('refunds.status', 'refunded')
            ->sum('refunds.refunds_tax');

        $totalServiceTaxEvent = $totalTaxSemuaTransaksi - $taxSudahDirefundSelesai;
        if ($totalServiceTaxEvent < 0) {
            $totalServiceTaxEvent = 0;
        }
        // =========================================================================

        return view('admin.refunds.show', compact(
            'batch', 
            'refunds', 
            'totalDanaRefund', 
            'availableBalance', 
            'totalServiceTaxEvent', 
            'estimasiBiayaXendit'
        ));
    }
    
    /**
     * 🔄 4. Mengubah Status Batch (Open <=> Closed) Mandiri per Komoditas
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
                $message = 'Batch berhasil dikunci! Pembeli baru yang masuk setelah ini otomatis berada di luar antrean batch ini.';
            } else {
                $batch->update(['status' => 'open']);

                // Tarik kembali data waiting berdasarkan kesesuaian jenis komoditas batch terkait
                if ($batch->type === 'ticket') {
                    DB::table('refunds')
                        ->whereIn('transaction_id', function($query) use ($batch) {
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
                    DB::table('refunds')
                        ->whereIn('transaction_merch_id', function($query) use ($batch) {
                            $query->select('id')->from('transaction_merch')->where('event_id', $batch->event_id);
                        })
                        ->whereNull('refund_batch_id')
                        ->where('status', 'waiting')
                        ->update([
                            'refund_batch_id' => $batch->id,
                            'status'          => 'pending',
                            'updated_at'      => now()
                        ]);
                }

                $message = 'Batch dibuka kembali! Data antrean waiting yang bersangkutan telah ditarik masuk.';
            }

            DB::commit();
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengubah status gerbang: ' . $e->getMessage());
        }
    }

    /**
     * 🏁 5. Aksi Tombol: Menyelesaikan Batch Refund (Mendukung Alokasi Dompet Tiket vs Merch)
     */
    public function completeBatch(Request $request, $id)
    {
        $batch = RefundBatch::with('event')->findOrFail($id);

        if ($batch->status !== 'closed') {
            return redirect()->back()->with('error', 'Batch wajib dikunci/ditutup terlebih dahulu sebelum diselesaikan.');
        }

        $pendingRefunds = Refund::where('refund_batch_id', $batch->id)
            ->where('status', 'pending')
            ->get();

        if ($pendingRefunds->isEmpty()) {
            $batch->update(['status' => 'completed']);
            return redirect()->route('admin.refunds.index', ['tab' => $batch->type])->with('success', 'Batch diselesaikan sukses tanpa antrean transfer.');
        }

        // Kalkulasi total beban EO berdasarkan jenis komoditas masing-masing
        $totalBebanEO = 0;
        foreach ($pendingRefunds as $refund) {
            if ($batch->type === 'ticket') {
                if ($refund->transaction) {
                    $totalBebanEO += $refund->transaction->total_amount;
                }
            } else {
                if ($refund->transactionMerch) {
                    $totalBebanEO += $refund->transactionMerch->total_amount;
                }
            }
        }

        if ($totalBebanEO <= 0) {
            $batch->update(['status' => 'completed']);
            return redirect()->route('admin.refunds.index', ['tab' => $batch->type])->with('success', 'Batch ditutup sukses tanpa pemotongan saldo.');
        }

        $biayaOperasionalXendit = $pendingRefunds->sum('refunds_tax');
        
        // Tentukan Target Tabel & Query Saldo sesuai dengan tipe batch (event_wallets vs merch_wallets)
        $walletTable = $batch->type === 'ticket' ? 'event_wallets' : 'merch_wallets';
        $wallet = DB::table($walletTable)->where('event_id', $batch->event_id)->first();
        
        // Karena merchandise dikelola terpisah, penentuan kondisi cancel mengikuti status utama event
        $isCancelled = ($batch->event->status === 'cancelled');
        $sumberSaldoUang = $wallet ? ($isCancelled ? $wallet->held_balance : $wallet->available_balance) : 0;

        DB::beginTransaction();
        try {
            if ($sumberSaldoUang >= $totalBebanEO) {
                if ($isCancelled) {
                    DB::table($walletTable)->where('event_id', $batch->event_id)->decrement('held_balance', $totalBebanEO);
                } else {
                    // Jika event berstatus normal (misal: kasus tiket refund ajuan manual / reschedule)
                    DB::table($walletTable)->where('event_id', $batch->event_id)->decrement('available_balance', $totalBebanEO);
                }
            } else {
                $kekuranganDana = $totalBebanEO - $sumberSaldoUang;

                if ($sumberSaldoUang > 0) {
                    if ($isCancelled) {
                        DB::table($walletTable)->where('event_id', $batch->event_id)->update(['held_balance' => 0]);
                    } else {
                        DB::table($walletTable)->where('event_id', $batch->event_id)->update(['available_balance' => 0]);
                    }
                }

                // Catat transaksi pencatatan utang EO ke tabel eo_debts
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

            // Potong wallet platform untuk biaya operasional mass transfer
            DB::table('platform_wallets')
                ->where('id', 1)
                ->update([
                    'total_refund_fees_spent' => DB::raw("total_refund_fees_spent + $biayaOperasionalXendit"),
                    'current_balance'         => DB::raw("current_balance - $biayaOperasionalXendit")
                ]);

            // Set status pengajuan per item refund menjadi refunded
            foreach ($pendingRefunds as $refund) {
                if ($batch->type === 'ticket') {
                    $pureAmountToBuyer = $refund->transaction ? $refund->transaction->total_amount : $refund->grand_total_refunded;
                } else {
                    $pureAmountToBuyer = $refund->transactionMerch ? $refund->transactionMerch->total_amount : $refund->grand_total_refunded;
                }

                $refund->update([
                    'grand_total_refunded' => $pureAmountToBuyer, 
                    'status'               => 'refunded',
                    'processed_at'         => now(),
                    'updated_at'           => now()
                ]);
            }

            // Sinkronisasi status payment_status item transaksi pembeli menjadi 'refunded' agar omzet sinkron
            if ($batch->type === 'ticket') {
                $transactionIds = $pendingRefunds->pluck('transaction_id')->filter()->toArray();
                if (!empty($transactionIds)) {
                    DB::table('transactions')->whereIn('id', $transactionIds)->update([
                        'payment_status' => 'refunded',
                        'updated_at'     => now()
                    ]);
                }
            } else {
                $merchTxIds = $pendingRefunds->pluck('transaction_merch_id')->filter()->toArray();
                if (!empty($merchTxIds)) {
                    DB::table('transaction_merch')->whereIn('id', $merchTxIds)->update([
                        'payment_status' => 'refunded',
                        'updated_at'     => now()
                    ]);
                }
            }

            $batch->update(['status' => 'completed']);

            DB::commit();
            return redirect()->route('admin.refunds.index', ['tab' => $batch->type])
                ->with('success', 'Batch berhasil ditutup sepenuhnya! Status finansial dan transaksi komoditas sukses disinkronkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses penyelesaian akibat kesalahan database: ' . $e->getMessage());
        }
    }

    /**
     * 🧾 6. Ekspor data mass transfer Xendit (Mendukung Data Tiket & Merch)
     */
    public function exportXendit($batchId)
    {
        $batch = DB::table('refund_batches')->where('id', $batchId)->first();

        if (!$batch) {
            return redirect()->back()->with('error', 'Batch refund tidak ditemukan.');
        }

        if ($batch->status !== 'closed') {
            return redirect()->back()->with('error', 'Proteksi Gagal: Anda harus mengunci status batch ini terlebih dahulu sebelum melakukan ekspor berkas.');
        }

        // Modifikasi query penyerapan data xendit dengan kondisional item join dinamis
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
            return redirect()->back()->with('warning', 'Tidak ada data antrean rekening di dalam batch ini yang siap diekspor.');
        }

        $cleanBatchName = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $batch->name));
        $fileName = 'XENDIT_TEMPLATE_' . strtoupper($cleanBatchName) . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new RefundXenditExport($refundItems), $fileName);
    }
}
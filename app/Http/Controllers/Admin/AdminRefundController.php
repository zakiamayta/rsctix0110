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
     * 📊 1. Halaman Utama Dashboard Refund Admin
     */
    public function index(Request $request)
    {
        // Tangkap ID Event yang ingin difilter dari URL
        $filterEventId = $request->input('filter_event_id');

        // Master List Event: Mengambil daftar event unik yang memiliki data batch untuk isi pilihan di Dropdown Filter
        $allEventsWithBatches = Event::whereHas('refundBatches')->orderBy('title', 'asc')->get();

        // Ambil daftar batch dengan kondisi filter jika ditentukan
        $batches = RefundBatch::with(['event', 'eo'])
            ->withCount(['refunds as total_pengajuan'])
            ->when($filterEventId, function ($query) use ($filterEventId) {
                return $query->where('event_id', $filterEventId);
            })
            ->latest()
            ->get();

        // 🔥 PERBAIKAN PILIHAN EVENT: Ambil list Event yang statusnya cancelled ATAU disetujui reschedule
        // Dan event tersebut BELUM memiliki batch refund yang berstatus 'open' atau 'closed'
        // Event berstatus 'approved' normal otomatis tidak akan lolos syarat ini karena ada pengondisian 'is_rescheduled > 0'
        $eligibleEvents = Event::where(function($query) {
                $query->where('status', 'cancelled')
                      ->orWhere(function($q) {
                          $q->where('status', 'approved')
                            ->where('is_rescheduled', '>', 0);
                      });
            })
            ->whereDoesntHave('refundBatches', function($query) {
                $query->whereIn('status', ['open', 'closed']);
            })
            ->with(['eo', 'eventWallet'])
            ->get();

        // 📰 BARU: Ambil Berita / Riwayat Perubahan Status Event Terkini (Maksimal 5 Log Terbaru)
        // Kita bisa memanfaatkan tabel 'events' langsung yang berstatus khusus sebagai trigger berita bagi Admin
        $eventNewsLogs = Event::whereIn('status', ['cancelled', 'approved'])
            ->where(function($query) {
                $query->where('status', 'cancelled')
                      ->orWhere('is_rescheduled', '>', 0);
            })
            ->with('eo')
            ->latest('updated_at')
            ->take(5)
            ->get();

        // Sertakan 'eventNewsLogs' ke dalam view
        return view('admin.refunds.index', compact(
            'batches', 
            'eligibleEvents', 
            'allEventsWithBatches', 
            'eventNewsLogs'
        ));
    }
    public function storeBatch(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        $event = Event::findOrFail($request->event_id);

        // Keamanan ganda: pastikan event tidak punya batch aktif yang belum rampung
        $exists = RefundBatch::where('event_id', $event->id)->whereIn('status', ['open', 'closed'])->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'Batch refund aktif untuk event ini sudah ada.');
        }

        // Hitung total batch dari event ini untuk keperluan penamaan otomatis
        $batchCount = RefundBatch::where('event_id', $event->id)->count() + 1;

        DB::beginTransaction();
        try {
            // 1. Buat Batch Baru dengan status 'open'
            $batch = RefundBatch::create([
                'eo_id'      => $event->eo_id,
                'event_id'   => $event->id,
                'name'       => "Refund " . $event->title . " - Batch " . $batchCount,
                'start_date' => now()->toDateString(),
                'end_date'   => now()->addDays(14)->toDateString(),
                'status'     => 'open',
            ]);

            // 🔥 PERBAIKAN: Gunakan whereIn dan hapus limit(1) supaya semua pembeli EVENT INI terserap sempurna tanpa mencampur event lain
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
                    'status' => 'pending',
                    'updated_at' => now()
                ]);

            DB::commit();
            return redirect()->back()->with('success', 'Batch Refund baru berhasil diaktifkan! Semua antrean berstatus waiting dari event ini otomatis dialihkan ke dalam batch.');
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
        
        // 1. Ambil data refund khusus untuk batch saat ini
        $refunds = Refund::where('refund_batch_id', $batch->id)
            ->with('transaction')
            ->latest()
            ->get();

        // Hitung total akumulasi dana refund murni (Beban Tiket) di batch ini
        $totalDanaRefund = $refunds->sum('grand_total_refunded');

        // 2. Hitung Estimasi Potongan Biaya Mass Transfer Xendit (Rp2.500 per Antrean 'pending')
        $jumlahAntreanPending = $refunds->where('status', 'pending')->count();
        $estimasiBiayaXendit = $jumlahAntreanPending * 2500;

        // 3. Ambil info saldo wallet event terkini
        $wallet = DB::table('event_wallets')->where('event_id', $batch->event_id)->first();
        $availableBalance = $wallet ? ($wallet->available_balance + $wallet->held_balance) : 0;

        // =========================================================================
        // 🔥 LOGIKA BARU: HITUNG SERVICE TAX GLOBAL EVENT (DENGAN PENGURANG BATCH SEBELUMNYA)
        // =========================================================================
        
        // A. Hitung total SERVICE TAX dari SEMUA pembeli (Paid & Refunded) di event ini
        $totalTaxSemuaTransaksi = DB::table('transactions')
            ->where('event_id', $batch->event_id)
            ->whereIn('payment_status', ['paid', 'refunded'])
            ->sum('service_tax');

        // B. Hitung total SERVICE TAX yang SUDAH HANGUS karena batch-batch sebelum ini sudah diselesaikan ('completed')
        $taxSudahDirefundSelesai = DB::table('refunds')
            ->join('refund_batches', 'refunds.refund_batch_id', '=', 'refund_batches.id')
            ->where('refund_batches.event_id', $batch->event_id)
            ->where('refund_batches.status', 'completed') // Hanya batch masa lalu yang sudah finish
            ->where('refunds.status', 'refunded')
            ->sum('refunds.refunds_tax');

        // C. Hasil Akhir: Total Tax Event yang tersisa / valid saat ini
        $totalServiceTaxEvent = $totalTaxSemuaTransaksi - $taxSudahDirefundSelesai;
        
        // Jaga-jaga agar nilai tidak minus di view
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
                // Skenario: Kunci/Tutup Batch untuk persiapan export Excel
                $batch->update(['status' => 'closed']);
                $message = 'Batch berhasil dikunci! Pembeli baru yang mengajukan refund setelah ini otomatis masuk ke status waiting (antrean luar batch).';
            } else {
                // Skenario: Buka Kembali Batch dan serap data waiting yang sempat terlewat
                $batch->update(['status' => 'open']);

                // 🔥 PERBAIKAN: Gunakan whereIn dan hapus limit(1) pada proses buka kembali gerbang
                DB::table('refunds')
                    ->whereIn('transaction_id', function($query) use ($batch) {
                        $query->select('id')
                            ->from('transactions')
                            ->where('event_id', $batch->event_id);
                    })
                    ->whereNull('refund_batch_id')
                    ->where('status', 'waiting')
                    ->update([
                        'refund_batch_id' => $batch->id,
                        'status' => 'pending',
                        'updated_at' => now()
                    ]);

                $message = 'Batch dibuka kembali! Data pembeli berstatus waiting dari event ini telah ditarik masuk kembali ke dalam batch.';
            }

            DB::commit();
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengubah status gerbang: ' . $e->getMessage());
        }
    }

    /**
     * 🏁 5. Aksi Tombol: Menyelesaikan Batch Refund (Hitung Saldo / Potong Utang EO)
     */
    public function completeBatch(Request $request, $id)
    {
        $batch = RefundBatch::with('event')->findOrFail($id);

        if ($batch->status !== 'closed') {
            return redirect()->back()->with('error', 'Batch wajib dikunci/ditutup terlebih dahulu sebelum diselesaikan pembayaran massalnya.');
        }

        // Cari tahu data refund dengan status 'pending' yang wajib diproses
        $pendingRefunds = Refund::where('refund_batch_id', $batch->id)
            ->where('status', 'pending')
            ->get();

        $totalTujuanTransfer = $pendingRefunds->count();

        if ($totalTujuanTransfer <= 0) {
            $batch->update(['status' => 'completed']);
            return redirect()->route('admin.refunds.index')->with('success', 'Batch diselesaikan sukses tanpa antrean transfer.');
        }

        $totalBebanEO = 0;
        foreach ($pendingRefunds as $refund) {
            $transaction = $refund->transaction;
            if ($transaction) {
                $totalBebanEO += $transaction->total_amount;
            }
        }

        if ($totalBebanEO <= 0) {
            $batch->update(['status' => 'completed']);
            return redirect()->route('admin.refunds.index')->with('success', 'Batch ditutup sukses tanpa pemotongan saldo.');
        }

        $biayaOperasionalXendit = Refund::where('refund_batch_id', $batch->id)
            ->where('status', 'pending')
            ->sum('refunds_tax');

        $wallet = DB::table('event_wallets')->where('event_id', $batch->event_id)->first();
        $isCancelled = ($batch->event->status === 'cancelled');
        
        // Sumber saldo penentu hutang
        $sumberSaldoUang = $wallet ? ($isCancelled ? $wallet->held_balance : $wallet->available_balance) : 0;

        DB::beginTransaction();
        try {
            if ($sumberSaldoUang >= $totalBebanEO) {
                // SINKRONISASI DOMPET: Jika event cancelled, potong held_balance secara manual.
                // Jika event normal, JANGAN potong available_balance manual agar tidak ter-overwrite rumus TicketWithdrawalController.
                if ($isCancelled) {
                    DB::table('event_wallets')->where('event_id', $batch->event_id)->decrement('held_balance', $totalBebanEO);
                }
            } else {
                $kekuranganDana = $totalBebanEO - $sumberSaldoUang;

                if ($sumberSaldoUang > 0) {
                    if ($isCancelled) {
                        DB::table('event_wallets')->where('event_id', $batch->event_id)->update(['held_balance' => 0]);
                    } else {
                        // Jika normal dan saldo tidak cukup, kita buat available_balance dinolkan sementara (sebagai representasi kas kosong)
                        DB::table('event_wallets')->where('event_id', $batch->event_id)->update(['available_balance' => 0]);
                    }
                }

                // Catat transaksi pencatatan hutang EO
                EODebt::create([
                    'eo_id'          => $batch->eo_id,
                    'event_id'       => $batch->event_id,
                    'total_debt'     => $kekuranganDana,
                    'remaining_debt' => $kekuranganDana,
                    'status'         => 'unpaid',
                ]);

                if ($wallet) {
                    DB::table('event_wallets')->where('event_id', $batch->event_id)->increment('negative_balance', $kekuranganDana);
                    // Otomatis kunci sistem penarikan dana agar EO dipaksa melakukan TOP UP saldo
                    DB::table('event_wallets')->where('event_id', $batch->event_id)->update(['withdraw_locked' => 1]);
                }

                DB::table('eo')->where('id', $batch->eo_id)->increment('total_debt', $kekuranganDana);
            }

            // Potong wallet platform untuk operasional biaya mass transfer
            DB::table('platform_wallets')
                ->where('id', 1)
                ->update([
                    'total_refund_fees_spent' => DB::raw("total_refund_fees_spent + $biayaOperasionalXendit"),
                    'current_balance'         => DB::raw("current_balance - $biayaOperasionalXendit")
                ]);

            // Set status pengajuan di batch ini menjadi refunded
            foreach ($pendingRefunds as $refund) {
                $transaction = $refund->transaction;
                $pureAmountToBuyer = $transaction ? $transaction->total_amount : $refund->grand_total_refunded;

                $refund->update([
                    'grand_total_refunded' => $pureAmountToBuyer, 
                    'status'               => 'refunded',
                    'processed_at'         => now(),
                    'updated_at'           => now()
                ]);
            }

            // 🔥 LOGIKA KUNCI: Ubah status transaksi tiket pembeli menjadi 'refunded'
            // Perubahan status ini otomatis memotong total omzet bersih ($paidTotal) di sistem,
            // sehingga saat TicketWithdrawalController dipanggil, sisa available_balance akan menyusut secara akurat & aman dari bug overwrite.
            $transactionIds = $pendingRefunds->pluck('transaction_id')->toArray();
            if (!empty($transactionIds)) {
                DB::table('transactions')
                    ->whereIn('id', $transactionIds)
                    ->update([
                        'payment_status' => 'refunded',
                        'updated_at'     => now()
                    ]);
            }

            $batch->update(['status' => 'completed']);

            DB::commit();
            return redirect()->route('admin.refunds.index')->with('success', 'Batch berhasil ditutup sepenuhnya, status transaksi disinkronkan, dan dana refund telah sukses dibukukan.');
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
            return redirect()->back()->with('error', 'Proteksi Gagal: Anda harus menutup/mengunci status batch ini terlebih dahulu agar tidak ada perubahan data masuk di tengah proses ekspor.');
        }

        $refundItems = DB::table('refunds')
            ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->where('refunds.refund_batch_id', $batchId)
            ->where('refunds.status', 'pending')
            ->select(
                'refunds.id',
                'transactions.id as refund_code', 
                'refunds.grand_total_refunded as amount', 
                'refunds.bank_name',
                'refunds.account_number',
                'refunds.account_name',
                'transactions.email as user_email', 
                'events.title as event_name' 
            )
            ->get();

        if ($refundItems->isEmpty()) {
            return redirect()->back()->with('warning', 'Tidak ada data antrean rekening penonton di dalam batch ini yang siap diekspor.');
        }

        $cleanBatchName = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $batch->name));
        $fileName = 'XENDIT_TEMPLATE_' . strtoupper($cleanBatchName) . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new RefundXenditExport($refundItems), $fileName);
    }
}
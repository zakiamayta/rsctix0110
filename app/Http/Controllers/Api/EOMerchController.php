<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EOMerchController extends Controller
{
    /**
     * 1. GET HISTORI PENARIKAN MERCHANDISE
     */
    /**
     * 1. GET HISTORI PENARIKAN MERCHANDISE (DENGAN FIX OWNER NOTE)
     */
    public function index(Request $request)
    {
        try {
            // Validasi input fleksibel
            $request->validate([
                'eo_id' => 'required|integer',
                'event_id' => 'nullable|integer', 
            ]);

            // LEFT JOIN agar data withdrawal tetap muncul walau relasi kosong
            $query = DB::table('merch_withdrawals')
                ->leftJoin('events', 'merch_withdrawals.event_id', '=', 'events.id')
                ->leftJoin('eo', 'merch_withdrawals.eo_id', '=', 'eo.id') 
                ->where('merch_withdrawals.eo_id', $request->eo_id)
                ->select(
                    'merch_withdrawals.id as withdrawal_id',
                    'merch_withdrawals.event_id',
                    'merch_withdrawals.amount',
                    'merch_withdrawals.note',
                    'merch_withdrawals.owner_note', // 🔥 FIX BUG #2: Select owner_note dari database asli
                    'merch_withdrawals.status',
                    'merch_withdrawals.transfer_proof',
                    'merch_withdrawals.created_at',
                    'merch_withdrawals.approved_at',
                    'merch_withdrawals.paid_at',
                    'events.title as event_name',
                    'eo.bank_name',
                    'eo.account_number',
                    'eo.account_name'
                );

            // Filter kondisional berdasarkan event jika dikirim dari Flutter
            if ($request->has('event_id') && !is_null($request->event_id)) {
                $query->where('merch_withdrawals.event_id', $request->event_id);
            }

            $history = $query->orderByDesc('merch_withdrawals.id')->get();

            if ($history->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ], 200);
            }

            // Format JSON untuk Flutter
            $formattedHistory = $history->map(function ($item) {
                return [
                    'id' => $item->withdrawal_id,
                    'event_id' => (int) $item->event_id,
                    'amount' => (int) $item->amount,
                    'note' => $item->note ?? '',
                    'owner_note' => $item->owner_note ?? 'Belum ada catatan dari admin.', // 🔥 FIX BUG #2: Teruskan field owner_note ke Flutter
                    'status' => $item->status ?? 'pending',
                    'transfer_proof' => $item->transfer_proof,
                    'event_name' => $item->event_name ?? 'Event Tidak Diketahui',
                    'reference_number' => '-', 
                    'bank_name' => $item->bank_name ?? '-',
                    'account_number' => $item->account_number ?? '-',
                    'account_name' => $item->account_name ?? '-',
                    'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('d M Y, H:i') : '-',
                    'approved_at' => $item->approved_at ? Carbon::parse($item->approved_at)->format('d M Y, H:i') : null,
                    'paid_at' => $item->paid_at ? Carbon::parse($item->paid_at)->format('d M Y, H:i') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedHistory,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat riwayat: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. STATISTIK DASHBOARD + LOGIKA DOMPET MERCHANDISE PER EVENT
     */
    public function merchWallets($eoId)
    {
        try {
            $events = DB::table('events')
                ->leftJoin('merch_wallets', 'events.id', '=', 'merch_wallets.event_id')
                ->join('eo', 'events.eo_id', '=', 'eo.id')
                ->where('events.eo_id', $eoId)
                ->select(
                    'events.id as event_id',
                    'events.title',
                    'events.poster',
                    'events.date as start_date',  
                    'events.status as event_status',
                    'merch_wallets.id as wallet_id',
                    'merch_wallets.negative_balance',
                    'merch_wallets.withdraw_locked',
                    'eo.bank_name',       
                    'eo.account_name',    
                    'eo.account_number'   
                )
                ->orderByDesc('events.id')
                ->get();

            $result = [];

            foreach ($events as $event) {
                // Buat dompet otomatis jika belum ada
                if (is_null($event->wallet_id)) {
                    $insertedId = DB::table('merch_wallets')->insertGetId([
                        'eo_id'             => $eoId,
                        'event_id'          => $event->event_id,
                        'available_balance' => 0, 
                        'held_balance'      => 0,
                        'negative_balance'  => 0,
                        'withdraw_locked'   => 0,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                    
                    $event->wallet_id = $insertedId;
                    $event->withdraw_locked = 0;
                    $event->negative_balance = 0;
                }

                // 1. Ambil omset penjualan riil (status 'paid') berdasarkan product_id milik event ini
                $paidTotal = DB::table('transaction_merch_details as tmd')
                    ->join('transaction_merch as tm', 'tmd.transaction_merch_id', '=', 'tm.id')
                    ->join('products as p', 'tmd.product_id', '=', 'p.id')
                    ->where('p.event_id', $event->event_id)
                    ->where('tm.payment_status', 'paid')
                    ->sum('tmd.subtotal') ?? 0;

                // 1b. 🛠️ FIX SALDO RIL: potong refund yang SUDAH selesai (status 'refunded')
                // dari omset merch, disamakan dengan EOTicketController & dengan
                // EODashboardController::getRealRevenue() yang memperlakukan wallet
                // sebagai sumber kebenaran saldo (sudah dipotong refund & WD).
                $refundedTotal = DB::table('refunds')
                    ->where('status', 'refunded')
                    ->whereIn('transaction_merch_id', function ($q) use ($event) {
                        $q->select('transaction_merch_details.transaction_merch_id')
                            ->from('transaction_merch_details')
                            ->join('products', 'products.id', '=', 'transaction_merch_details.product_id')
                            ->where('products.event_id', $event->event_id);
                    })
                    ->sum('grand_total_refunded') ?? 0;

                // 🛠️ FIX BUG: SALDO MINUS TIDAK PERNAH MUNCUL (disamakan dengan
                // EOTicketController::eventWallets()). Selisih minus dari
                // (paidTotal - refundedTotal) ditangkap ke $negativeBalance & disimpan
                // ke DB, bukan dibuang lewat max(0, ...) seperti sebelumnya.
                $negativeBalance = 0;

                $netAfterRefund = $paidTotal - $refundedTotal;
                if ($netAfterRefund < 0) {
                    $negativeBalance += abs($netAfterRefund);
                }
                $paidTotal = max(0, $netAfterRefund);

                // 2. Total penarikan yang SUDAH APPROVED SAJA.
                //    (Penarikan berstatus 'pending' TIDAK memotong saldo yang
                //    ditampilkan — saldo baru berkurang setelah owner approve.
                //    Proteksi dobel-pengajuan tetap ada lewat pengecekan
                //    $hasPendingWithdrawal di requestMerchWithdraw().)
                $alreadyWithdrawn = DB::table('merch_withdrawals')
                    ->where('event_id', $event->event_id)
                    ->where('status', 'approved')
                    ->sum('amount') ?? 0;

                // Deficit tambahan: dana yang sudah/sedang ditarik ternyata melebihi sisa
                // omset merch bersih event ini (WD disetujui duluan, refund besar belakangan).
                if (($paidTotal - $alreadyWithdrawn) < 0) {
                    $negativeBalance += abs($paidTotal - $alreadyWithdrawn);
                }

                // 3. Deteksi potensi nilai omset berdasarkan stok awal & harga merch
                $potentialRevenue = DB::table('products_ukuran')
                    ->where('event_id', $event->event_id)
                    ->select(DB::raw('SUM(stok * harga) as total_potential'))
                    ->value('total_potential') ?? 0;

                $isSkalaBesar = $potentialRevenue >= 25000000; 
                $minBalanceRequired = $isSkalaBesar ? 500000 : 100000; 
                $minHeldBalance = $isSkalaBesar ? 250000 : 50000;       

                // 4. Hitung masa plafon waktu berjalan (Menghapus Aturan End Date)
                $isHMinus10 = false;
                
                if (!is_null($event->start_date)) {
                    $startDate = Carbon::parse($event->start_date);
                    // H-10 atau setelahnya sebelum event dimulai (atau saat hari H berjalan)
                    $isHMinus10 = now()->diffInDays($startDate, false) <= 10;
                }

                // 4b. 🛠️ FIX BUG #1: Deteksi status "Event Selesai" agar plafon otomatis
                // terbuka penuh 100% dan gerbang syarat omset minimum/saldo mengendap
                // (yang tadinya hanya dibypass saat H-10) ikut di-bypass juga saat event
                // sudah benar-benar berakhir. Tanggal akhir riil diambil dari jadwal
                // terakhir (jika ada), jika tidak ada jadwal maka pakai events.date.
                $maxJadwalDate = DB::table('jadwal')->where('event_id', $event->event_id)->max('tanggal');
                $realEndDate = $maxJadwalDate
                    ? Carbon::parse($maxJadwalDate)
                    : (!is_null($event->start_date) ? Carbon::parse($event->start_date) : null);
                $isEventFinished = $realEndDate ? now()->greaterThan($realEndDate) : false;

                // Jika event sudah SELESAI -> plafon 100%. Jika belum tapi sudah H-10 -> 70%.
                // Selain itu default 50%.
                if ($isEventFinished) {
                    $plafonPercent = 1.0;
                } elseif ($isHMinus10) {
                    $plafonPercent = 0.7; 
                } else {
                    $plafonPercent = 0.5; 
                }

                // 5. Rumus Finansial Berjalan
                $maxEligibleBalance = floor($paidTotal * $plafonPercent);
                
                $calculatedAvailable = $maxEligibleBalance - $alreadyWithdrawn;
                if ($calculatedAvailable < 0) $calculatedAvailable = 0;

                $sisaKasSistem = $paidTotal - $alreadyWithdrawn;
                $heldBalance = $sisaKasSistem - $calculatedAvailable;
                if ($heldBalance < 0) $heldBalance = 0;

                // 6. Gerbang Logika Validasi Tombol Tarik
                $canWithdraw = true;
                $systemReason = 'Silakan masukkan nominal pengajuan Anda.';

                if ($event->withdraw_locked == 1) {
                    $canWithdraw = false;
                    $calculatedAvailable = 0;
                    $heldBalance = $paidTotal - $alreadyWithdrawn;
                    $systemReason = 'Fitur penarikan dana dinonaktifkan sementara oleh admin.';
                } 
                // Auto terbuka jika sudah H-10 ATAU event sudah selesai, walau nominal omset belum tercapai
                elseif ($paidTotal < $minBalanceRequired && !$isHMinus10 && !$isEventFinished) {
                    $canWithdraw = false;
                    $calculatedAvailable = 0;
                    $heldBalance = $paidTotal - $alreadyWithdrawn;
                    $systemReason = 'Total omset belum mencapai batas minimal ' . $this->formatRupiah($minBalanceRequired);
                } 
                // Auto terbuka jika sudah H-10 ATAU event sudah selesai, walau sisa saldo berjalan di bawah target mengendap
                elseif (($paidTotal - $alreadyWithdrawn) < $minHeldBalance && !$isHMinus10 && !$isEventFinished) {
                    $canWithdraw = false;
                    $calculatedAvailable = 0;
                    $heldBalance = $paidTotal - $alreadyWithdrawn;
                    $systemReason = 'Sisa saldo berjalan di bawah target mengendap ' . $this->formatRupiah($minHeldBalance);
                } 
                elseif ($calculatedAvailable <= 0) {
                    $canWithdraw = false;
                    $calculatedAvailable = 0;
                    $systemReason = $isEventFinished
                        ? 'Seluruh saldo kas event ini sudah habis dicairkan.'
                        : 'Kuota limit penarikan termin berjalan Anda saat ini sudah habis.';
                }

                // Update data dompet merch agar sinkron di database
                DB::table('merch_wallets')
                    ->where('id', $event->wallet_id)
                    ->update([
                        'available_balance' => (int) $calculatedAvailable,
                        'held_balance'      => (int) $heldBalance,
                        'negative_balance'  => (int) $negativeBalance,
                        'updated_at'        => now()
                    ]);

                $result[] = [
                    'event_id'          => (int) $event->event_id,
                    'event_name'        => $event->title,
                    'poster'            => $event->poster,
                    'start_date'        => $event->start_date,
                    'status'            => $event->event_status,
                    'is_h_minus_10'     => $isHMinus10,
                    'is_event_finished' => $isEventFinished,
                    'skala_event'       => $isSkalaBesar ? 'Besar (Potensi Capai ' . $this->formatRupiah($potentialRevenue) . ')' : 'Kecil (Potensi ' . $this->formatRupiah($potentialRevenue) . ')',
                    'total_sales'       => (int) $paidTotal,
                    'total_refunded'    => (int) $refundedTotal,
                    'already_withdrawn' => (int) $alreadyWithdrawn,
                    'available_balance' => (int) $calculatedAvailable, 
                    'held_balance_ui'   => (int) $heldBalance, 
                    'held_balance'      => (int) $heldBalance, 
                    'negative_balance'  => (int) $negativeBalance,
                    'withdraw_locked'   => (int) $event->withdraw_locked,
                    'can_withdraw'      => $canWithdraw,
                    'system_reason'     => $systemReason,
                    'min_balance_required' => (int) $minBalanceRequired,
                    'max_amount_allowed'   => (int) $calculatedAvailable,
                    'bank_name'         => $event->bank_name ?? '-',
                    'account_name'      => $event->account_name ?? '-',
                    'account_number'    => $event->account_number ?? '-',
                ];
            }

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses wallet data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. ACTION REQUEST PENARIKAN DANA MERCHANDISE PER EVENT
     */
    /**
     * 3. ACTION REQUEST PENARIKAN DANA MERCHANDISE PER EVENT (PERBAIKAN)
     */
    public function requestMerchWithdraw(Request $request)
    {
        $request->validate([
            'eo_id'    => 'required|integer',
            'event_id' => 'required|integer',
            'amount'   => 'required|integer|min:100000', 
            'note'     => 'nullable|string',
            'invoice'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', 
        ]);

        DB::beginTransaction();
        try {
            // Lock langsung ke tabel dompet untuk menghindari race condition saldo
            $wallet = DB::table('merch_wallets')
                ->join('events', 'merch_wallets.event_id', '=', 'events.id')
                ->join('eo', 'merch_wallets.eo_id', '=', 'eo.id')
                ->where('merch_wallets.eo_id', $request->eo_id)
                ->where('merch_wallets.event_id', $request->event_id)
                ->select(
                    'events.date as start_date', 
                    'merch_wallets.id as wallet_id',
                    'merch_wallets.withdraw_locked',
                    'eo.bank_name',       
                    'eo.account_name', 
                    'eo.account_number'
                ) 
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                return response()->json(['success' => false, 'message' => 'Data event, dompet, atau profil EO tidak ditemukan.'], 404);
            }

            // Proteksi status PENDING ganda
            $hasPendingWithdrawal = DB::table('merch_withdrawals')
                ->where('event_id', $request->event_id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingWithdrawal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal. Anda masih memiliki pengajuan penarikan merchandise yang berstatus PENDING pada event ini.'
                ], 400);
            }

            if (is_null($wallet->bank_name) || is_null($wallet->account_number)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal. Anda belum melengkapi data rekening bank di profil EO Anda.'
                ], 400);
            }

            if ($wallet->withdraw_locked == 1) {
                return response()->json(['success' => false, 'message' => 'Penarikan untuk event ini sedang dikunci.'], 403);
            }

            $paidTotal = DB::table('transaction_merch_details as tmd')
                ->join('transaction_merch as tm', 'tmd.transaction_merch_id', '=', 'tm.id')
                ->join('products as p', 'tmd.product_id', '=', 'p.id')
                ->where('p.event_id', $request->event_id)
                ->where('tm.payment_status', 'paid')
                ->sum('tmd.subtotal') ?? 0;

            // 🛠️ FIX SALDO RIL: disamakan dengan merchWallets() — potong refund yang
            // sudah selesai (status 'refunded') dari omset sebelum dipakai untuk
            // validasi & perhitungan plafon penarikan.
            $refundedTotal = DB::table('refunds')
                ->where('status', 'refunded')
                ->whereIn('transaction_merch_id', function ($q) use ($request) {
                    $q->select('transaction_merch_details.transaction_merch_id')
                        ->from('transaction_merch_details')
                        ->join('products', 'products.id', '=', 'transaction_merch_details.product_id')
                        ->where('products.event_id', $request->event_id);
                })
                ->sum('grand_total_refunded') ?? 0;

            $paidTotal = max(0, $paidTotal - $refundedTotal);

            // Menghitung penarikan sebelumnya — hanya yang SUDAH APPROVED.
            // Withdrawal 'pending' tidak ikut memotong plafon di sini karena
            // EO tidak mungkin sampai ke titik ini jika masih ada pengajuan
            // pending untuk event yang sama (lihat $hasPendingWithdrawal di atas).
            $alreadyWithdrawn = DB::table('merch_withdrawals')
                ->where('event_id', $request->event_id)
                ->where('status', 'approved')
                ->sum('amount') ?? 0;

            $potentialRevenue = DB::table('products_ukuran')
                ->where('event_id', $request->event_id)
                ->select(DB::raw('SUM(stok * harga) as total_potential'))
                ->value('total_potential') ?? 0;

            $isSkalaBesar = $potentialRevenue >= 25000000;
            $minBalanceRequired = $isSkalaBesar ? 500000 : 100000;
            $minHeldBalance = $isSkalaBesar ? 250000 : 50000;

            $isHMinus10 = false;
            if (!is_null($wallet->start_date)) {
                $startDate = Carbon::parse($wallet->start_date);
                $isHMinus10 = now()->diffInDays($startDate, false) <= 10;
            }

            // 🛠️ FIX BUG #1: sinkronkan deteksi "Event Selesai" dengan merchWallets(),
            // supaya gerbang validasi saat submit tidak lagi menolak pengajuan yang
            // sebenarnya sudah ditampilkan aktif (100%) di layar dashboard.
            $maxJadwalDate = DB::table('jadwal')->where('event_id', $request->event_id)->max('tanggal');
            $realEndDate = $maxJadwalDate
                ? Carbon::parse($maxJadwalDate)
                : (!is_null($wallet->start_date) ? Carbon::parse($wallet->start_date) : null);
            $isEventFinished = $realEndDate ? now()->greaterThan($realEndDate) : false;

            // Validasi omset minimum jika belum H-10 dan event belum selesai
            if ($paidTotal < $minBalanceRequired && !$isHMinus10 && !$isEventFinished) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal. Omset belum mencapai batas syarat minimum ' . $this->formatRupiah($minBalanceRequired)
                ], 400);
            }

            // Validasi saldo mengendap jika belum H-10 dan event belum selesai
            if (($paidTotal - ($alreadyWithdrawn + $request->amount)) < $minHeldBalance && !$isHMinus10 && !$isEventFinished) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal. Penarikan ini melanggar batas saldo mengendap wajib sistem senilai ' . $this->formatRupiah($minHeldBalance)
                ], 400);
            }

            if ($isEventFinished) {
                $plafonPercent = 1.0;
            } elseif ($isHMinus10) {
                $plafonPercent = 0.7;
            } else {
                $plafonPercent = 0.5;
            }

            $maxEligibleBalance = floor($paidTotal * $plafonPercent);
            $calculatedAvailable = $maxEligibleBalance - $alreadyWithdrawn;

            if ($calculatedAvailable < $request->amount) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Nominal pengajuan melebihi ketentuan batas limit termin hak tarik Anda saat ini.'
                ], 400);
            }

            $invoicePath = null;
            if ($request->hasFile('invoice')) {
                $file = $request->file('invoice');
                $filename = 'invoice_merch_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/invoices_merch', $filename);
                $invoicePath = 'invoices_merch/' . $filename;
            }

            $adminReviewNote = "[Sistem Log] Skala Omset Potensial: " . $this->formatRupiah($potentialRevenue) . " | Plafon: " . ($plafonPercent * 100) . "%";

            // 1. Masukkan data ke log penarikan (Status: pending)
            DB::table('merch_withdrawals')->insert([
                'eo_id'          => $request->eo_id,
                'event_id'       => $request->event_id,
                'amount'         => $request->amount,
                'note'           => $request->note ? $request->note . " (" . $adminReviewNote . ")" : $adminReviewNote,
                'status'         => 'pending', 
                'transfer_proof' => null,
                'invoice_file'   => $invoicePath, 
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // 2. Sinkronkan nilai sisa ke merch_wallets untuk dibaca real-time oleh dashboard
            $newAvailableBalance = (int) ($calculatedAvailable - $request->amount);
            if ($newAvailableBalance < 0) $newAvailableBalance = 0;

            $sisaKasSistem = $paidTotal - ($alreadyWithdrawn + $request->amount);
            $heldBalance = $sisaKasSistem - $newAvailableBalance;
            if ($heldBalance < 0) $heldBalance = 0;

            DB::table('merch_wallets')
                ->where('id', $wallet->wallet_id)
                ->update([
                    'available_balance' => $newAvailableBalance,
                    'held_balance'      => $heldBalance,
                    'updated_at'        => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan penarikan dana & file invoice berhasil dikirim! Menunggu tinjauan manual owner.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 4. GET DAFTAR TRANSAKSI PENJUALAN MERCHANDISE (DENGAN FILTER EVENT)
     */
    public function getMerchSales(Request $request)
    {
        try {
            $query = DB::table('transaction_merch_details as tmd')
                ->leftJoin('transaction_merch as tm', 'tmd.transaction_merch_id', '=', 'tm.id')
                ->join('products as p', 'tmd.product_id', '=', 'p.id')
                ->join('events as e', 'p.event_id', '=', 'e.id')
                ->select(
                    'tmd.transaction_merch_id as transaction_id',
                    'tm.kode_unik as invoice_number',
                    'tm.payment_status',
                    'tm.created_at as transaction_date',
                    'e.title as event_title',
                    'tmd.buyer_name', 
                    DB::raw('SUM(tmd.subtotal) as total_amount')
                );

            // 🛠️ FILTER: Jika Flutter mengirim parameter eo_id atau event_id
            if ($request->has('eo_id') && !is_null($request->eo_id)) {
                $query->where('e.eo_id', $request->eo_id);
            }
            if ($request->has('event_id') && !is_null($request->event_id) && $request->event_id != 0) {
                $query->where('p.event_id', $request->event_id);
            }

            $sales = $query->groupBy(
                    'tmd.transaction_merch_id', 
                    'tm.kode_unik', 
                    'tm.payment_status', 
                    'tm.created_at', 
                    'e.title', 
                    'tmd.buyer_name'
                )
                ->orderByDesc('tmd.transaction_merch_id')
                ->get();

            $formattedSales = $sales->map(function ($item) {
                return [
                    'id' => $item->transaction_id,
                    'invoice_number' => $item->invoice_number ?? 'INV-DUMMY', 
                    'event_title' => $item->event_title,
                    'buyer_name' => $item->buyer_name ?? 'Pembeli',
                    'amount' => (int) $item->total_amount,
                    'payment_status' => $item->payment_status ?? 'paid', 
                    'payment_method' => 'Xendit Gateway',
                    'created_at' => $item->transaction_date ? Carbon::parse($item->transaction_date)->toIso8601String() : Carbon::now()->toIso8601String(),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedSales
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 5. GET DETAIL TRANSAKSI PENJUALAN MERCHANDISE
     */
    public function show(Request $request, $transactionId)
    {
        try {
            $transaction = DB::table('transaction_merch')
                ->where('id', $transactionId)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Detail transaksi penjualan merch tidak ditemukan.'
                ], 404);
            }

            $buyerName = DB::table('transaction_merch_details')
                ->where('transaction_merch_id', $transactionId)
                ->value('buyer_name') ?? 'Pembeli';

            $items = DB::table('transaction_merch_details as tmd')
                ->join('products as p', 'tmd.product_id', '=', 'p.id')
                ->leftJoin('products_ukuran as pu', 'tmd.ukuran_id', '=', 'pu.id')
                ->leftJoin('products_varian as pv', 'tmd.varian_id', '=', 'pv.id')
                ->where('tmd.transaction_merch_id', $transactionId)
                ->select([
                    'tmd.id as detail_id',
                    'p.name as product_name',
                    'pv.id as varian_id',
                    'pv.varian as varian_name',
                    'pu.ukuran as ukuran_name',
                    'tmd.quantity', 
                    'tmd.price', 
                    'tmd.subtotal'
                ])
                ->get();

            $formattedItems = $items->map(function ($item) {
                $imageUrl = null;
                if (!is_null($item->varian_id)) {
                    $imageUrl = DB::table('images')
                        ->where('product_varian_id', $item->varian_id)
                        ->orderBy('id', 'asc')
                        ->value('url');
                }

                return [
                    'product_name'  => $item->product_name ?? 'Produk Merch',
                    'varian_name'   => $item->varian_name ?? '-',
                    'ukuran_name'   => $item->ukuran_name ?? '-',
                    'quantity'      => (int) ($item->quantity ?? 1),
                    'price'         => (int) ($item->price ?? 0),
                    'subtotal'      => (int) ($item->subtotal ?? 0),
                    'product_image' => $this->formatImage($imageUrl)
                ];
            });

            $formattedTransaction = [
                'invoice_number' => $transaction->kode_unik ?? 'INV-DUMMY',
                'buyer_name'     => $buyerName,
                'email'          => $transaction->email ?? '-',
                'payment_method' => $transaction->payment_method ?? ($transaction->grand_total == 0 ? 'Free' : 'Xendit Gateway'),
                'payment_status' => $transaction->payment_status ?? 'paid',
                'created_at'     => $transaction->created_at ? Carbon::parse($transaction->created_at)->toIso8601String() : now()->toIso8601String(),
                // Semua nilai finansial di bawah diambil LANGSUNG dari kolom tabel `transaction_merch`
                // (sudah final saat checkout), TANPA dihitung ulang / tanpa logika persentase apa pun.
                'total_amount'    => (int) $transaction->total_amount,   // Harga total produk (porsi EO)
                'service_fee'     => (int) ($transaction->service_tax ?? 0), // Biaya layanan (porsi platform)
                'total_price'     => (int) $transaction->grand_total,    // Total yang dibayar customer
                'net_revenue_eo'  => (int) $transaction->total_amount,   // Pendapatan bersih EO = total_amount, apa adanya dari DB
            ];

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil memuat data detail.',
                'data'    => [
                    'transaction' => $formattedTransaction,
                    'items'       => $formattedItems
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memuat detail transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    private function formatRupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }

    private function formatImage($path)
    {
        if (!$path) return asset('images/no-image.png');
        if (str_starts_with($path, 'http')) return $path;
        
        $path = ltrim($path, '/');
        if (!str_contains($path, 'images/')) {
            $path = 'images/' . $path;
        }
        return asset($path);
    }
}
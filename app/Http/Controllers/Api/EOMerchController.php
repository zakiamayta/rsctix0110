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
                    'merch_withdrawals.amount',
                    'merch_withdrawals.note',
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
                    'amount' => (int) $item->amount,
                    'note' => $item->note ?? '',
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
                    'events.end_date',            
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

                // 2. Total penarikan yang SUKSES (approved)
                $alreadyWithdrawn = DB::table('merch_withdrawals')
                    ->where('event_id', $event->event_id)
                    ->where('status', 'approved') 
                    ->sum('amount') ?? 0;

                // 3. Deteksi potensi nilai omset berdasarkan stok awal & harga merch
                $potentialRevenue = DB::table('products_ukuran')
                    ->where('event_id', $event->event_id)
                    ->select(DB::raw('SUM(stok * harga) as total_potential'))
                    ->value('total_potential') ?? 0;

                $isSkalaBesar = $potentialRevenue >= 25000000; 
                $minBalanceRequired = $isSkalaBesar ? 500000 : 100000; 
                $minHeldBalance = $isSkalaBesar ? 250000 : 50000;       

                // 4. Hitung masa plafon waktu berjalan
                $isEventFinished = false;
                $isHMinus10 = false;
                
                if (!is_null($event->start_date)) {
                    $startDate = Carbon::parse($event->start_date);
                    $isHMinus10 = now()->diffInDays($startDate, false) <= 10 && now()->isBefore($startDate);
                }
                
                if (!is_null($event->end_date)) {
                    $isEventFinished = Carbon::parse($event->end_date)->isPast();
                }

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
                elseif ($paidTotal < $minBalanceRequired) {
                    $canWithdraw = false;
                    $calculatedAvailable = 0;
                    $heldBalance = $paidTotal - $alreadyWithdrawn;
                    $systemReason = 'Total omset belum mencapai batas minimal ' . $this->formatRupiah($minBalanceRequired);
                } 
                elseif (($paidTotal - $alreadyWithdrawn) < $minHeldBalance && !$isEventFinished) {
                    $canWithdraw = false;
                    $calculatedAvailable = 0;
                    $heldBalance = $paidTotal - $alreadyWithdrawn;
                    $systemReason = 'Sisa saldo berjalan di bawah target mengendap ' . $this->formatRupiah($minHeldBalance);
                } 
                elseif ($calculatedAvailable <= 0) {
                    $canWithdraw = false;
                    $calculatedAvailable = 0;
                    $systemReason = 'Kuota limit penarikan termin berjalan Anda saat ini sudah habis.';
                }

                // Update data dompet merch agar sinkron di database
                DB::table('merch_wallets')
                    ->where('id', $event->wallet_id)
                    ->update([
                        'available_balance' => (int) $calculatedAvailable,
                        'held_balance'      => (int) $heldBalance,
                        'updated_at'        => now()
                    ]);

                $result[] = [
                    'event_id'          => (int) $event->event_id,
                    'event_name'        => $event->title,
                    'poster'            => $event->poster,
                    'start_date'        => $event->start_date,
                    'end_date'          => $event->end_date,
                    'status'            => $event->event_status,
                    'is_event_finished' => $isEventFinished,
                    'is_h_minus_10'     => $isHMinus10,
                    'skala_event'       => $isSkalaBesar ? 'Besar (Potensi Capai ' . $this->formatRupiah($potentialRevenue) . ')' : 'Kecil (Potensi ' . $this->formatRupiah($potentialRevenue) . ')',
                    'total_sales'       => (int) $paidTotal,
                    'already_withdrawn' => (int) $alreadyWithdrawn,
                    'available_balance' => (int) $calculatedAvailable, 
                    'held_balance_ui'   => (int) $heldBalance, 
                    'held_balance'      => (int) $heldBalance, 
                    'negative_balance'  => (int) $event->negative_balance,
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
            $event = DB::table('events')
                ->join('merch_wallets', 'events.id', '=', 'merch_wallets.event_id')
                ->join('eo', 'events.eo_id', '=', 'eo.id') 
                ->where('events.eo_id', $request->eo_id)
                ->where('events.id', $request->event_id)
                ->select(
                    'events.date as start_date', 
                    'events.end_date', 
                    'merch_wallets.id as wallet_id',
                    'merch_wallets.withdraw_locked',
                    'eo.bank_name',       
                    'eo.account_name', 
                    'eo.account_number'
                ) 
                ->lockForUpdate()
                ->first();

            if (!$event) {
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

            if (is_null($event->bank_name) || is_null($event->account_number)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal. Anda belum melengkapi data rekening bank di profil EO Anda.'
                ], 400);
            }

            if ($event->withdraw_locked == 1) {
                return response()->json(['success' => false, 'message' => 'Penarikan untuk event ini sedang dikunci.'], 403);
            }

            $paidTotal = DB::table('transaction_merch_details as tmd')
                ->join('transaction_merch as tm', 'tmd.transaction_merch_id', '=', 'tm.id')
                ->join('products as p', 'tmd.product_id', '=', 'p.id')
                ->where('p.event_id', $request->event_id)
                ->where('tm.payment_status', 'paid')
                ->sum('tmd.subtotal') ?? 0;

            $alreadyWithdrawn = DB::table('merch_withdrawals')
                ->where('event_id', $request->event_id)
                ->whereIn('status', ['approved', 'pending']) 
                ->sum('amount') ?? 0;

            $potentialRevenue = DB::table('products_ukuran')
                ->where('event_id', $request->event_id)
                ->select(DB::raw('SUM(stok * harga) as total_potential'))
                ->value('total_potential') ?? 0;

            $isSkalaBesar = $potentialRevenue >= 25000000;
            $minBalanceRequired = $isSkalaBesar ? 500000 : 100000;
            $minHeldBalance = $isSkalaBesar ? 250000 : 50000;

            if ($paidTotal < $minBalanceRequired) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal. Omset belum mencapai batas syarat minimum ' . $this->formatRupiah($minBalanceRequired)
                ], 400);
            }

            if (($paidTotal - ($alreadyWithdrawn + $request->amount)) < $minHeldBalance && !Carbon::parse($event->end_date)->isPast()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal. Penarikan ini melanggar batas saldo mengendap wajib sistem senilai ' . $this->formatRupiah($minHeldBalance)
                ], 400);
            }

            $isEventFinished = false;
            $isHMinus10 = false;

            if (!is_null($event->start_date)) {
                $startDate = Carbon::parse($event->start_date);
                $isHMinus10 = now()->diffInDays($startDate, false) <= 10 && now()->isBefore($startDate);
            }

            if (!is_null($event->end_date)) {
                $isEventFinished = Carbon::parse($event->end_date)->isPast();
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

            $newAvailableBalance = (int) ($calculatedAvailable - $request->amount);
            if($newAvailableBalance < 0) $newAvailableBalance = 0;

            DB::table('merch_wallets')
                ->where('id', $event->wallet_id)
                ->update([
                    'available_balance' => $newAvailableBalance,
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
     * 4. GET DAFTAR TRANSAKSI PENJUALAN MERCHANDISE (FIXED RELASI & KOLOM)
     */
    
    public function getMerchSales(Request $request)
    {
        try {
            $eoId = auth()->user()->id;

            // Menggunakan leftJoin agar data detail tetap muncul walaupun induknya kosong
            $sales = DB::table('transaction_merch_details as tmd')
                ->leftJoin('transaction_merch as tm', 'tmd.transaction_merch_id', '=', 'tm.id')
                ->join('products as p', 'tmd.product_id', '=', 'p.id')
                ->join('events as e', 'p.event_id', '=', 'e.id')
                // ->where('e.eo_id', $eoId) // Buka tanda komen ini jika nanti eo_id di DB sudah tepat
                ->select(
                    'tmd.transaction_merch_id as transaction_id', // Ambil ID langsung dari detail demi keamanan testing
                    'tm.kode_unik as invoice_number',
                    'tm.payment_status',
                    'tm.created_at as transaction_date',
                    'e.title as event_title',
                    'tmd.buyer_name', 
                    DB::raw('SUM(tmd.subtotal) as total_amount')
                )
                ->groupBy(
                    'tmd.transaction_merch_id', 
                    'tm.kode_unik', 
                    'tm.payment_status', 
                    'tm.created_at', 
                    'e.title', 
                    'tmd.buyer_name'
                )
                ->get();

            $formattedSales = $sales->map(function ($item) {
                return [
                    'id' => $item->transaction_id,
                    'invoice_number' => $item->invoice_number ?? 'INV-DUMMY', // Fallback jika tm.kode_unik null
                    'event_title' => $item->event_title,
                    'buyer_name' => $item->buyer_name ?? 'Pembeli',
                    'amount' => (int) $item->total_amount,
                    'payment_status' => $item->payment_status ?? 'paid', // Fallback jika tm.payment_status null
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
    
    private function formatRupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
}
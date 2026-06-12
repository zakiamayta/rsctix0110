<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EOTicketController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'eo_id' => 'required|integer',
                'event_id' => 'nullable|integer', 
            ]);

            // 1. Tambahkan LEFT JOIN ke tabel transactions berdasarkan event_id atau foreign key yang sesuai
            $query = DB::table('withdrawals')
                ->join('events', 'withdrawals.event_id', '=', 'events.id')
                ->join('eo', 'withdrawals.eo_id', '=', 'eo.id')
                // Ambil transaksi pertama yang paid untuk mengambil data kode_unik dari event tersebut
                ->leftJoin('transactions', function($join) {
                    $join->on('withdrawals.event_id', '=', 'transactions.event_id')
                        ->where('transactions.payment_status', '=', 'paid');
                })
                ->where('withdrawals.eo_id', $request->eo_id)
                ->select(
                    'withdrawals.*', 
                    'events.title as event_name',
                    'eo.bank_name',
                    'eo.account_number',
                    'eo.account_name',
                    'transactions.kode_unik as trx_kode_unik' // <-- Ambil kode_unik database di sini
                )
                // Grouping agar data withdrawal tidak duplikat jika transaksi paid ada banyak
                ->groupBy('withdrawals.id', 'events.title', 'eo.bank_name', 'eo.account_number', 'eo.account_name', 'transactions.kode_unik');

            if ($request->has('event_id') && !is_null($request->event_id)) {
                $query->where('withdrawals.event_id', $request->event_id);
            }

            $history = $query->orderByDesc('withdrawals.id')->get();

            $formattedHistory = $history->map(function ($item) {
                return [
                    'id' => $item->id,
                    'amount' => (int) $item->amount,
                    'note' => $item->note ?? '',
                    'status' => $item->status ?? 'pending',
                    'transfer_proof' => $item->transfer_proof,
                    'event_name' => $item->event_name ?? 'Event Tidak Diketahui',
                    
                    // ========================================================
                    // SEKARANG KODE_UNIK SUDAH DI-MAPPING DAN DIKIRIM KE FLUTTER
                    // ========================================================
                    'kode_unik' => $item->trx_kode_unik ?? '-', 
                    
                    'reference_number' => $item->reference_number ?? '-',
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

    public function eventWallets($eoId)
    {
        try {
            $events = DB::table('events')
                ->leftJoin('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                ->join('eo', 'events.eo_id', '=', 'eo.id') 
                ->where('events.eo_id', $eoId)
                ->select(
                    'events.id as event_id',
                    'events.title',
                    'events.poster',
                    'events.date as start_date',  
                    'events.end_date',            
                    'events.status as event_status',
                    'event_wallets.id as wallet_id',
                    'event_wallets.negative_balance',
                    'event_wallets.withdraw_locked',
                    'eo.bank_name',       
                    'eo.account_name',    
                    'eo.account_number'   
                )
                ->orderByDesc('events.id')
                ->get();

            $result = [];

            foreach ($events as $event) {
                // Buat dompet otomatis jika belum ada di database
                if (is_null($event->wallet_id)) {
                    $insertedId = DB::table('event_wallets')->insertGetId([
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

                // 1. Ambil riwayat transaksi riil berstatus 'paid'
                $paidTotal = DB::table('transactions')
                    ->where('event_id', $event->event_id)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount') ?? 0;

                // 2. Total penarikan yang SUKSES
                $alreadyWithdrawn = DB::table('withdrawals')
                    ->where('event_id', $event->event_id)
                    ->whereIn('status', ['approved', 'pending']) 
                    ->sum('amount') ?? 0;

                // 3. Deteksi skala berdasarkan Potensi Nilai Omset
                try {
                    $potentialRevenue = DB::table('tickets')
                        ->where('event_id', $event->event_id)
                        ->select(DB::raw('SUM(stock * price) as total_potential_revenue'))
                        ->value('total_potential_revenue') ?? 0;
                } catch (\Exception $ticketEx) {
                    $potentialRevenue = 0; 
                }

                $isSkalaBesar = $potentialRevenue >= 50000000;
                $minBalanceRequired = $isSkalaBesar ? 1000000 : 200000; 
                $minHeldBalance = $isSkalaBesar ? 500000 : 100000;       

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

                // 5. Rumus Finansial
                $maxEligibleBalance = floor($paidTotal * $plafonPercent);
                
                $calculatedAvailable = $maxEligibleBalance - $alreadyWithdrawn;
                if ($calculatedAvailable < 0) $calculatedAvailable = 0;

                $sisaKasSistem = $paidTotal - $alreadyWithdrawn;
                $heldBalance = $sisaKasSistem - $calculatedAvailable;
                if ($heldBalance < 0) $heldBalance = 0;

                // 6. Validasi Tombol Gerbang Logika Mutlak
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

                // Update database
                DB::table('event_wallets')
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
     * ACTION PENARIKAN DANA PER EVENT (UPDATED WITH PROTEKSI PENDING)
     */
    public function requestWithdraw(Request $request)
    {
        // 1. Validasi input file invoice dan data ID dasar
        $request->validate([
            'eo_id'    => 'required|integer',
            'event_id' => 'required|integer',
            'amount'   => 'required|integer|min:100000', 
            'note'     => 'nullable|string',
            'invoice'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // Wajib file, maks 2MB
        ]);

        DB::beginTransaction();
        try {
            // 2. Query Gabungan: Ambil data Event, Dompet, DAN Data Bank dari tabel EO
            $event = DB::table('events')
                ->join('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                ->join('eo', 'events.eo_id', '=', 'eo.id') 
                ->where('events.eo_id', $request->eo_id)
                ->where('events.id', $request->event_id)
                ->select(
                    'events.date as start_date', 
                    'events.end_date', 
                    'event_wallets.*',
                    'eo.bank_name',       
                    'eo.account_name', 
                    'eo.account_number'
                ) 
                ->lockForUpdate()
                ->first();

            if (!$event) {
                return response()->json(['success' => false, 'message' => 'Data event, dompet, atau profil EO tidak ditemukan.'], 404);
            }

            // ================== PROTEKSI STATUS PENDING (BARU) ==================
            // Cek apakah masih ada transaksi withdrawal tiket yang menggantung ('pending') untuk event ini
            $hasPendingWithdrawal = DB::table('withdrawals')
                ->where('event_id', $request->event_id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingWithdrawal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal. Anda masih memiliki pengajuan penarikan dana TIKET yang berstatus PENDING pada event ini. Silakan tunggu hingga dicairkan oleh admin sebelum mengajukan kembali.'
                ], 400);
            }
            // ====================================================================

            // Cek kelengkapan data bank di profil EO sebelum melanjutkan request
            if (is_null($event->bank_name) || is_null($event->account_number)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal. Anda belum melengkapi data rekening bank di profil EO Anda.'
                ], 400);
            }

            if ($event->withdraw_locked == 1) {
                return response()->json(['success' => false, 'message' => 'Penarikan untuk event ini sedang dikunci.'], 403);
            }

            $paidTotal = DB::table('transactions')
                ->where('event_id', $request->event_id)
                ->where('payment_status', 'paid')
                ->sum('total_amount') ?? 0;

            $alreadyWithdrawn = DB::table('withdrawals')
                ->where('event_id', $request->event_id)
                ->where('status', 'approved') 
                ->sum('amount') ?? 0;

            try {
                $potentialRevenue = DB::table('tickets')
                    ->where('event_id', $request->event_id)
                    ->select(DB::raw('SUM(stock * price) as total_potential_revenue'))
                    ->value('total_potential_revenue') ?? 0;
            } catch (\Exception $ticketEx) {
                $potentialRevenue = 0;
            }

            $isSkalaBesar = $potentialRevenue >= 50000000;
            $minBalanceRequired = $isSkalaBesar ? 1000000 : 200000;
            $minHeldBalance = $isSkalaBesar ? 500000 : 100000;

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

            // Proses Upload File Invoice ke Storage
            $invoicePath = null;
            if ($request->hasFile('invoice')) {
                $file = $request->file('invoice');
                $filename = 'invoice_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/invoices', $filename);
                $invoicePath = 'invoices/' . $filename;
            }

            $adminReviewNote = "[Sistem Log] Skala Omset Potensial: " . $this->formatRupiah($potentialRevenue) . " | Plafon: " . ($plafonPercent * 100) . "%";

            // SIMPAN KE TABEL WITHDRAWALS (Tanpa duplikasi kolom bank karena ditarik via JOIN)
            DB::table('withdrawals')->insert([
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

            DB::table('event_wallets')
                ->where('event_id', $request->event_id)
                ->update([
                    'available_balance' => (int) ($calculatedAvailable - $request->amount),
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
    
    private function formatRupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
}
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

            // 1. BUAT SUBQUERY UNTUK MENGHITUNG TOTAL OMSET TRANSAKSI PER EVENT
            // Ini mencegah duplikasi data akibat One-to-Many Join
            $trxSub = DB::table('transactions')
                ->select('event_id', DB::raw('SUM(total_amount) as total_omset_paid'))
                ->where('payment_status', 'paid')
                ->groupBy('event_id');

            $query = DB::table('withdrawals')
                ->join('events', 'withdrawals.event_id', '=', 'events.id')
                ->join('eo', 'withdrawals.eo_id', '=', 'eo.id')
                // 2. LEFT JOIN MENGGUNAKAN SUBQUERY AGAR AMAN
                ->leftJoinSub($trxSub, 'trx_summary', function($join) {
                    $join->on('withdrawals.event_id', '=', 'trx_summary.event_id');
                })
                ->where('withdrawals.eo_id', $request->eo_id)
                ->select(
                    'withdrawals.id',
                    'withdrawals.eo_id',
                    'withdrawals.event_id',
                    'withdrawals.amount',
                    'withdrawals.note',
                    'withdrawals.status',
                    'withdrawals.transfer_proof',
                    'withdrawals.invoice_file',
                    'withdrawals.reference_number',
                    'withdrawals.created_at',
                    'withdrawals.approved_at',
                    'withdrawals.paid_at',
                    'events.title as event_name',
                    'eo.bank_name',
                    'eo.account_number',
                    'eo.account_name',
                    // Ambil nilai total omset riil yang terjual
                    DB::raw('IFNULL(trx_summary.total_omset_paid, 0) as total_sales_real')
                );
                // KINI KITA TIDAK MEMBUTUHKAN GROUP BY YANG MERUSAK DATA LAGI

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
                    'invoice_file' => $item->invoice_file,
                    'event_name' => $item->event_name ?? 'Event Tidak Diketahui',
                    'total_sales_event' => (int) $item->total_sales_real, // Data penjualan sukses sekarang keluar di sini
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
            // Menggunakan Subquery khusus agar data baris 'events' tidak ter-duplikasi (ganda) akibat join tabel jadwal Many-to-One
            $jadwalSub = DB::table('jadwal')
                ->select('event_id', DB::raw('MAX(tanggal) as max_tanggal'))
                ->groupBy('event_id');

            $events = DB::table('events')
                ->leftJoin('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                ->join('eo', 'events.eo_id', '=', 'eo.id') 
                ->leftJoinSub($jadwalSub, 'j_max', function($join) {
                    $join->on('events.id', '=', 'j_max.event_id');
                })
                ->where('events.eo_id', $eoId)
                ->select(
                    'events.id as event_id',
                    'events.title',
                    'events.poster',
                    'events.date as start_date',  
                    'j_max.max_tanggal as end_date',            
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
                $actualEndDate = $event->end_date ?? $event->start_date;

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

                // 2. Total penarikan yang SUKSES maupun PENDING
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
                
                $minBalanceRequired = $isSkalaBesar ? 3000000 : 1000000; 
                $minHeldBalance = $isSkalaBesar ? 500000 : 100000;       

                // 4. Hitung masa waktu kalender (H-10 & Selesai)
                $isEventFinished = false;
                $isHMinus10 = false;
                
                if (!is_null($event->start_date)) {
                    $today = now()->startOfDay();
                    $startDate = Carbon::parse($event->start_date)->startOfDay();
                    $daysLeft = $today->diffInDays($startDate);
                    
                    $isHMinus10 = ($daysLeft <= 10) && $today->isBefore($startDate);
                }
                
                if (!is_null($actualEndDate)) {
                    $isEventFinished = Carbon::parse($actualEndDate)->isPast();
                }

                if ($isEventFinished) {
                    $plafonPercent = 1.0; 
                } else {
                    $plafonPercent = 0.5; 
                }

                $isBypassedByHMinus10 = false;
                if ($isHMinus10 && $paidTotal < $minBalanceRequired) {
                    $minBalanceRequired = 0; 
                    $isBypassedByHMinus10 = true;
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
                    $systemReason = 'Total omset belum mencapai batas minimal pembuka gerbang ' . $this->formatRupiah($minBalanceRequired);
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
                    if ($isBypassedByHMinus10) {
                        $systemReason = 'Gerbang H-10 terbuka otomatis! Namun saldo hak tarik Anda masih 0 karena limit plafon 50% sudah habis atau belum ada tiket laku baru.';
                    } else {
                        $systemReason = 'Kuota limit penarikan termin berjalan (Plafon 50%) Anda saat ini sudah diambil.';
                    }
                }

                // Update database
                DB::table('event_wallets')
                    ->where('id', $event->wallet_id)
                    ->update([
                        'available_balance' => (int) $calculatedAvailable,
                        'held_balance'      => (int) $heldBalance,
                        'updated_at'        => now()
                    ]);

                $statusBypassMsg = $isBypassedByHMinus10 ? ' (Bypass H-10 Aktif)' : '';

                $result[] = [
                    'event_id'          => (int) $event->event_id,
                    'event_name'        => $event->title,
                    'poster'            => $event->poster,
                    'start_date'        => $event->start_date,
                    'end_date'          => $actualEndDate, 
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
                    'system_reason'     => $systemReason . $statusBypassMsg,
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

    public function requestWithdraw(Request $request)
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
            // Subquery ter-isolasi untuk keamanan relasi 1-to-Many jadwal ke event
            $jadwalSub = DB::table('jadwal')
                ->select('event_id', DB::raw('MAX(tanggal) as max_tanggal'))
                ->groupBy('event_id');

            $event = DB::table('events')
                ->join('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                ->join('eo', 'events.eo_id', '=', 'eo.id') 
                ->leftJoinSub($jadwalSub, 'j_max', function($join) {
                    $join->on('events.id', '=', 'j_max.event_id');
                })
                ->where('events.eo_id', $request->eo_id)
                ->where('events.id', $request->event_id)
                ->select(
                    'events.date as start_date', 
                    'j_max.max_tanggal as end_date',
                    'event_wallets.id as wallet_id',
                    'event_wallets.withdraw_locked',
                    'eo.bank_name',       
                    'eo.account_name', 
                    'eo.account_number'
                ) 
                ->lockForUpdate()
                ->first();

            if (!$event) {
                return response()->json(['success' => false, 'message' => 'Data event, dompet, atau profil EO tidak ditemukan.'], 404);
            }

            $actualEndDate = $event->end_date ?? $event->start_date;

            $hasPendingWithdrawal = DB::table('withdrawals')
                ->where('event_id', $request->event_id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingWithdrawal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal. Anda masih memiliki pengajuan penarikan dana TIKET yang berstatus PENDING pada event ini.'
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

            $paidTotal = DB::table('transactions')
                ->where('event_id', $request->event_id)
                ->where('payment_status', 'paid')
                ->sum('total_amount') ?? 0;

            $alreadyWithdrawn = DB::table('withdrawals')
                ->where('event_id', $request->event_id)
                ->whereIn('status', ['approved', 'pending']) 
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
            
            $minBalanceRequired = $isSkalaBesar ? 3000000 : 1000000;
            $minHeldBalance = $isSkalaBesar ? 500000 : 100000;

            $isEventFinished = false;
            $isHMinus10 = false;

            if (!is_null($event->start_date)) {
                $today = now()->startOfDay();
                $startDate = Carbon::parse($event->start_date)->startOfDay();
                $daysLeft = $today->diffInDays($startDate);

                $isHMinus10 = ($daysLeft <= 10) && $today->isBefore($startDate);
            }

            if (!is_null($actualEndDate)) {
                $isEventFinished = Carbon::parse($actualEndDate)->isPast();
            }

            if ($isEventFinished) {
                $plafonPercent = 1.0;
            } else {
                $plafonPercent = 0.5;
            }

            if ($isHMinus10 && $paidTotal < $minBalanceRequired) {
                $minBalanceRequired = 0;
            }

            if ($paidTotal < $minBalanceRequired) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal. Omset belum mencapai batas syarat minimum pembuka gerbang ' . $this->formatRupiah($minBalanceRequired)
                ], 400);
            }

            if (($paidTotal - ($alreadyWithdrawn + $request->amount)) < $minHeldBalance && !$isEventFinished) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal. Penarikan ini melanggar batas saldo mengendang wajib sistem senilai ' . $this->formatRupiah($minHeldBalance)
                ], 400);
            }

            $maxEligibleBalance = floor($paidTotal * $plafonPercent);
            $calculatedAvailable = $maxEligibleBalance - $alreadyWithdrawn;

            if ($calculatedAvailable < $request->amount) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Nominal pengajuan melebihi ketentuan batas limit termin hak tarik Anda (Plafon ' . ($plafonPercent * 100) . '%).'
                ], 400);
            }

            $invoicePath = null;
            if ($request->hasFile('invoice')) {
                $file = $request->file('invoice');
                $filename = 'invoice_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/invoices', $filename);
                $invoicePath = 'invoices/' . $filename;
            }

            $adminReviewNote = "[Sistem Log] Skala Omset Potensial: " . $this->formatRupiah($potentialRevenue) . " | Plafon: " . ($plafonPercent * 100) . "%" . ($isHMinus10 ? " | Darurat H-10 Terbuka" : "");

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

            // Pembaruan sisa saldo setelah ditarik
            $finalAvailable = (int) ($calculatedAvailable - $request->amount);
            $finalHeld = (int) (($paidTotal - ($alreadyWithdrawn + $request->amount)) - $finalAvailable);
            if ($finalHeld < 0) $finalHeld = 0;

            DB::table('event_wallets')
                ->where('id', $event->wallet_id)
                ->update([
                    'available_balance' => $finalAvailable,
                    'held_balance'      => $finalHeld,
                    'updated_at'        => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan penarikan dana berhasil dikirim! Menunggu tinjauan manual.',
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
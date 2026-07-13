<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\TicketWalletService;

class TicketWithdrawalController extends Controller
{
    /**
     * Helper privat untuk mengambil ID Event Organizer milik user yang login secara aman.
     */
    private function getEoId()
    {
        return DB::table('eo')
            ->where('user_id', Auth::id())
            ->value('id');
    }

    /**
     * Menampilkan halaman riwayat penarikan dana & ringkasan dompet event.
     */
    public function index(Request $request)
    {
        $eoId = $this->getEoId();
        
        if (!$eoId) {
            return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
        }

        // 1. AMBIL DATA DOMPET EVENT
        $events = DB::table('events')
            ->leftJoin('event_wallets', 'events.id', '=', 'event_wallets.event_id')
            ->join('eo', 'events.eo_id', '=', 'eo.id') 
            ->leftJoin('jadwal', 'events.id', '=', 'jadwal.event_id')
            ->where('events.eo_id', $eoId)
            ->select(
                'events.id as event_id',
                'events.title',
                'events.poster',
                'events.date as start_date',  
                DB::raw('MAX(jadwal.tanggal) as end_date'),            
                'events.status as event_status',
                'event_wallets.id as wallet_id',
                'event_wallets.negative_balance',
                'event_wallets.withdraw_locked',
                'eo.bank_name',       
                'eo.account_name',    
                'eo.account_number'   
            )
            ->groupBy(
                'events.id', 
                'events.title', 
                'events.poster', 
                'events.date', 
                'events.status', 
                'event_wallets.id', 
                'event_wallets.negative_balance', 
                'event_wallets.withdraw_locked', 
                'eo.bank_name', 
                'eo.account_name', 
                'eo.account_number'
            )
            ->orderByDesc('events.id')
            ->get();

        $wallets = []; 
        $totalGlobalAvailable = 0;
        $totalGlobalHeld = 0;
        $totalGlobalSales = 0;
        $totalGlobalNegative = 0;

        $bank_name = count($events) > 0 ? ($events[0]->bank_name ?? '-') : '-';
        $account_number = count($events) > 0 ? ($events[0]->account_number ?? '-') : '-';
        $account_name = count($events) > 0 ? ($events[0]->account_name ?? '-') : '-';

foreach ($events as $event) {
    $calc = TicketWalletService::recalculate($event->event_id);

    $totalGlobalAvailable += ($calc['negative_balance'] > 0) ? 0 : $calc['available_balance'];
    $totalGlobalHeld      += $calc['held_balance'];
    $totalGlobalSales     += $calc['total_sales'];
    $totalGlobalNegative  += $calc['negative_balance'];

    $wallets[] = [
        'event_id'          => $event->event_id,
        'event_name'        => $event->title,
        'poster'            => $event->poster,
        'start_date'        => $event->start_date,
        'end_date'          => $event->end_date ?? $event->start_date,
        'status'            => $event->event_status,
        'is_event_finished' => $calc['is_event_finished'],
        'is_h_minus_10'     => $calc['is_h_minus_10'],
        'withdraw_locked'   => $calc['withdraw_locked'],
        'negative_balance'  => $calc['negative_balance'],
        'skala_event'       => $calc['skala_event'],
        'total_sales'       => $calc['total_sales'],
        'already_withdrawn' => $calc['already_withdrawn'],
        'available_balance' => $calc['available_balance'],
        'held_balance'      => $calc['held_balance'],
        'can_withdraw'      => $calc['can_withdraw'],
        'system_reason'     => $calc['system_reason'],
        'bank_name'         => $event->bank_name ?? '-',
        'account_name'      => $event->account_name ?? '-',
        'account_number'    => $event->account_number ?? '-',
    ];
}

        // 2. AMBIL RIWAYAT TRANSAKSI WITHDRAWAL
        $historyQuery = DB::table('withdrawals')
            ->join('events', 'withdrawals.event_id', '=', 'events.id')
            ->join('eo', 'withdrawals.eo_id', '=', 'eo.id')
            ->leftJoin('transactions', function($join) {
                $join->on('withdrawals.event_id', '=', 'transactions.event_id')
                    ->where('transactions.payment_status', '=', 'paid');
            })
            ->where('withdrawals.eo_id', $eoId)
            ->select(
                'withdrawals.*', 
                'events.title as event_name',
                'eo.bank_name',
                'eo.account_number',
                'eo.account_name',
                'transactions.kode_unik as trx_kode_unik'
            )
            ->groupBy(
                'withdrawals.id', 
                'withdrawals.eo_id',
                'withdrawals.event_id',
                'withdrawals.amount',
                'withdrawals.note',
                'withdrawals.status',
                'withdrawals.transfer_proof',
                'withdrawals.invoice_file',
                'withdrawals.created_at',
                'withdrawals.updated_at',
                'events.title', 
                'eo.bank_name', 
                'eo.account_number', 
                'eo.account_name', 
                'transactions.kode_unik'
            );

        if ($request->has('event_id') && !is_null($request->event_id)) {
            $historyQuery->where('withdrawals.event_id', $request->event_id);
        }

        $history = $historyQuery->orderByDesc('withdrawals.id')->get();

        return view('eo.wallet.ticket.dashboard', compact(
            'wallets', 
            'history', 
            'totalGlobalAvailable', 
            'totalGlobalHeld', 
            'totalGlobalSales', 
            'totalGlobalNegative',
            'bank_name',
            'account_number',
            'account_name'
        ));
    }

    /**
     * Menampilkan halaman formulir pengajuan withdraw berdasarkan ID Event (GET).
     */
    public function create($eventId)
    {
        $eoId = $this->getEoId();
        
        if (!$eoId) {
            return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
        }

        $event = DB::table('events')
            ->join('event_wallets', 'events.id', '=', 'event_wallets.event_id')
            ->where('events.eo_id', $eoId)
            ->where('events.id', $eventId)
            ->select(
                'events.id as event_id',
                'events.title as event_name',
                'events.status as event_status',
                'event_wallets.available_balance',
                'event_wallets.withdraw_locked',
                'event_wallets.negative_balance'
            )
            ->first();

        if (!$event) {
            return redirect()->route('eo.ticket-wallet.dashboard')->with('error', 'Data Event atau Wallet tidak ditemukan.');
        }

        if ($event->event_status === 'cancelled') {
            return redirect()->back()->with('error', 'Gagal. Penarikan dana dibekukan karena status event ini adalah CANCELLED.');
        }
        if ($event->withdraw_locked == 1) {
            return redirect()->back()->with('error', 'Gagal. Fitur penarikan dana untuk event ini dikunci oleh admin.');
        }
        if ($event->negative_balance > 0) {
            return redirect()->back()->with('error', 'Gagal. Penarikan dibekukan karena event memiliki saldo refund minus.');
        }
        if ($event->available_balance <= 0) {
            return redirect()->back()->with('error', 'Gagal. Saldo hak tarik Anda saat ini kosong atau limit plafon sudah habis.');
        }

        $wallet = [
            'event_id'          => $event->event_id,
            'event_name'        => $event->event_name,
            'available_balance' => $event->available_balance
        ];

        return view('eo.wallet.ticket.withdraw', compact('wallet'));
    }

    /**
     * Memproses form submit pengajuan request withdraw (POST).
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'required|integer',
            'amount'   => 'required|integer|min:100000', 
            'note'     => 'nullable|string',
            'invoice'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', 
        ]);

        $eoId = $this->getEoId();

        if (!$eoId) {
            return redirect()->back()->with('error', 'Aksi ditolak. Profil EO tidak ditemukan.');
        }

        DB::beginTransaction();
        try {
            $event = DB::table('events')
                ->join('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                ->join('eo', 'events.eo_id', '=', 'eo.id') 
                ->leftJoin('jadwal', 'events.id', '=', 'jadwal.event_id')
                ->where('events.eo_id', $eoId)
                ->where('events.id', $request->event_id)
                ->select(
                    'events.date as start_date', 
                    'events.status as event_status',
                    DB::raw('MAX(jadwal.tanggal) as end_date'),
                    'event_wallets.id as wallet_id',
                    'event_wallets.withdraw_locked',
                    'event_wallets.negative_balance',
                    'eo.bank_name',       
                    'eo.account_name', 
                    'eo.account_number'
                ) 
                ->groupBy(
                    'events.date', 
                    'events.status',
                    'event_wallets.id', 
                    'event_wallets.withdraw_locked', 
                    'event_wallets.negative_balance', 
                    'eo.bank_name', 
                    'eo.account_name', 
                    'eo.account_number'
                )
                ->lockForUpdate()
                ->first();

            if (!$event) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Data Wallet Event tidak ditemukan.');
            }

            if ($event->event_status === 'cancelled') {
                DB::rollBack();
                return redirect()->back()->with('error', 'Gagal memproses. Seluruh sisa dana event telah dibatalkan dikunci di sistem.');
            }

            $actualEndDate = $event->end_date ?? $event->start_date;

            $hasPendingWithdrawal = DB::table('withdrawals')
                ->where('event_id', $request->event_id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingWithdrawal) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Gagal. Anda masih memiliki pengajuan dana berstatus PENDING.');
            }

            if (is_null($event->bank_name) || is_null($event->account_number)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Gagal. Lengkapi data rekening bank di profil EO Anda terlebih dahulu.');
            }

            if ($event->withdraw_locked == 1) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Penarikan untuk event ini sedang dikunci oleh admin.');
            }

            $paidTotal = DB::table('transactions')
                ->where('event_id', $request->event_id)
                ->where('payment_status', 'paid')
                ->sum('total_amount') ?? 0;

            $alreadyWithdrawn = DB::table('withdrawals')
                ->where('event_id', $request->event_id)
                ->whereIn('status', ['approved', 'pending']) 
                ->sum('amount') ?? 0;

            $potentialRevenue = DB::table('tickets')
                ->where('event_id', $request->event_id)
                ->select(DB::raw('SUM(stock * price) as total_potential_revenue'))
                ->value('total_potential_revenue') ?? 0;

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

            $plafonPercent = $isEventFinished ? 1.0 : 0.5;

            if ($isHMinus10 && $paidTotal < $minBalanceRequired) {
                $minBalanceRequired = 0;
            }

            // FIX LOGIKA RE-VALIDASI LEVEL POST: Target omset minimum hanya memblokir jika event BELUM selesai
            if ($paidTotal < $minBalanceRequired && !$isEventFinished) {
                return redirect()->back()->with('error', 'Gagal. Omset belum mencapai batas syarat minimum ' . $this->formatRupiah($minBalanceRequired));
            }

            if (($paidTotal - ($alreadyWithdrawn + $request->amount)) < $minHeldBalance && !$isEventFinished) {
                return redirect()->back()->with('error', 'Gagal. Penarikan melanggar batas saldo mengendap wajib sistem senilai ' . $this->formatRupiah($minHeldBalance));
            }

            $maxEligibleBalance = floor($paidTotal * $plafonPercent);
            $calculatedAvailable = $maxEligibleBalance - $alreadyWithdrawn;

            if ($calculatedAvailable < $request->amount) {
                return redirect()->back()->with('error', 'Nominal pengajuan melebihi ketentuan batas limit plafon ' . ($plafonPercent * 100) . '%.');
            }

            $invoicePath = null;
            if ($request->hasFile('invoice')) {
                $file = $request->file('invoice');
                $filename = 'invoice_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/invoices_withdrawal'), $filename);// Path yang disimpan ke database (disesuaikan agar mudah dipanggil di view)
                $invoicePath = 'images/invoices_withdrawal/' . $filename;
            }

            $adminReviewNote = "Skala Omset: " . $this->formatRupiah($potentialRevenue) . " | Plafon: " . ($plafonPercent * 100) . "%" . ($isHMinus10 ? " | Darurat H-10 Terbuka" : "");

            DB::table('withdrawals')->insert([
                'eo_id'          => $eoId,
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
            return redirect()->route('eo.ticket-wallet.dashboard')->with('success', 'Pengajuan penarikan dana berhasil dikirim! Menunggu tinjauan manual admin.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan internal web: ' . $e->getMessage());
        }
    }
    
    private function formatRupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }

    public function soldTickets($eventId)
    {
        $eoId = $this->getEoId();

        try {
            $tickets = DB::table('ticket_attendees as ta')
                ->join('transactions as t', 't.id', '=', 'ta.transaction_id')
                ->join('tickets as tk', 'tk.id', '=', 'ta.ticket_id')
                ->where('t.event_id', $eventId)
                ->where('t.payment_status', 'paid')
                ->groupBy('tk.id', 'tk.name', 'tk.price')
                ->select(
                    'tk.name as nama_tiket',
                    'tk.price as harga_satuan',
                    DB::raw('COUNT(ta.id) as total_terjual'),
                    DB::raw('SUM(tk.price) as total_omset')
                )
                ->orderByDesc('total_terjual')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tickets
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data finansial tiket: ' . $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\MerchWalletService;

class MerchWithdrawalController extends Controller
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
     * 1. Menampilkan Halaman Dashboard Finansial Merch & Riwayat Penarikan (index)
     */
    public function index(Request $request)
    {
        $eoId = $this->getEoId();
        
        if (!$eoId) {
            return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
        }

        // Ambil data dompet merchandise per event
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

        $wallets = [];
        $totalGlobalAvailable = 0;
        $totalGlobalHeld = 0;
        $totalGlobalSales = 0;
        $totalGlobalNegative = 0;

        // Fallback info rekening bank dasar dari item pertama
        $bank_name = count($events) > 0 ? ($events[0]->bank_name ?? '-') : '-';
        $account_number = count($events) > 0 ? ($events[0]->account_number ?? '-') : '-';
        $account_name = count($events) > 0 ? ($events[0]->account_name ?? '-') : '-';

foreach ($events as $event) {
    $calc = MerchWalletService::recalculate($event->event_id);

    $totalGlobalAvailable += ($calc['negative_balance'] > 0) ? 0 : $calc['available_balance'];
    $totalGlobalHeld      += $calc['held_balance'];
    $totalGlobalSales     += $calc['total_sales'];
    $totalGlobalNegative  += $calc['negative_balance'];

    $wallets[] = [
        'event_id'          => $event->event_id,
        'event_name'        => $event->title,
        'poster'            => $event->poster,
        'start_date'        => $event->start_date,
        'status'            => $event->event_status,
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

        // Ambil Data Riwayat Transaksi Withdrawal Merch
        $historyQuery = DB::table('merch_withdrawals')
            ->leftJoin('events', 'merch_withdrawals.event_id', '=', 'events.id')
            ->join('eo', 'merch_withdrawals.eo_id', '=', 'eo.id')
            ->where('merch_withdrawals.eo_id', $eoId)
            ->select(
                'merch_withdrawals.*', 
                'events.title as event_name',
                'eo.bank_name',
                'eo.account_number',
                'eo.account_name'
            );

        if ($request->has('event_id') && !is_null($request->event_id)) {
            $historyQuery->where('merch_withdrawals.event_id', $request->event_id);
        }

        $history = $historyQuery->orderByDesc('merch_withdrawals.id')->get();

        return view('eo.wallet.merch.dashboard', compact(
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
     * 2. Menampilkan Formulir Pengajuan Withdraw Merch (create)
     */
    public function create($eventId)
    {
        $eoId = $this->getEoId();
        
        if (!$eoId) {
            return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
        }

        $event = DB::table('events')
            ->join('merch_wallets', 'events.id', '=', 'merch_wallets.event_id')
            ->where('events.eo_id', $eoId)
            ->where('events.id', $eventId)
            ->select(
                'events.id as event_id',
                'events.title as event_name',
                'merch_wallets.available_balance',
                'merch_wallets.withdraw_locked',
                'merch_wallets.negative_balance'
            )
            ->first();

        if (!$event) {
            return redirect()->route('eo.wallet.merch.dashboard')->with('error', 'Data Event atau Wallet tidak ditemukan.');
        }

        if ($event->withdraw_locked == 1) {
            return redirect()->back()->with('error', 'Gagal. Fitur penarikan dana untuk merch ini dikunci oleh admin.');
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

        return view('eo.wallet.merch.withdraw', compact('wallet'));
    }

    /**
     * 3. Memproses Submit Form Request Pengajuan Pencairan Dana Merch (store)
     */
/**
     * 3. Memproses Submit Form Request Pengajuan Pencairan Dana Merch (store) - FIXED REDIRECT
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
                ->join('merch_wallets', 'events.id', '=', 'merch_wallets.event_id')
                ->join('eo', 'events.eo_id', '=', 'eo.id') 
                ->where('events.eo_id', $eoId)
                ->where('events.id', $request->event_id)
                ->select(
                    'events.date as start_date', 
                    'merch_wallets.id as wallet_id',
                    'merch_wallets.withdraw_locked',
                    'merch_wallets.negative_balance',
                    'eo.bank_name',       
                    'eo.account_name', 
                    'eo.account_number'
                ) 
                ->lockForUpdate()
                ->first();

            if (!$event) {
                DB::rollBack(); // <-- WAJIB ROLLBACK SEBELUM REDIRECT
                return redirect()->back()->with('error', 'Data Wallet Event tidak ditemukan.');
            }

            $hasPendingWithdrawal = DB::table('merch_withdrawals')
                ->where('event_id', $request->event_id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingWithdrawal) {
                DB::rollBack(); // <-- WAJIB ROLLBACK SEBELUM REDIRECT
                return redirect()->back()->with('error', 'Gagal. Anda masih memiliki pengajuan dana berstatus PENDING.');
            }

            if (is_null($event->bank_name) || is_null($event->account_number)) {
                DB::rollBack(); // <-- WAJIB ROLLBACK SEBELUM REDIRECT
                return redirect()->back()->with('error', 'Gagal. Lengkapi data rekening bank di profil EO Anda terlebih dahulu.');
            }

            if ($event->withdraw_locked == 1) {
                DB::rollBack(); // <-- WAJIB ROLLBACK SEBELUM REDIRECT
                return redirect()->back()->with('error', 'Penarikan untuk event ini sedang dikunci oleh admin.');
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

            $isHMinus10 = false;
            if (!is_null($event->start_date)) {
                $startDate = Carbon::parse($event->start_date)->startOfDay();
                $today = now()->startOfDay();
                $isHMinus10 = $today->diffInDays($startDate, false) <= 10;
            }

            if ($isHMinus10) {
                $minBalanceRequired = 0;
                $minHeldBalance = 0; 
                $plafonPercent = 0.7;
            } else {
                $plafonPercent = 0.5;
            }

            if ($paidTotal < $minBalanceRequired) {
                DB::rollBack(); // <-- WAJIB ROLLBACK SEBELUM REDIRECT
                return redirect()->back()->with('error', 'Gagal. Omset belum mencapai batas syarat minimum ' . $this->formatRupiah($minBalanceRequired));
            }

            if (($paidTotal - ($alreadyWithdrawn + $request->amount)) < $minHeldBalance) {
                DB::rollBack(); // <-- WAJIB ROLLBACK SEBELUM REDIRECT
                return redirect()->back()->with('error', 'Gagal. Penarikan melanggar batas saldo mengendap wajib sistem senilai ' . $this->formatRupiah($minHeldBalance));
            }

            $maxEligibleBalance = floor($paidTotal * $plafonPercent);
            $calculatedAvailable = $maxEligibleBalance - $alreadyWithdrawn;

            if ($calculatedAvailable < $request->amount) {
                DB::rollBack(); // <-- WAJIB ROLLBACK SEBELUM REDIRECT
                return redirect()->back()->with('error', 'Nominal pengajuan melebihi ketentuan batas limit plafon ' . ($plafonPercent * 100) . '%.');
            }

            // Upload Invoice file ke Storage public
            $invoicePath = null;
            if ($request->hasFile('invoice')) {
                $file = $request->file('invoice');
                $filename = 'invoice_merch_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/invoices_withdrawal'), $filename);
                $invoicePath = 'images/invoices_withdrawal/' . $filename;
            }

            $adminReviewNote = "Skala Omset Potensial: " . $this->formatRupiah($potentialRevenue) . " | Plafon: " . ($plafonPercent * 100) . "%" . ($isHMinus10 ? " | Darurat H-10 Terbuka" : "");

            // Insert data withdrawal baru
            DB::table('merch_withdrawals')->insert([
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

            // Potong Sisa Available Balance di tabel wallet
            DB::table('merch_wallets')
                ->where('event_id', $request->event_id)
                ->update([
                    'available_balance' => (int) ($calculatedAvailable - $request->amount),
                    'updated_at'        => now()
                ]);

            DB::commit(); // <-- DATA BERHASIL DISIMPAN SECARA RESMI
            
            // Redirect resmi ke rute dashboard utama milik EO
            return redirect()
                ->route('eo.merch-wallet.dashboard')
                ->with('success', 'Pengajuan penarikan dana merch berhasil dikirim! Menunggu tinjauan manual owner.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan internal web: ' . $e->getMessage());
        }
    }

    /**
     * 4. Menampilkan Halaman Detail Pencairan Dana (show)
     */
    public function show($id)
{
    $eoId = $this->getEoId(); // Ambil ID EO login

    $withdrawal = DB::table('merch_withdrawals')
        ->leftJoin('events', 'merch_withdrawals.event_id', '=', 'events.id')
        ->leftJoin('merch_wallets', 'events.id', '=', 'merch_wallets.event_id') // <--- Kunci Perbaikan: Join ke wallet merch
        ->where('merch_withdrawals.eo_id', $eoId)
        ->where('merch_withdrawals.id', $id)
        ->select(
            'merch_withdrawals.*', 
            'events.title as event_title',
            'merch_wallets.available_balance',  // <--- Tarik nilai saldo ke view
            'merch_wallets.held_balance',       // <--- Tarik nilai saldo ke view
            'merch_wallets.negative_balance'
        )
        ->first();

    if (!$withdrawal) {
        return redirect()->route('eo.wallet.merch.dashboard')->with('error', 'Data pengajuan tidak ditemukan.');
    }

    return view('eo.wallet.merch.detail', compact('withdrawal'));
}

    private function formatRupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }

    /**
     * 5. Menampilkan Halaman Riwayat Penarikan Dana Merchandise Terpisah (history)
     */
/**
     * Menampilkan Halaman Riwayat Penarikan Dana Merchandise Terpisah (Selaras dengan TicketHistoryController)
     */
/**
     * Menampilkan Halaman Riwayat Penarikan Dana Merchandise Terpisah
     */
    public function history(Request $request)
    {
        $eoId = $this->getEoId();
        
        if (!$eoId) {
            return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
        }

        // Ambil data riwayat dan simpan ke variabel $history agar sesuai dengan file view Anda
        $history = DB::table('merch_withdrawals')
            ->leftJoin('events', 'merch_withdrawals.event_id', '=', 'events.id')
            ->join('eo', 'merch_withdrawals.eo_id', '=', 'eo.id')
            ->where('merch_withdrawals.eo_id', $eoId)
            ->select(
                'merch_withdrawals.*', 
                'events.title as event_name',
                'eo.bank_name',
                'eo.account_number',
                'eo.account_name'
            )
            ->orderByDesc('merch_withdrawals.id')
            ->paginate(20); // Menggunakan paginate 20 data per halaman

        // Kirimkan dengan nama 'history'
        return view('eo.wallet.merch.history', compact('history'));
    }

    /**
     * Menampilkan Halaman Detail Pencairan Dana Merchandise (Selaras dengan TicketHistory show)
     */
    public function showDetailHistory($id)
    {
        $eoId = $this->getEoId();

        $withdrawal = DB::table('merch_withdrawals')
            ->leftJoin('events', 'merch_withdrawals.event_id', '=', 'events.id')
            ->where('merch_withdrawals.eo_id', $eoId)
            ->where('merch_withdrawals.id', $id)
            ->select('merch_withdrawals.*', 'events.title as event_title')
            ->first();

        if (!$withdrawal) {
            return redirect()->route('eo.merch-withdraw.history')->with('error', 'Data pengajuan tidak ditemukan.');
        }

        return view('eo.wallet.merch.detail', compact('withdrawal'));
    }

    /**
 * Menampilkan detail produk merchandise yang terjual per event
 */
public function soldProducts($eventId)
{
    $eoId = $this->getEoId();

    try {
        $products = DB::table('transaction_merch_details as tmd')
            ->join('transaction_merch as tm', 'tm.id', '=', 'tmd.transaction_merch_id')
            ->join('products as p', 'p.id', '=', 'tmd.product_id')
            ->join('events as e', 'e.id', '=', 'p.event_id')
            // JOIN ke tabel ukuran karena kolom 'harga' berada di tabel ini sesuai skema database Anda
            ->join('products_ukuran as pu', 'pu.id', '=', 'tmd.ukuran_id') 
            ->where('e.eo_id', $eoId)
            ->where('p.event_id', $eventId)
            ->where('tm.payment_status', 'paid')
            ->groupBy(
                'p.id',
                'p.name',
                'pu.harga'
            )
            ->select(
                'p.name as nama_produk', // Alias agar Javascript dashboard tetap mengenali properti ini
                'pu.harga',              // Diambil dari tabel products_ukuran
                DB::raw('COALESCE(SUM(tmd.quantity), 0) as total_terjual'),
                DB::raw('COALESCE(SUM(tmd.subtotal), 0) as total_omset')
            )
            ->orderByDesc('total_terjual')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
}
<?php

namespace App\Http\Controller\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SaldoController extends Controller
{
public function index(Request $request)
{
        $eoId = session('eo_id') ?? 1; 

        // Ambil info bank EO
        $eoBankInfo = DB::table('eo')->where('id', $eoId)->first();

        // -----------------------------------------------------------------
        // DATA TAB 1: TIKET
        // -----------------------------------------------------------------
        $ticketEvents = DB::table('events')
            ->leftJoin('event_wallets', 'events.id', '=', 'event_wallets.event_id')
            ->where('events.eo_id', $eoId)
            ->select('events.id as event_id', 'events.title', 'events.date as start_date', 'events.end_date', 'event_wallets.withdraw_locked', 'event_wallets.negative_balance')
            ->get();

        $ticketWallets = [];
        $globalTicketAvailable = 0; $globalTicketHeld = 0; $globalTicketSales = 0; $globalTicketNegative = 0;

        foreach ($ticketEvents as $event) {
            $paidTotal = DB::table('transactions')->where('event_id', $event->event_id)->where('payment_status', 'paid')->sum('total_amount') ?? 0;
            $alreadyWithdrawn = DB::table('withdrawals')->where('event_id', $event->event_id)->whereIn('status', ['approved', 'pending'])->sum('amount') ?? 0;
            $potentialRevenue = DB::table('tickets')->where('event_id', $event->event_id)->select(DB::raw('SUM(stock * price) as total'))->value('total') ?? 0;

            $financials = $this->calculateFinancialLogic($paidTotal, $alreadyWithdrawn, $potentialRevenue, $event);
            $negativeBal = (int)($event->negative_balance ?? 0);

            // Akumulasi Global Tiket
            $globalTicketSales += $paidTotal;
            $globalTicketHeld += $financials['held_balance'];
            $globalTicketNegative += $negativeBal;
            if ($event->withdraw_locked != 1 && $negativeBal <= 0) {
                $globalTicketAvailable += $financials['available_balance'];
            }

            $ticketWallets[] = array_merge([
                'title' => $event->title, 
                'event_id' => $event->event_id,
                'negative_balance' => $negativeBal,
                'is_finished' => $financials['is_finished'],
                'is_h_minus_10' => $financials['is_h_minus_10']
            ], $financials);
        }

        $ticketHistory = DB::table('withdrawals')
            ->join('events', 'withdrawals.event_id', '=', 'events.id')
            ->where('withdrawals.eo_id', $eoId)
            ->select('withdrawals.*', 'events.title as event_name')
            ->orderByDesc('withdrawals.id')->get();

        // -----------------------------------------------------------------
        // DATA TAB 2: MERCHANDISE
        // -----------------------------------------------------------------
        $merchEvents = DB::table('events')
            ->leftJoin('merch_wallets', 'events.id', '=', 'merch_wallets.event_id')
            ->where('events.eo_id', $eoId)
            ->select('events.id as event_id', 'events.title', 'events.date as start_date', 'events.end_date', 'merch_wallets.withdraw_locked', 'merch_wallets.negative_balance')
            ->get();

        $merchWallets = [];
        $globalMerchAvailable = 0; $globalMerchHeld = 0; $globalMerchSales = 0; $globalMerchNegative = 0;

        foreach ($merchEvents as $event) {
            $paidTotal = DB::table('transaction_merch')->where('event_id', $event->event_id)->where('payment_status', 'paid')->sum('total_amount') ?? 0;
            $alreadyWithdrawn = DB::table('merch_withdrawals')->where('event_id', $event->event_id)->whereIn('status', ['approved', 'pending'])->sum('amount') ?? 0;
            
            $potentialRevenue = DB::table('products_ukuran')
                ->join('products_varian', 'products_ukuran.varian_id', '=', 'products_varian.id')
                ->join('products', 'products_varian.product_id', '=', 'products.id')
                ->where('products.event_id', $event->event_id)
                ->select(DB::raw('SUM(products_ukuran.stok * products_ukuran.harga) as total'))->value('total') ?? 0;

            $financials = $this->calculateFinancialLogic($paidTotal, $alreadyWithdrawn, $potentialRevenue, $event);
            $negativeBal = (int)($event->negative_balance ?? 0);

            // Akumulasi Global Merch
            $globalMerchSales += $paidTotal;
            $globalMerchHeld += $financials['held_balance'];
            $globalMerchNegative += $negativeBal;
            if ($event->withdraw_locked != 1 && $negativeBal <= 0) {
                $globalMerchAvailable += $financials['available_balance'];
            }

            $merchWallets[] = array_merge([
                'title' => $event->title, 
                'event_id' => $event->event_id,
                'negative_balance' => $negativeBal,
                'is_finished' => $financials['is_finished'],
                'is_h_minus_10' => $financials['is_h_minus_10']
            ], $financials);
        }

        $merchHistory = DB::table('merch_withdrawals')
            ->join('events', 'merch_withdrawals.event_id', '=', 'events.id')
            ->where('merch_withdrawals.eo_id', $eoId)
            ->select('merch_withdrawals.*', 'events.title as event_name')
            ->orderByDesc('merch_withdrawals.id')->get();

        return view('eo.saldo.index', compact(
            'eoId', 'eoBankInfo',
            'ticketWallets', 'ticketHistory', 'globalTicketAvailable', 'globalTicketHeld', 'globalTicketSales', 'globalTicketNegative',
            'merchWallets', 'merchHistory', 'globalMerchAvailable', 'globalMerchHeld', 'globalMerchSales', 'globalMerchNegative'
        ));
    }

    /**
     * AKSI POST: Proses Pengajuan Tarik Dana TIKET
     */
    public function requestTicketWithdraw(Request $request)
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
                ->join('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                ->join('eo', 'events.eo_id', '=', 'eo.id') 
                ->where('events.eo_id', $request->eo_id)
                ->where('events.id', $request->event_id)
                ->select('events.date as start_date', 'events.end_date', 'event_wallets.*', 'eo.bank_name', 'eo.account_number') 
                ->lockForUpdate()->first();

            if (!$event || is_null($event->bank_name) || is_null($event->account_number) || $event->withdraw_locked == 1) {
                return redirect()->back()->with('error', 'Gagal. Profil bank belum lengkap atau fitur penarikan sedang dikunci.');
            }

            if (DB::table('withdrawals')->where('event_id', $request->event_id)->where('status', 'pending')->exists()) {
                return redirect()->back()->with('error', 'Gagal. Anda memiliki pengajuan Tiket yang masih PENDING.');
            }

            $paidTotal = DB::table('transactions')->where('event_id', $request->event_id)->where('payment_status', 'paid')->sum('total_amount') ?? 0;
            $alreadyWithdrawn = DB::table('withdrawals')->where('event_id', $request->event_id)->whereIn('status', ['approved', 'pending'])->sum('amount') ?? 0;
            $potentialRevenue = DB::table('tickets')->where('event_id', $request->event_id)->select(DB::raw('SUM(stock * price) as total'))->value('total') ?? 0;

            $financials = $this->calculateFinancialLogic($paidTotal, $alreadyWithdrawn, $potentialRevenue, $event);

            if ($financials['available_balance'] < $request->amount) {
                return redirect()->back()->with('error', 'Nominal melebihi limit saldo tiket yang tersedia.');
            }

            $invoicePath = null;
            if ($request->hasFile('invoice')) {
                $file = $request->file('invoice');
                $filename = 'invoice_ticket_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $invoicePath = $file->storeAs('invoices', $filename, 'public');
            }

            DB::table('withdrawals')->insert([
                'eo_id' => $request->eo_id, 'event_id' => $request->event_id, 'amount' => $request->amount,
                'note' => $request->note . " [Sistem Log: Plafon Berjalan]", 'status' => 'pending', 
                'invoice_file' => $invoicePath, 'created_at' => now(), 'updated_at' => now(),
            ]);

            DB::table('event_wallets')->where('event_id', $request->event_id)->update([
                'available_balance' => (int) ($financials['available_balance'] - $request->amount), 'updated_at' => now()
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Pengajuan dana TIKET berhasil dikirim!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan internal: ' . $e->getMessage());
        }
    }

    /**
     * AKSI POST: Proses Pengajuan Tarik Dana MERCHANDISE
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
                ->select('events.date as start_date', 'events.end_date', 'merch_wallets.*', 'eo.bank_name', 'eo.account_number') 
                ->lockForUpdate()->first();

            if (!$event || is_null($event->bank_name) || is_null($event->account_number) || $event->withdraw_locked == 1) {
                return redirect()->back()->with('error', 'Gagal. Profil bank belum lengkap atau fitur penarikan sedang dikunci.');
            }

            if (DB::table('merch_withdrawals')->where('event_id', $request->event_id)->where('status', 'pending')->exists()) {
                return redirect()->back()->with('error', 'Gagal. Anda memiliki pengajuan Merchandise yang masih PENDING.');
            }

            $paidTotal = DB::table('transaction_merch')->where('event_id', $request->event_id)->where('payment_status', 'paid')->sum('total_amount') ?? 0;
            $alreadyWithdrawn = DB::table('merch_withdrawals')->where('event_id', $request->event_id)->whereIn('status', ['approved', 'pending'])->sum('amount') ?? 0;
            
            $potentialRevenue = DB::table('products_ukuran')
                ->join('products_varian', 'products_ukuran.varian_id', '=', 'products_varian.id')
                ->join('products', 'products_varian.product_id', '=', 'products.id')
                ->where('products.event_id', $event->event_id)
                ->select(DB::raw('SUM(products_ukuran.stok * products_ukuran.harga) as total'))
                ->value('total') ?? 0;

            $financials = $this->calculateFinancialLogic($paidTotal, $alreadyWithdrawn, $potentialRevenue, $event);

            if ($financials['available_balance'] < $request->amount) {
                return redirect()->back()->with('error', 'Nominal melebihi limit saldo merchandise yang tersedia.');
            }

            $invoicePath = null;
            if ($request->hasFile('invoice')) {
                $file = $request->file('invoice');
                $filename = 'invoice_merch_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $invoicePath = $file->storeAs('invoices', $filename, 'public');
            }

            DB::table('merch_withdrawals')->insert([
                'eo_id' => $request->eo_id, 'event_id' => $request->event_id, 'amount' => $request->amount,
                'note' => $request->note . " [Sistem Log: Plafon Merch Berjalan]", 'status' => 'pending', 
                'invoice_file' => $invoicePath, 'created_at' => now(), 'updated_at' => now(),
            ]);

            DB::table('merch_wallets')->where('event_id', $request->event_id)->update([
                'available_balance' => (int) ($financials['available_balance'] - $request->amount), 'updated_at' => now()
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Pengajuan dana MERCHANDISE berhasil dikirim!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan internal: ' . $e->getMessage());
        }
    }

    /**
     * CORE FINANCIAL LOGIC (Mesin Hitung Otomatis)
     */
    private function calculateFinancialLogic($paidTotal, $alreadyWithdrawn, $potentialRevenue, $event)
{
    $isSkalaBesar = $potentialRevenue >= 50000000;
    $minBalanceRequired = $isSkalaBesar ? 1000000 : 200000; 
    $minHeldBalance = $isSkalaBesar ? 500000 : 100000;       

    $isEventFinished = false; $isHMinus10 = false;
    
    if (!is_null($event->start_date)) {
        $today = now()->startOfDay();
        $startDate = Carbon::parse($event->start_date)->startOfDay();
        $daysLeft = $today->diffInDays($startDate);
        $isHMinus10 = ($daysLeft <= 10) && $today->isBefore($startDate);
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
    if ($calculatedAvailable < 0) $calculatedAvailable = 0;

    $sisaKasSistem = $paidTotal - $alreadyWithdrawn;
    $heldBalance = $sisaKasSistem - $calculatedAvailable;
    if ($heldBalance < 0) $heldBalance = 0;

    $canWithdraw = true;
    $systemReason = '';

    if ($event->withdraw_locked == 1) {
        $canWithdraw = false; $calculatedAvailable = 0; $heldBalance = $paidTotal - $alreadyWithdrawn;
        $systemReason = 'Penarikan dikunci sementara oleh admin.';
    } elseif ($paidTotal < $minBalanceRequired) {
        $canWithdraw = false; $calculatedAvailable = 0; $heldBalance = $paidTotal - $alreadyWithdrawn;
        $systemReason = 'Omset belum mencapai batas minimal sistem.';
    } elseif (($paidTotal - $alreadyWithdrawn) < $minHeldBalance && !$isEventFinished) {
        $canWithdraw = false; $calculatedAvailable = 0; $heldBalance = $paidTotal - $alreadyWithdrawn;
        $systemReason = 'Saldo berjalan di bawah target mengendap.';
    } elseif ($calculatedAvailable <= 0) {
        $canWithdraw = false; $calculatedAvailable = 0;
        $systemReason = 'Kuota limit termin berjalan Anda habis.';
    }

    return [
        'total_sales' => $paidTotal,
        'already_withdrawn' => $alreadyWithdrawn,
        'available_balance' => $calculatedAvailable, 
        'held_balance' => $heldBalance, 
        'can_withdraw' => $canWithdraw,
        'system_reason' => $systemReason,
        'is_finished' => $isEventFinished,
        'is_h_minus_10' => $isHMinus10
    ];
}
}
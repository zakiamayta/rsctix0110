<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Eo;
use App\Models\Event;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\TransactionMerch;

class SaldoController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            /*
            |--------------------------------------------------------------------------
            | AUTH USER
            |--------------------------------------------------------------------------
            */

            if (!auth('user')->check()) {
                return redirect()->route('loginuser');
            }

            $user = auth('user')->user();

            /*
            |--------------------------------------------------------------------------
            | ROLE EO ONLY
            |--------------------------------------------------------------------------
            */

            if ($user->role !== 'eo') {
                abort(403);
            }

            return $next($request);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HALAMAN SALDO EO
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $user = auth('user')->user();

        /*
        |--------------------------------------------------------------------------
        | DATA EO
        |--------------------------------------------------------------------------
        */

        $eo = Eo::where('user_id', $user->id)->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | EVENT IDS
        |--------------------------------------------------------------------------
        */

        $eventIds = Event::where('eo_id', $eo->id)
            ->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | TOTAL PENDAPATAN TIKET
        |--------------------------------------------------------------------------
        */

        $totalTicketRevenue = Transaction::whereIn('event_id', $eventIds)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | TOTAL PENDAPATAN MERCH
        |--------------------------------------------------------------------------
        */

        $totalMerchRevenue = TransactionMerch::whereHas(
            'details.product.event',
            function ($q) use ($eo) {
                $q->where('eo_id', $eo->id);
            }
        )
        ->where('payment_status', 'paid')
        ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | TOTAL REVENUE
        |--------------------------------------------------------------------------
        */

        $totalRevenue = $totalTicketRevenue + $totalMerchRevenue;

        /*
        |--------------------------------------------------------------------------
        | TOTAL WITHDRAW APPROVED
        |--------------------------------------------------------------------------
        */

        $totalWithdraw = Withdrawal::where('eo_id', $eo->id)
            ->where('status', 'approved')
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | SALDO TERSEDIA
        |--------------------------------------------------------------------------
        */

        $availableBalance = $totalRevenue - $totalWithdraw;

        /*
        |--------------------------------------------------------------------------
        | HISTORY WITHDRAWAL
        |--------------------------------------------------------------------------
        */

        $withdrawals = Withdrawal::with('eo')
            ->where('eo_id', $eo->id)
            ->latest()
            ->get();

        return view('eo.saldo', compact(
            'eo',
            'totalRevenue',
            'totalWithdraw',
            'availableBalance',
            'withdrawals'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | AJUKAN WITHDRAWAL
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'note'   => 'nullable|string'
        ]);

        $user = auth('user')->user();

        /*
        |--------------------------------------------------------------------------
        | GET EO
        |--------------------------------------------------------------------------
        */

        $eo = Eo::where('user_id', $user->id)->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | VALIDASI DATA REKENING
        |--------------------------------------------------------------------------
        */

        if (
            !$eo->bank_name ||
            !$eo->account_name ||
            !$eo->account_number
        ) {
            return back()->with(
                'error',
                'Silakan lengkapi data rekening terlebih dahulu pada profile EO'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | GET EVENT IDS
        |--------------------------------------------------------------------------
        */

        $eventIds = Event::where('eo_id', $eo->id)
            ->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | TOTAL PENDAPATAN TIKET
        |--------------------------------------------------------------------------
        */

        $totalTicketRevenue = Transaction::whereIn('event_id', $eventIds)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | TOTAL PENDAPATAN MERCH
        |--------------------------------------------------------------------------
        */

        $totalMerchRevenue = TransactionMerch::whereHas(
            'details.product.event',
            function ($q) use ($eo) {
                $q->where('eo_id', $eo->id);
            }
        )
        ->where('payment_status', 'paid')
        ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | TOTAL REVENUE
        |--------------------------------------------------------------------------
        */

        $totalRevenue = $totalTicketRevenue + $totalMerchRevenue;

        /*
        |--------------------------------------------------------------------------
        | TOTAL WITHDRAW APPROVED
        |--------------------------------------------------------------------------
        */

        $totalWithdraw = Withdrawal::where('eo_id', $eo->id)
            ->where('status', 'approved')
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | AVAILABLE BALANCE
        |--------------------------------------------------------------------------
        */

        $availableBalance = $totalRevenue - $totalWithdraw;

        /*
        |--------------------------------------------------------------------------
        | VALIDASI SALDO
        |--------------------------------------------------------------------------
        */

        if ($request->amount > $availableBalance) {

            return back()->with(
                'error',
                'Saldo tidak mencukupi'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SIMPAN WITHDRAWAL
        |--------------------------------------------------------------------------
        */

        Withdrawal::create([
            'eo_id'  => $eo->id,
            'amount' => $request->amount,
            'note'   => $request->note,
            'status' => 'pending',
        ]);

        return back()->with(
            'success',
            'Pengajuan withdrawal berhasil diajukan'
        );
    }
}
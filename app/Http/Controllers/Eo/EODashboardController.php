<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transaction;
use App\Models\TransactionMerch;
use App\Models\TransactionMerchDetail;
use App\Models\Event;
use App\Models\Eo;
use App\Models\TicketAttendee;

use Carbon\Carbon;

class EoDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (!auth('user')->check()) {
                return redirect()->route('loginuser');
            }

            $user = auth('user')->user();

            $eo = Eo::where('user_id', $user->id)->first();

            if ($user->role !== 'eo') {
                abort(403, 'Akses hanya untuk EO');
            }

            if (!$eo || $eo->status !== 'approved') {
                abort(403, 'Akun belum di-approve');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $user = auth('user')->user();

        /*
        |--------------------------------------------------------------------------
        | EO
        |--------------------------------------------------------------------------
        */

        $eo = Eo::where('user_id', $user->id)->first();

        /*
        |--------------------------------------------------------------------------
        | EVENT EO
        |--------------------------------------------------------------------------
        */

        $events = Event::where('eo_id', $eo->id)->get();

        $eventIds = $events->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION TIKET
        |--------------------------------------------------------------------------
        */

        $paidTransactions = Transaction::whereIn('event_id', $eventIds)
            ->where('payment_status', 'paid');

        $unpaidTransactions = Transaction::whereIn('event_id', $eventIds)
            ->where('payment_status', 'unpaid');

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION MERCH
        |--------------------------------------------------------------------------
        */

        $paidMerchTransactions = TransactionMerch::whereHas(
            'details.product.event',
            function ($q) use ($eo) {

                $q->where('eo_id', $eo->id);

            }
        )->where('payment_status', 'paid');

        $unpaidMerchTransactions = TransactionMerch::whereHas(
            'details.product.event',
            function ($q) use ($eo) {

                $q->where('eo_id', $eo->id);

            }
        )->where('payment_status', 'unpaid');

        /*
        |--------------------------------------------------------------------------
        | TOTAL TIKET TERJUAL
        |--------------------------------------------------------------------------
        */

        $totalTicketsSold = TicketAttendee::whereHas(
            'transaction',
            function ($q) use ($eventIds) {

                $q->whereIn('event_id', $eventIds)
                  ->where('payment_status', 'paid');

            }
        )->count();

        /*
        |--------------------------------------------------------------------------
        | TOTAL MERCH TERJUAL
        |--------------------------------------------------------------------------
        */

        $totalMerchSold = TransactionMerchDetail::whereHas(
            'product.event',
            function ($q) use ($eo) {

                $q->where('eo_id', $eo->id);

            }
        )
        ->whereHas('transaction', function ($q) {

            $q->where('payment_status', 'paid');

        })
        ->sum('quantity');

        /*
        |--------------------------------------------------------------------------
        | TOTAL PENDAPATAN TIKET
        |--------------------------------------------------------------------------
        */

        $ticketRevenue = (clone $paidTransactions)
            ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | TOTAL PENDAPATAN MERCH
        |--------------------------------------------------------------------------
        */

        $merchRevenue = (clone $paidMerchTransactions)
            ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | TOTAL PENDAPATAN
        |--------------------------------------------------------------------------
        */

        $totalRevenue = $ticketRevenue + $merchRevenue;

        /*
        |--------------------------------------------------------------------------
        | PENDAPATAN HARI INI
        |--------------------------------------------------------------------------
        */

        $todayTicketRevenue = (clone $paidTransactions)
            ->whereDate('paid_time', today())
            ->sum('total_amount');

        $todayMerchRevenue = (clone $paidMerchTransactions)
            ->whereDate('paid_time', today())
            ->sum('total_amount');

        $todayRevenue = $todayTicketRevenue + $todayMerchRevenue;

        /*
        |--------------------------------------------------------------------------
        | TRANSAKSI BERHASIL
        |--------------------------------------------------------------------------
        */

        $successTransactions =
            (clone $paidTransactions)->count()
            +
            (clone $paidMerchTransactions)->count();

        /*
        |--------------------------------------------------------------------------
        | TRANSAKSI PENDING / BELUM BAYAR
        |--------------------------------------------------------------------------
        */

        $pendingTransactions =
            (clone $unpaidTransactions)->count()
            +
            (clone $unpaidMerchTransactions)->count();

        /*
        |--------------------------------------------------------------------------
        | EVENT AKTIF
        |--------------------------------------------------------------------------
        */

        $activeEvents = Event::where('eo_id', $eo->id)
            ->where('status', 'approved')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | PENJUALAN HARI INI
        |--------------------------------------------------------------------------
        */

        $todaySales =
            (clone $paidTransactions)
                ->whereDate('paid_time', today())
                ->count()
            +
            (clone $paidMerchTransactions)
                ->whereDate('paid_time', today())
                ->count();

        /*
        |--------------------------------------------------------------------------
        | TOTAL TRANSAKSI
        |--------------------------------------------------------------------------
        */

        $totalTransactions =
            Transaction::whereIn('event_id', $eventIds)->count()
            +
            TransactionMerch::whereHas(
                'details.product.event',
                function ($q) use ($eo) {

                    $q->where('eo_id', $eo->id);

                }
            )->count();

        /*
        |--------------------------------------------------------------------------
        | SUCCESS RATE
        |--------------------------------------------------------------------------
        */

        $successRate = $totalTransactions > 0
            ? round(($successTransactions / $totalTransactions) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | RECENT TRANSACTIONS TIKET
        |--------------------------------------------------------------------------
        */

        $recentTicketTransactions = Transaction::with('event')
            ->whereIn('event_id', $eventIds)
            ->latest()
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RECENT TRANSACTIONS MERCH
        |--------------------------------------------------------------------------
        */

        $recentMerchTransactions = TransactionMerch::with([
                'details.product.event'
            ])
            ->whereHas(
                'details.product.event',
                function ($q) use ($eo) {

                    $q->where('eo_id', $eo->id);

                }
            )
            ->latest()
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | SALES PERFORMANCE 7 HARI
        |--------------------------------------------------------------------------
        */

        $salesChart = [];

        for ($i = 6; $i >= 0; $i--) {

            $date = Carbon::today()->subDays($i);

            /*
            |--------------------------------------------------------------------------
            | REVENUE TIKET
            |--------------------------------------------------------------------------
            */

            $ticketTotal = Transaction::whereIn('event_id', $eventIds)
                ->where('payment_status', 'paid')
                ->whereDate('paid_time', $date)
                ->sum('total_amount');

            /*
            |--------------------------------------------------------------------------
            | REVENUE MERCH
            |--------------------------------------------------------------------------
            */

            $merchTotal = TransactionMerch::whereHas(
                'details.product.event',
                function ($q) use ($eo) {

                    $q->where('eo_id', $eo->id);

                }
            )
            ->where('payment_status', 'paid')
            ->whereDate('paid_time', $date)
            ->sum('total_amount');

            /*
            |--------------------------------------------------------------------------
            | PUSH CHART
            |--------------------------------------------------------------------------
            */

            $salesChart[] = [
                'date'  => $date->format('d M'),
                'total' => $ticketTotal + $merchTotal
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view('eo.dashboard', compact(
            'events',
            'totalTicketsSold',
            'totalMerchSold',
            'ticketRevenue',
            'merchRevenue',
            'totalRevenue',
            'todayRevenue',
            'successTransactions',
            'pendingTransactions',
            'activeEvents',
            'todaySales',
            'totalTransactions',
            'successRate',
            'recentTicketTransactions',
            'recentMerchTransactions',
            'salesChart'
        ));
    }
}
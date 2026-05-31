<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EODashboardController extends Controller
{
    public function index(Request $request)
    {
        try {

            /// 🔥 USER LOGIN
            $user = $request->user();

            if (!$user) {

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            /// 🔥 AMBIL EO BERDASARKAN USER LOGIN
            $eo = DB::table('eo')
                ->where('user_id', $user->id)
                ->first();

            /// ❌ BELUM PUNYA EO
            if (!$eo) {

                return response()->json([
                    'success' => true,
                    'data' => [
                        'eo_name' => null,
                        'eo_status' => 'pending',

                        'total_tickets' => 0,
                        'total_revenue' => 0,
                        'today_sales' => 0,

                        'active_events' => 0,
                        'approved_events' => 0,
                        'pending_events' => 0,
                        'rejected_events' => 0,

                        'events' => [],
                    ]
                ]);
            }

            $eoId = $eo->id;

            /// 🔥 SEMUA EVENT EO
            $eventsQuery = DB::table('events')
                ->where('eo_id', $eoId);

            $eventIds = $eventsQuery->pluck('id');

            /// 🎫 TOTAL TIKET
            $totalTickets = DB::table('transactions')
                ->whereIn('event_id', $eventIds)
                ->where('payment_status', 'paid')
                ->count();

            /// 💰 TOTAL REVENUE
            $totalRevenue = DB::table('transactions')
                ->whereIn('event_id', $eventIds)
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            /// 📅 PENJUALAN HARI INI
            $todaySales = DB::table('transactions')
                ->whereIn('event_id', $eventIds)
                ->where('payment_status', 'paid')
                ->whereDate('created_at', Carbon::today())
                ->count();

            /// 🎯 STATUS EVENT
            $approvedEvents = DB::table('events')
                ->where('eo_id', $eoId)
                ->where('status', 'approved')
                ->count();

            $pendingEvents = DB::table('events')
                ->where('eo_id', $eoId)
                ->where('status', 'pending')
                ->count();

            $rejectedEvents = DB::table('events')
                ->where('eo_id', $eoId)
                ->where('status', 'rejected')
                ->count();

            /// 🚀 EVENT AKTIF
            $activeEvents = DB::table('events')
                ->where('eo_id', $eoId)
                ->where('status', 'approved')
                ->where('date', '>=', now())
                ->count();

            /// 🎫 LIST EVENT
            $events = DB::table('events')

                ->leftJoin('transactions', function ($join) {

                    $join->on(
                        'events.id',
                        '=',
                        'transactions.event_id'
                    )
                    ->where(
                        'transactions.payment_status',
                        'paid'
                    );
                })

                ->where('events.eo_id', $eoId)

                ->select(
                    'events.id',
                    'events.title',
                    'events.date',
                    'events.status',

                    DB::raw('COUNT(transactions.id) as sold'),

                    DB::raw('COALESCE(SUM(transactions.total_amount), 0) as revenue')
                )

                ->groupBy(
                    'events.id',
                    'events.title',
                    'events.date',
                    'events.status'
                )

                ->orderByDesc('events.created_at')

                ->get();

            return response()->json([

                'success' => true,

                'data' => [

                    /// 🔥 EO
                    'eo_id' => $eo->id,
                    'eo_name' => $eo->nama_badan_usaha,
                    'eo_status' => $eo->status,
                    'penanggung_jawab' => $eo->penanggung_jawab,

                    /// 📊 STATISTIK
                    'total_tickets' => (int) $totalTickets,
                    'total_revenue' => (int) $totalRevenue,
                    'today_sales' => (int) $todaySales,

                    'active_events' => (int) $activeEvents,
                    'approved_events' => (int) $approvedEvents,
                    'pending_events' => (int) $pendingEvents,
                    'rejected_events' => (int) $rejectedEvents,

                    /// 🎫 EVENTS
                    'events' => $events,
                ]
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function ticketSales(Request $request)
    {
        $user = $request->user();

        $eo = DB::table('eo')
            ->where('user_id', $user->id)
            ->first();

        if (!$eo) {

            return response()->json([
                'success' => false,
                'message' => 'EO tidak ditemukan',
                'data' => []
            ]);
        }

        $sales = DB::table('transactions')

            ->join(
                'events',
                'events.id',
                '=',
                'transactions.event_id'
            )

            ->leftJoin(
                'ticket_attendees',
                'ticket_attendees.transaction_id',
                '=',
                'transactions.id'
            )

            ->where('events.eo_id', $eo->id)

            ->select(

                'transactions.id',

                'events.title as event_title',

                'transactions.payment_status',

                /// 🔥 GANTI KE TOTAL_PRICE
                'transactions.total_price',

                'transactions.checkout_time',

                'transactions.payment_method',

                DB::raw('COUNT(ticket_attendees.id) as total_attendees')
            )

            ->groupBy(
                'transactions.id',
                'events.title',
                'transactions.payment_status',

                /// 🔥 GANTI JUGA
                'transactions.total_price',

                'transactions.checkout_time',
                'transactions.payment_method'
            )

            ->orderByDesc('transactions.id')

            ->get();

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }
    public function ticketSalesDetail($id)
    {
        $transaction = DB::table('transactions')

            ->join(
                'events',
                'events.id',
                '=',
                'transactions.event_id'
            )

            ->where('transactions.id', $id)

            ->select(
                'transactions.*',
                'events.title as event_title'
            )

            ->first();

        if (!$transaction) {

            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        /// =========================
        /// HITUNGAN
        /// =========================

        // harga tiket asli
        $totalAmount = (double) $transaction->total_amount;

        // biaya layanan 10%
        $serviceFee = $totalAmount * 0.10;

        // total dibayar customer
        $totalPrice = $totalAmount + $serviceFee;

        $attendees = DB::table('ticket_attendees')

            ->join(
                'tickets',
                'tickets.id',
                '=',
                'ticket_attendees.ticket_id'
            )

            ->where(
                'ticket_attendees.transaction_id',
                $id
            )

            ->select(
                'ticket_attendees.name',
                'ticket_attendees.phone_number',
                'tickets.name as ticket_name'
            )

            ->get();

        return response()->json([

            'success' => true,

            'data' => [

                'transaction' => [

                    'id' => $transaction->id,
                    'event_title' => $transaction->event_title,

                    'email' => $transaction->email,

                    'payment_status' =>
                        $transaction->payment_status,

                    'payment_method' =>
                        $transaction->payment_method,

                    'checkout_time' =>
                        $transaction->checkout_time,

                    'paid_time' =>
                        $transaction->paid_time,

                    /// 🔥 BREAKDOWN
                    'total_price' => $totalPrice,
                    'service_fee' => $serviceFee,
                    'total_amount' => $totalAmount,
                ],

                'attendees' => $attendees,
            ]
        ]);
    }
}
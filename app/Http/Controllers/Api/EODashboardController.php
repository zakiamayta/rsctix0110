<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
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
                        'total_merch' => 0, 
                        'total_revenue' => 0,
                        'total_ticket_revenue' => 0,
                        'total_merch_revenue' => 0,
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
            $eventsQuery = DB::table('events')->where('eo_id', $eoId);
            $eventIds = $eventsQuery->pluck('id');

            /// 🎫 TOTAL TIKET TERJUAL
            $totalTickets = DB::table('transactions')
                ->whereIn('event_id', $eventIds)
                ->where('payment_status', 'paid')
                ->count();

            /// 📦 HITUNG JUMLAH QUANTITY MERCHANDISE TERJUAL
            $totalMerch = DB::table('transaction_merch_details')
                ->join('transaction_merch', 'transaction_merch.id', '=', 'transaction_merch_details.transaction_merch_id')
                ->join('products', 'products.id', '=', 'transaction_merch_details.product_id')
                ->join('events', 'events.id', '=', 'products.event_id')
                ->where('events.eo_id', $eoId)
                ->where('transaction_merch.payment_status', 'paid')
                ->sum('transaction_merch_details.quantity'); 

            /// 💰 1. HITUNG PENDAPATAN TIKET UTAMA
            $totalTicketRevenue = DB::table('transactions')
                ->whereIn('event_id', $eventIds)
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            /// 🛍️ 2. HITUNG PENDAPATAN MERCHANDISE 
            $totalMerchRevenue = DB::table('transaction_merch_details')
                ->join('transaction_merch', 'transaction_merch.id', '=', 'transaction_merch_details.transaction_merch_id')
                ->join('products', 'products.id', '=', 'transaction_merch_details.product_id')
                ->join('events', 'events.id', '=', 'products.event_id') 
                ->where('events.eo_id', $eoId)
                ->where('transaction_merch.payment_status', 'paid')
                ->sum('transaction_merch_details.subtotal');

            /// 💵 3. AKUMULASI GABUNGAN TOTAL REVENUE
            $totalRevenue = $totalTicketRevenue + $totalMerchRevenue;

            /// 📅 PENJUALAN HARI INI (Gabungan Tiket Hari Ini + Nota Merch Hari Ini)
            $todayTicketSales = DB::table('transactions')
                ->whereIn('event_id', $eventIds)
                ->where('payment_status', 'paid')
                ->whereDate('created_at', Carbon::today())
                ->count();

            $todayMerchSales = DB::table('transaction_merch_details')
                ->join('transaction_merch', 'transaction_merch.id', '=', 'transaction_merch_details.transaction_merch_id')
                ->join('products', 'products.id', '=', 'transaction_merch_details.product_id')
                ->join('events', 'events.id', '=', 'products.event_id')
                ->where('events.eo_id', $eoId)
                ->where('transaction_merch.payment_status', 'paid')
                ->whereDate('transaction_merch.created_at', Carbon::today())
                ->distinct('transaction_merch.id') // Hitung per invoice transaksi
                ->count();

            $todaySales = $todayTicketSales + $todayMerchSales;

            /// 🎯 STATUS EVENT
            $approvedEvents = DB::table('events')->where('eo_id', $eoId)->where('status', 'approved')->count();
            $pendingEvents = DB::table('events')->where('eo_id', $eoId)->where('status', 'pending')->count();
            $rejectedEvents = DB::table('events')->where('eo_id', $eoId)->where('status', 'rejected')->count();
        
            /// 🚀 EVENT AKTIF 
            $activeEvents = DB::table('events')
                ->where('eo_id', $eoId)
                ->where('status', 'approved')
                ->whereDate('end_date', '>=', Carbon::today()) 
                ->count();

            /// 🎫 LIST EVENT
            $events = DB::table('events')
                ->leftJoin('transactions', function ($join) {
                    $join->on('events.id', '=', 'transactions.event_id')
                        ->where('transactions.payment_status', 'paid');
                })
                ->where('events.eo_id', $eoId)
                ->select(
                    'events.id',
                    'events.title',
                    'events.date',
                    'events.end_date', 
                    'events.status',
                    'events.poster',
                    DB::raw('COUNT(transactions.id) as sold'),
                    DB::raw('COALESCE(SUM(transactions.total_amount), 0) as revenue')
                )
                ->groupBy(
                    'events.id', 
                    'events.title', 
                    'events.date', 
                    'events.end_date', 
                    'events.status', 
                    'events.poster', 
                    'events.created_at'
                )
                ->orderByDesc('events.created_at')
                ->get();

            /// ✨ UBAH POSTER LIST EVENT MENJADI FULL URL UNTUK FLUTTER
            $events->transform(function ($event) {
                if ($event->poster) {
                    $event->poster = filter_var($event->poster, FILTER_VALIDATE_URL) 
                        ? $event->poster 
                        : Storage::url($event->poster);
                } else {
                    $event->poster = null;
                }
                
                $event->sold = (int) $event->sold;
                $event->revenue = (int) $event->revenue;
                
                return $event;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    /// 🔥 EO
                    'eo_id' => $eo->id,
                    'eo_name' => $eo->nama_badan_usaha,
                    'eo_status' => $eo->status,
                    'penanggung_jawab' => $eo->penanggung_jawab,

                    /// 📊 STATISTIK UTAMA & SUB-REVENUE
                    'total_tickets' => (int) $totalTickets,
                    'total_merch' => (int) $totalMerch, 
                    'total_revenue' => (int) $totalRevenue,
                    'total_ticket_revenue' => (int) $totalTicketRevenue,
                    'total_merch_revenue' => (int) $totalMerchRevenue, 
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

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

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
            ->join('events', 'events.id', '=', 'transactions.event_id')
            ->leftJoin('ticket_attendees', 'ticket_attendees.transaction_id', '=', 'transactions.id')
            ->where('events.eo_id', $eo->id)
            ->select(
                'transactions.id',
                'events.title as event_title',
                'events.poster',
                'transactions.payment_status',
                DB::raw('COALESCE(transactions.grand_total, 0) as total_price'),
                'transactions.checkout_time',
                'transactions.payment_method',
                DB::raw('COUNT(ticket_attendees.id) as total_attendees')
            )
            ->groupBy(
                'transactions.id',
                'events.title',
                'events.poster',
                'transactions.payment_status',
                'transactions.grand_total',
                'transactions.checkout_time',
                'transactions.payment_method'
            )
            ->orderByDesc('transactions.id')
            ->get();

        $sales->transform(function ($item) {
            if ($item->poster) {
                $item->poster = filter_var($item->poster, FILTER_VALIDATE_URL) 
                    ? $item->poster 
                    : Storage::url($item->poster);
            } else {
                $item->poster = null;
            }

            $item->total_price = (int) $item->total_price;
            $item->total_attendees = (int) $item->total_attendees;

            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    public function ticketSalesDetail(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $eo = DB::table('eo')->where('user_id', $user->id)->first();
        if (!$eo) {
            return response()->json([
                'success' => false,
                'message' => 'EO tidak ditemukan'
            ], 403);
        }

        $transaction = DB::table('transactions')
            ->join('events', 'events.id', '=', 'transactions.event_id')
            ->where('transactions.id', $id)
            ->where('events.eo_id', $eo->id) 
            ->select(
                'transactions.*',
                'events.title as event_title'
            )
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan atau Anda tidak memiliki akses ke data ini'
            ], 404);
        }

        $attendees = DB::table('ticket_attendees')
            ->join('tickets', 'tickets.id', '=', 'ticket_attendees.ticket_id')
            ->where('ticket_attendees.transaction_id', $id)
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
                    'kode_unik' => $transaction->kode_unik ?? '-', // 🔥 BARIS INI YANG MEMPERBAIKI BUG
                    'event_title' => $transaction->event_title,
                    'email' => $transaction->email,
                    'payment_status' => $transaction->payment_status,
                    'payment_method' => $transaction->payment_method,
                    'checkout_time' => $transaction->checkout_time,
                    'paid_time' => $transaction->paid_time,
                    'total_amount' => (int) $transaction->total_amount,
                    'service_fee' => (int) ($transaction->service_tax ?? 0), 
                    'total_price' => (int) ($transaction->grand_total ?? 0),
                ],
                'attendees' => $attendees,
            ]
        ]);
    }
    public function generateWebToken(Request $request) 
    {
        $user = $request->user(); // Mengambil user dari token Sanctum/Passport
        $token = Str::random(40);

        // Simpan token di cache selama 2 menit. Key: token, Value: user_id
        Cache::put('web_autologin_' . $token, $user->id, now()->addMinutes(2));

        return response()->json([
            'success' => true,
            'token' => $token // Cukup kirim tokennya saja, URL-nya biar Flutter yang susun
        ]);
    }
}
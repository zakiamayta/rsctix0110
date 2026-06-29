<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardController extends Controller
{
    /**
     * Memuat rincian mendalam data akuntansi dan transaksi platform RSC Ticketing.
     * Mengambil keseluruhan track record event (termasuk yang sudah selesai, batal, maupun rescheduled).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Proteksi Akses Khusus Superadmin
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Halaman ini khusus Admin Utama.'
            ], 403);
        }

        $avatarUrl = null;
        if ($user->avatar) {
            $avatarUrl = filter_var($user->avatar, FILTER_VALIDATE_URL) ? $user->avatar : url($user->avatar);
        }

        try {
            // =====================================================================
            // 2. RINGKASAN DATA STATISTIK UTAMA
            // =====================================================================
            $totalUsers = DB::table('users')->where('role', 'user')->count();
            $totalEo = DB::table('eo')->count(); 
            $totalEventsPending = DB::table('events')->where('status', 'pending')->count();
            
            // Refund pending dari tabel refunds baru berstatus 'pending'
            $totalRefundPending = Schema::hasTable('refunds') 
                ? DB::table('refunds')->where('status', 'pending')->count()
                : DB::table('events')->whereIn('status', ['cancelled', 'pending_cancel'])->count();

            // =====================================================================
            // 3. LAPORAN OMSET PLATFORM GLOBAL & SALDO ESCROW
            // =====================================================================
            $totalTicketSales = DB::table('transactions')
                ->where('payment_status', 'paid')
                ->sum('grand_total');

            $totalMerchSales = DB::table('transaction_merch')
                ->where('payment_status', 'paid')
                ->sum('grand_total');

            $platformBalance = 0;
            if (Schema::hasTable('platform_wallets')) {
                $platformBalance = DB::table('platform_wallets')->where('id', 1)->value('current_balance') ?? 0;
            }

            // =====================================================================
            // 4. MONITORING TRANSAKSI TIKET DENGAN RINCIAN PESERTA & TIKET
            // =====================================================================
            $recentTicketTransactions = DB::table('transactions')
                ->leftJoin('events', 'transactions.event_id', '=', 'events.id')
                ->select(
                    'transactions.id',
                    'transactions.kode_unik as invoice_code', 
                    'transactions.email as user_email', 
                    'transactions.grand_total as total_amount', 
                    'transactions.payment_status as status', 
                    'events.title as event_title',
                    DB::raw('COALESCE(transactions.paid_time, transactions.checkout_time) as date')
                )
                ->orderBy('transactions.id', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($tx) {
                    $tx->total_amount = (int)$tx->total_amount;
                    
                    // Ambil daftar peserta (attendees) untuk transaksi ini
                    $tx->attendees = DB::table('ticket_attendees')
                        ->leftJoin('tickets', 'ticket_attendees.ticket_id', '=', 'tickets.id')
                        ->where('ticket_attendees.transaction_id', $tx->id)
                        ->select(
                            'ticket_attendees.name as attendee_name',
                            'ticket_attendees.phone_number',
                            'tickets.name as ticket_category'
                        )
                        ->get();

                    return $tx;
                });

            // =====================================================================
            // 5. MONITORING TRANSAKSI MERCHANDISE DENGAN RINCIAN PRODUK & VARIAN
            // =====================================================================
            $recentMerchTransactions = DB::table('transaction_merch')
                ->select(
                    'id',
                    'kode_unik as invoice_code', 
                    'email as user_email', 
                    'grand_total as total_amount', 
                    'payment_status as status', 
                    DB::raw('COALESCE(paid_time, checkout_time) as date')
                )
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($tx) {
                    $tx->total_amount = (int)$tx->total_amount;

                    // Ambil rincian detail barang merchandise yang dipesan
                    $tx->items = DB::table('transaction_merch_details')
                        ->leftJoin('products', 'transaction_merch_details.product_id', '=', 'products.id')
                        ->leftJoin('products_varian', 'transaction_merch_details.varian_id', '=', 'products_varian.id')
                        ->leftJoin('products_ukuran', 'transaction_merch_details.ukuran_id', '=', 'products_ukuran.id')
                        ->where('transaction_merch_details.transaction_merch_id', $tx->id)
                        ->select(
                            'products.name as product_name',
                            'products_varian.varian as product_variant',
                            'products_ukuran.ukuran as product_size',
                            'transaction_merch_details.quantity',
                            'transaction_merch_details.price',
                            'transaction_merch_details.subtotal'
                        )
                        ->get()
                        ->map(function($item) {
                            $item->price = (int)$item->price;
                            $item->subtotal = (int)$item->subtotal;
                            return $item;
                        });

                    return $tx;
                });

            // =====================================================================
            // 6. PERFORMA EVENT ORGANIZER (EO) LENGKAP DENGAN PORTFOLIO EVENT (KESELURUHAN STATUS)
            // =====================================================================
            $eoPerformance = DB::table('eo')
                ->join('users', 'eo.user_id', '=', 'users.id')
                ->select(
                    'eo.id as eo_id', 
                    'eo.nama_badan_usaha as eo_name', 
                    'users.email as email',
                    'eo.balance as current_balance',
                    'eo.total_debt as current_debt'
                )
                ->get()
                ->map(function ($eo) {
                    // Tarik keseluruhan event tanpa memandang status aktif saja (approved, pending, rejected, cancelled, dll)
                    $events = DB::table('events')
                        ->where('eo_id', $eo->eo_id)
                        ->select('id', 'title', 'status', 'date', 'is_rescheduled')
                        ->get()
                        ->map(function($ev) {
                            // Hitung revenue masing-masing event
                            $revenue = DB::table('transactions')
                                ->where('event_id', $ev->id)
                                ->where('payment_status', 'paid')
                                ->sum('grand_total');

                            $ticketsSold = DB::table('ticket_attendees')
                                ->leftJoin('tickets', 'ticket_attendees.ticket_id', '=', 'tickets.id')
                                ->where('tickets.event_id', $ev->id)
                                ->count();

                            return [
                                'title' => $ev->title,
                                'status' => $ev->status,
                                'date' => $ev->date,
                                'revenue' => (int)$revenue,
                                'tickets_sold' => $ticketsSold,
                                'is_rescheduled' => $ev->is_rescheduled ?? 0,
                            ];
                        });

                    $totalEvents = $events->count();
                    $totalRevenue = $events->sum('revenue');

                    // Hitung rincian status event secara dinamis untuk laporan superadmin
                    $doneEvents = $events->filter(function($e) {
                        return $e['status'] === 'approved' && strtotime($e['date']) < time();
                    })->count();

                    $activeEvents = $events->filter(function($e) {
                        return $e['status'] === 'approved' && strtotime($e['date']) >= time();
                    })->count();

                    $cancelledEvents = $events->filter(function($e) {
                        return in_array($e['status'], ['cancelled', 'pending_cancel']);
                    })->count();

                    $rescheduledEvents = $events->filter(function($e) {
                        return in_array($e['status'], ['pending_reschedule']) || (isset($e['is_rescheduled']) && $e['is_rescheduled'] > 0);
                    })->count();

                    return [
                        'eo_id' => $eo->eo_id,
                        'eo_name' => $eo->eo_name,
                        'eo_email' => $eo->email,
                        'total_events' => (int)$totalEvents,
                        'total_revenue' => (int)$totalRevenue,
                        'balance' => (int)$eo->current_balance,
                        'debt' => (int)$eo->current_debt,
                        'events_list' => $events->values(),
                        'track_record' => [
                            'done' => $doneEvents,
                            'active' => $activeEvents,
                            'cancelled' => $cancelledEvents,
                            'rescheduled' => $rescheduledEvents
                        ]
                    ];
                })
                ->sortByDesc('total_revenue')
                ->values();

            // =====================================================================
            // 7. MEMANTAU PROSES REFUND & CANCEL (Skema Refunds Terbaru)
            // =====================================================================
            $refundEvents = [];
            if (Schema::hasTable('refunds')) {
                $refundEvents = DB::table('refunds')
                    ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
                    ->leftJoin('events', 'transactions.event_id', '=', 'events.id')
                    ->select(
                        'refunds.id as refund_id',
                        'events.title as event_title',
                        'transactions.kode_unik as invoice_code',
                        'refunds.grand_total_refunded as estimated_refund',
                        'refunds.refunds_tax as refund_tax',
                        'refunds.status',
                        'refunds.bank_name',
                        'refunds.account_number',
                        'refunds.account_name',
                        'refunds.created_at as updated_at'
                    )
                    ->orderBy('refunds.id', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'event_id' => $item->refund_id,
                            'event_title' => $item->event_title ?? "Refund Pembelian Tiket",
                            'status' => $item->status,
                            'eo_name' => "Inv: " . $item->invoice_code,
                            'updated_at' => strval($item->updated_at),
                            'estimated_refund' => (int)$item->estimated_refund,
                            'bank_details' => $item->bank_name . " " . $item->account_number . " a.n " . $item->account_name
                        ];
                    });
            } else {
                $refundEvents = DB::table('events')
                    ->join('eo', 'events.eo_id', '=', 'eo.id')
                    ->whereIn('events.status', ['cancelled', 'pending_cancel'])
                    ->select('events.id as event_id', 'events.title as event_title', 'events.status', 'eo.nama_badan_usaha as eo_name', 'events.updated_at')
                    ->get()
                    ->map(function ($event) {
                        return [
                            'event_id' => $event->event_id,
                            'event_title' => $event->event_title,
                            'status' => $event->status,
                            'eo_name' => $event->eo_name,
                            'updated_at' => strval($event->updated_at),
                            'estimated_refund' => 0,
                            'bank_details' => '-'
                        ];
                    });
            }

            // =====================================================================
            // 8. RESPON JSON UTUH
            // =====================================================================
            return response()->json([
                'success' => true,
                'message' => 'Data dashboard admin berhasil dimuat dengan rincian lengkap.',
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'avatar'=> $avatarUrl,
                    'role'  => $user->role,
                ],
                'statistics' => [
                    'total_users'            => (int)$totalUsers,
                    'total_eo'               => (int)$totalEo,
                    'pending_events_count'   => (int)$totalEventsPending,
                    'pending_refund_count'   => (int)$totalRefundPending,
                    'global_ticket_sales'    => (int)$totalTicketSales,
                    'global_merch_sales'     => (int)$totalMerchSales,
                    'platform_balance'       => (int)$platformBalance,
                ],
                'recent_ticket_transactions' => $recentTicketTransactions,
                'recent_merch_transactions'  => $recentMerchTransactions,
                'eo_performance'             => $eoPerformance,
                'refund_events'              => $refundEvents
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data statistik admin akibat kendala database.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
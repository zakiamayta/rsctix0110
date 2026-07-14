<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Memuat rincian mendalam data akuntansi, statistik, merchandise, dan refund platform RSC.
     * Berdasarkan Skema Database Produksi Utama.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Proteksi Akses Khusus Admin
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
            // 2. RINGKASAN DATA STATISTIK UTAMA (Sesuai Struktur Platform)
            // =====================================================================
            $totalUsers = DB::table('users')->where('role', 'user')->count();
            $totalEo = DB::table('eo')->count();
            
            // Statistik Status Event Komprehensif dari tabel `events`
            $eventStats = DB::table('events')
                ->select(DB::raw("
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'pending_reschedule' OR is_rescheduled > 0 THEN 1 END) as rescheduled,
                    COUNT(CASE WHEN status = 'cancelled' OR status = 'pending_cancel' THEN 1 END) as cancelled,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved
                "))->first();

            // Total Permintaan Pengembalian Dana Aktif (Waiting atau Pending) dari tabel `refunds`
            $totalRefundPending = DB::table('refunds')
                ->whereIn('status', ['waiting', 'pending'])
                ->count();

            // Akuntansi Penjualan Finansial Global Tiket (tabel `transactions`)
            $globalTicketSales = DB::table('transactions')
                ->where('payment_status', 'paid')
                ->sum('grand_total');

            // Akuntansi Penjualan Finansial Global Merchandise (tabel `transaction_merch`)
            $globalMerchSales = DB::table('transaction_merch')
                ->where('payment_status', 'paid')
                ->sum('grand_total');

            // Saldo Dompet Platform Utama (tabel `platform_wallets`)
            $platformWallet = DB::table('platform_wallets')->first();
            $platformBalance = $platformWallet ? (float)$platformWallet->current_balance : 0.00;
            $totalServiceTax = $platformWallet ? (float)$platformWallet->total_service_tax_earned : 0.00;
            $totalRefundFees = $platformWallet ? (float)$platformWallet->total_refund_fees_spent : 0.00;

            // =====================================================================
            // 3. DATA TRANSAKSI TIKET TERBARU & MANIFEST ATTENDEES
            // =====================================================================
            $rawTicketTransactions = DB::table('transactions as t')
                ->leftJoin('events as e', 't.event_id', '=', 'e.id')
                ->select('t.id', 't.kode_unik as invoice_code', 'e.title as event_title', 't.email as user_email', 't.grand_total as total_amount', 't.payment_status as status', 't.paid_time as date')
                ->orderBy('t.id', 'desc')
                ->limit(30)
                ->get();

            $recentTicketTransactions = [];
            foreach ($rawTicketTransactions as $tx) {
                // Ambil data manifest peserta tiket dari table `ticket_attendees` dan hubungkan kategori komponen `tickets`
                $attendees = DB::table('ticket_attendees as ta')
                    ->join('tickets as tk', 'ta.ticket_id', '=', 'tk.id')
                    ->where('ta.transaction_id', $tx->id)
                    ->select('ta.name as attendee_name', 'ta.phone_number', 'tk.name as ticket_category')
                    ->get();

                $recentTicketTransactions[] = [
                    'invoice_code' => $tx->invoice_code ?? '-',
                    'event_title'  => $tx->event_title ?? 'Event Dihapus',
                    'user_email'   => $tx->user_email,
                    'total_amount' => (int)$tx->total_amount,
                    'status'       => $tx->status,
                    'date'         => $tx->date ? date('d M Y, H:i', strtotime($tx->date)) : '-',
                    'attendees'    => $attendees
                ];
            }

            // =====================================================================
            // 4. DATA TRANSAKSI MERCHANDISE TERBARU & DETAIL BRG (DIAGREGASIKAN)
            // =====================================================================
            $rawMerchTransactions = DB::table('transaction_merch')
                ->select('id', 'kode_unik as invoice_code', 'email as user_email', 'grand_total as total_amount', 'payment_status as status', 'paid_time as date')
                ->orderBy('id', 'desc')
                ->limit(30)
                ->get();

            $recentMerchTransactions = [];
            foreach ($rawMerchTransactions as $tx) {
                // Tarik rincian item, varian, dan ukuran sesuai skema relasi `transaction_merch_details`
                $items = DB::table('transaction_merch_details as tmd')
                    ->join('products as p', 'tmd.product_id', '=', 'p.id')
                    ->join('products_varian as pv', 'tmd.varian_id', '=', 'pv.id')
                    ->join('products_ukuran as pu', 'tmd.ukuran_id', '=', 'pu.id')
                    ->where('tmd.transaction_merch_id', $tx->id)
                    ->select('p.name as product_name', 'pv.varian as product_variant', 'pu.ukuran as product_size', 'tmd.quantity', 'tmd.price', 'tmd.subtotal')
                    ->get();

                $recentMerchTransactions[] = [
                    'invoice_code' => $tx->invoice_code ?? '-',
                    'user_email'   => $tx->user_email,
                    'total_amount' => (int)$tx->total_amount,
                    'status'       => $tx->status,
                    'date'         => $tx->date ? date('d M Y, H:i', strtotime($tx->date)) : '-',
                    'items'        => $items
                ];
            }

            // =====================================================================
            // 5. PERFORMA EVENT ORGANIZER & HISTORI PORTOFOLIO EVENT
            // =====================================================================
            $eoList = DB::table('eo')->get();
            $eoPerformance = [];

            foreach ($eoList as $eo) {
                // Hitung data analitik track record performa event internal EO dari tabel `events`
                $doneEvents = DB::table('events')->where('eo_id', $eo->id)->where('date', '<', now())->where('status', 'approved')->count();
                $activeEvents = DB::table('events')->where('eo_id', $eo->id)->where('date', '>=', now())->where('status', 'approved')->count();
                $rescheduledEvents = DB::table('events')->where('eo_id', $eo->id)->where('is_rescheduled', '>', 0)->count();
                $cancelledEvents = DB::table('events')->where('eo_id', $eo->id)->whereIn('status', ['cancelled', 'pending_cancel'])->count();

                // Tarik total omzet pendapatan kotor sales tiket EO lewat relasi tabel `transactions` -> `events`
                $totalRevenue = DB::table('transactions as t')
                    ->join('events as e', 't.event_id', '=', 'e.id')
                    ->where('e.eo_id', $eo->id)
                    ->where('t.payment_status', 'paid')
                    ->sum('t.grand_total');

                // Portofolio semua event yang dikelola mitra EO ini
                $eventsPortfolio = DB::table('events')
                    ->where('eo_id', $eo->id)
                    ->select('id', 'title', 'status', 'date')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->map(function($ev) {
                        $ticketSold = DB::table('transactions')->where('event_id', $ev->id)->where('payment_status', 'paid')->count();
                        $revenue = DB::table('transactions')->where('event_id', $ev->id)->where('payment_status', 'paid')->sum('grand_total');
                        return [
                            'title'        => $ev->title,
                            'status'       => $ev->status,
                            'tickets_sold' => $ticketSold,
                            'revenue'      => (int)$revenue
                        ];
                    });

                $eoPerformance[] = [
                    'id'            => $eo->id,
                    'eo_name'       => $eo->nama_badan_usaha,
                    'eo_email'      => DB::table('users')->where('id', $eo->user_id)->value('email') ?? '-',
                    'total_events'  => DB::table('events')->where('eo_id', $eo->id)->count(),
                    'total_revenue' => (int)$totalRevenue,
                    'balance'       => (float)$eo->balance,
                    // Sumber utang KANONIK = eo_debts (bukan cache eo.total_debt) agar konsisten lintas halaman
                    'debt'          => (float) DB::table('eo_debts')
                                        ->where('eo_id', $eo->id)
                                        ->whereIn('status', ['unpaid', 'partially_paid'])
                                        ->sum('remaining_debt'),
                    'status'        => $eo->status,
                    'track_record'  => [
                        'done'        => $doneEvents,
                        'active'      => $activeEvents,
                        'rescheduled' => $rescheduledEvents,
                        'cancelled'   => $cancelledEvents
                    ],
                    'events_list'   => $eventsPortfolio
                ];
            }

            // =====================================================================
            // 6. ANTRIAN REFUND YANG MEMBUTUHKAN VERIFIKASI / TRANSFER ADMIN
            // =====================================================================
            $refundEvents = DB::table('refunds as r')
                ->leftJoin('transactions as t', 'r.transaction_id', '=', 't.id')
                ->leftJoin('transaction_merch as tm', 'r.transaction_merch_id', '=', 'tm.id')
                ->select(
                    'r.id',
                    'r.bank_name',
                    'r.account_number',
                    'r.account_name',
                    'r.status',
                    'r.grand_total_refunded as estimated_refund',
                    'r.refunds_tax',
                    'r.updated_at',
                    't.kode_unik as ticket_invoice',
                    'tm.kode_unik as merch_invoice',
                    DB::raw("CASE 
                        WHEN r.transaction_id IS NOT NULL THEN 'Refund Tiket' 
                        ELSE 'Refund Merchandise' 
                     END as type_label")
                )
                ->orderBy('r.id', 'desc')
                ->get()
                ->map(function($ref) {
                    return [
                        'id'               => $ref->id,
                        'event_title'      => $ref->type_label,
                        'invoice_code'     => $ref->ticket_invoice ?? $ref->merch_invoice ?? '-',
                        'eo_name'          => 'Customer Account',
                        'bank_details'     => "{$ref->bank_name} - {$ref->account_number} a/n {$ref->account_name}",
                        'status'           => $ref->status,
                        'estimated_refund' => (int)$ref->estimated_refund,
                        'refund_tax'       => (int)$ref->refunds_tax,
                        'updated_at'       => date('d M Y', strtotime($ref->updated_at))
                    ];
                });

            // =====================================================================
            // 7. RETURN PAKET JSON RESPONS KE APP FLUTTER
            // =====================================================================
            return response()->json([
                'success' => true,
                'message' => 'Data workspace admin berhasil dikompilasi.',
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'avatar'=> $avatarUrl,
                ],
                'statistics' => [
                    'total_users'            => (int)$totalUsers,
                    'total_eo'               => (int)$totalEo,
                    'pending_events_count'   => (int)($eventStats->pending ?? 0),
                    'reschedule_events_count'=> (int)($eventStats->rescheduled ?? 0),
                    'cancelled_events_count' => (int)($eventStats->cancelled ?? 0),
                    'pending_refund_count'   => (int)$totalRefundPending,
                    'global_ticket_sales'    => (int)$globalTicketSales,
                    'global_merch_sales'     => (int)$globalMerchSales,
                    // Penambahan Detail Data Finansial Komprehensif Dompet Platform (`platform_wallets`)
                    'platform_balance'       => (int)$platformBalance,
                    'total_service_tax'      => (int)$totalServiceTax,
                    'total_refund_fees'      => (int)$totalRefundFees,
                ],
                'recent_ticket_transactions' => $recentTicketTransactions,
                'recent_merch_transactions'  => $recentMerchTransactions,
                'eo_performance'             => $eoPerformance,
                'refund_events'              => $refundEvents
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data statistik admin.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
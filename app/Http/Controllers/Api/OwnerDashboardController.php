<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class OwnerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Proteksi ketat: Hanya role 'owner' yang lolos
        if (!$user || $user->role !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Halaman ini khusus Owner.'
            ], 403);
        }

        $avatarUrl = null;
        if ($user->avatar) {
            if (filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                $avatarUrl = $user->avatar;
            } else {
                $avatarUrl = asset($user->avatar); 
            }
        }

        $filter = $request->query('filter', 'all');
        
        $txCondition = "1=1";
        $tmCondition = "1=1";
        $rfCondition = "1=1"; // Tambahan kondisi untuk tabel refunds
        $bindings = [];

        switch ($filter) {
            case 'today':
                $txCondition = "DATE(transactions.paid_time) = CURDATE()";
                $tmCondition = "DATE(transaction_merch.paid_time) = CURDATE()";
                $rfCondition = "DATE(refunds.created_at) = CURDATE()";
                break;
            case 'week':
                $txCondition = "transactions.paid_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $tmCondition = "transaction_merch.paid_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $rfCondition = "refunds.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $txCondition = "transactions.paid_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $tmCondition = "transaction_merch.paid_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $rfCondition = "refunds.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'custom':
                if ($request->has('start_date') && $request->has('end_date')) {
                    $startDate = Carbon::parse($request->query('start_date'))->startOfDay()->toDateTimeString();
                    $endDate = Carbon::parse($request->query('end_date'))->endOfDay()->toDateTimeString();
                    
                    $txCondition = "transactions.paid_time BETWEEN ? AND ?";
                    $tmCondition = "transaction_merch.paid_time BETWEEN ? AND ?";
                    $rfCondition = "refunds.created_at BETWEEN ? AND ?";
                    
                    // Binding untuk query stats (disusun berurutan sesuai urutan pemanggilan di SQL bawah)
                    $bindings[] = $startDate; $bindings[] = $endDate; // total_ticket_revenue (paid)
                    $bindings[] = $startDate; $bindings[] = $endDate; // total_ticket_revenue (refunded)
                    $bindings[] = $startDate; $bindings[] = $endDate; // total_merch_revenue
                    $bindings[] = $startDate; $bindings[] = $endDate; // ticket_service_tax (paid)
                    $bindings[] = $startDate; $bindings[] = $endDate; // ticket_service_tax (refunded)
                    $bindings[] = $startDate; $bindings[] = $endDate; // refund_tax_spent
                    $bindings[] = $startDate; $bindings[] = $endDate; // merch_service_tax
                    $bindings[] = $startDate; $bindings[] = $endDate; // total_tickets_sold
                    $bindings[] = $startDate; $bindings[] = $endDate; // total_merch_sold
                }
                break;
        }

        $hasWithdrawals = Schema::hasTable('withdrawals');
        $hasMerchWithdrawals = Schema::hasTable('merch_withdrawals');

        $sqlWithdrawals = $hasWithdrawals ? "(SELECT COUNT(*) FROM withdrawals WHERE status = 'pending')" : "0";
        $sqlMerchWithdrawals = $hasMerchWithdrawals ? "(SELECT COUNT(*) FROM merch_withdrawals WHERE status = 'pending')" : "0";

        // JALANKAN SINKRONISASI QUERY MATANG
        $stats = DB::select("
            SELECT 
                -- Finansial Berdasarkan Filter Periode (Kotor) - Menyertakan hitungan paid & refunded
                (SELECT COALESCE(SUM(grand_total), 0) FROM transactions WHERE payment_status = 'paid' AND $txCondition) AS ticket_revenue_paid,
                (SELECT COALESCE(SUM(grand_total), 0) FROM transactions WHERE payment_status = 'refunded' AND $txCondition) AS ticket_revenue_refunded,
                (SELECT COALESCE(SUM(grand_total), 0) FROM transaction_merch WHERE payment_status = 'paid' AND $tmCondition) AS total_merch_revenue,

                -- Service Tax Bersih (Ikut menyisir data refunds)
                (SELECT COALESCE(SUM(service_tax), 0) FROM transactions WHERE payment_status = 'paid' AND $txCondition) AS ticket_tax_paid,
                (SELECT COALESCE(SUM(service_tax), 0) FROM transactions WHERE payment_status = 'refunded' AND $txCondition) AS ticket_tax_refunded,
                (SELECT COALESCE(SUM(refunds_tax), 0) FROM refunds WHERE $rfCondition) AS refund_tax_spent,
                (SELECT COALESCE(SUM(service_tax), 0) FROM transaction_merch WHERE payment_status IN ('paid', 'refunded') AND $tmCondition) AS merch_service_tax,

                -- Volume Total Kuantitas Item Terjual (Paid + Refunded agar record list tetap muncul)
                (SELECT COUNT(*) FROM ticket_attendees 
                JOIN transactions ON ticket_attendees.transaction_id = transactions.id 
                WHERE transactions.payment_status IN ('paid', 'refunded') AND $txCondition) AS total_tickets_sold,

                (SELECT COALESCE(SUM(tmd.quantity), 0) FROM transaction_merch_details tmd
                JOIN transaction_merch ON tmd.transaction_merch_id = transaction_merch.id 
                WHERE transaction_merch.payment_status = 'paid' AND $tmCondition) AS total_merch_sold,

                -- Antrean Approval
                (SELECT COUNT(*) FROM eo WHERE status = 'pending') AS pending_eo,
                (SELECT COUNT(*) FROM events WHERE status = 'pending') AS pending_events,
                (SELECT COUNT(*) FROM events WHERE status IN ('pending_cancel', 'pending_reschedule')) AS pending_data_changes,
                ($sqlWithdrawals + $sqlMerchWithdrawals) AS pending_withdraws,

                -- Akumulasi Total Data Global
                (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_users_count,
                (SELECT COUNT(*) FROM eo) AS total_eo_count,
                (SELECT COUNT(*) FROM events) AS total_events_count,

                -- Event Aktif
                (SELECT COUNT(*) FROM events 
                WHERE status = 'approved' 
                AND (
                    COALESCE(
                        (SELECT MAX(DATE(jadwal.tanggal)) FROM jadwal WHERE jadwal.event_id = events.id), 
                        DATE(events.date)
                    ) >= CURDATE()
                )) AS active_events_count
        ", $bindings)[0];

        // Hitung Akumulasi Revenue Total Gabungan (Kotor)
        $totalTicketRevenueTotal = (int)$stats->ticket_revenue_paid + (int)$stats->ticket_revenue_refunded;
        $totalGrossRevenue = $totalTicketRevenueTotal + (int)$stats->total_merch_revenue;

        // Hitung Pendapatan Murni Pajak Layanan Platform (Original - Operational Refund Fee)
        $totalOriginalServiceTax = (int)$stats->ticket_tax_paid + (int)$stats->ticket_tax_refunded;
        $netPlatformRevenue = ($totalOriginalServiceTax + (int)$stats->merch_service_tax) - (int)$stats->refund_tax_spent;

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memuat data dashboard owner secara real-time berdasarkan filter periode',
            'user' => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'role'             => $user->role,
                'avatar'           => $avatarUrl,
                'profile_complete' => (bool) $user->profile_complete,
            ],
            'statistics' => [
                'active_filter'          => $filter,
                'total_revenue'          => $totalGrossRevenue, 
                'total_ticket_revenue'   => $totalTicketRevenueTotal,
                'total_merch_revenue'    => (int) $stats->total_merch_revenue,
                
                'total_service_tax'      => $netPlatformRevenue, // Aman disinkronkan ke Flutter
                'net_platform_revenue'   => $netPlatformRevenue,
                'ticket_service_tax'     => (int)$stats->ticket_tax_paid,
                'merch_service_tax'      => (int)$stats->merch_service_tax,

                'total_tickets_sold'     => (int) $stats->total_tickets_sold,
                'total_merch_sold'       => (int) $stats->total_merch_sold,
                
                'pending_eo'             => (int) $stats->pending_eo,
                'pending_events'         => (int) $stats->pending_events,
                'pending_data_changes'   => (int) $stats->pending_data_changes,
                'pending_withdraws'      => (int) $stats->pending_withdraws,

                'total_users'            => (int) $stats->total_users_count,
                'total_eo'               => (int) $stats->total_eo_count,
                'total_events'           => (int) $stats->total_events_count,
                
                'total_active_events'    => (int) $stats->active_events_count, 
            ]
        ], 200);
    }

    public function getOwnerHistory(Request $request)
    {
        $user = Auth::user();

        // Proteksi Akses Khusus Owner
        if (!$user || $user->role !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Halaman ini khusus Owner.'
            ], 403);
        }

        try {
            // 1. Ambil riwayat EO yang telah di-approve / reject
            $eoLogs = DB::table('eo')
                ->where('status', '!=', 'pending')
                ->select([
                    DB::raw("'eo' as type"),
                    'id',
                    'nama_badan_usaha as title',
                    DB::raw("CONCAT('Status registrasi: ', UPPER(status)) as description"),
                    'rejected_reason',
                    'status',
                    'updated_at as activity_time'
                ]);

            // 2. Ambil riwayat Event yang telah di-approve / reject
            $eventLogs = DB::table('events')
                ->whereNotIn('status', ['pending', 'pending_cancel', 'pending_reschedule'])
                ->select([
                    DB::raw("'event' as type"),
                    'id',
                    'title',
                    DB::raw("CONCAT('Status event: ', UPPER(status)) as description"),
                    'rejected_reason',
                    'status',
                    'updated_at as activity_time'
                ]);

            // Gabungkan base query utama
            $masterQuery = $eoLogs->unionAll($eventLogs);

            // 3. Gabungkan riwayat Withdrawals Tiket (jika tabel tersedia)
            if (Schema::hasTable('withdrawals')) {
                $ticketWithdrawLogs = DB::table('withdrawals')
                    ->join('events', 'withdrawals.event_id', '=', 'events.id')
                    ->where('withdrawals.status', '!=', 'pending')
                    ->select([
                        DB::raw("'withdraw_ticket' as type"),
                        'withdrawals.id',
                        'events.title as title',
                        DB::raw("CONCAT('Withdraw dana tiket senilai Rp ', FORMAT(withdrawals.amount, 0, 'id_ID')) as description"),
                        'withdrawals.owner_note as rejected_reason', 
                        'withdrawals.status',
                        DB::raw("COALESCE(withdrawals.approved_at, withdrawals.updated_at) as activity_time")
                    ]);
                $masterQuery = $masterQuery->unionAll($ticketWithdrawLogs);
            }

            // 4. Gabungkan riwayat Withdrawals Merchandise (jika tabel tersedia)
            if (Schema::hasTable('merch_withdrawals')) {
                $merchWithdrawLogs = DB::table('merch_withdrawals')
                    ->join('events', 'merch_withdrawals.event_id', '=', 'events.id')
                    ->where('merch_withdrawals.status', '!=', 'pending')
                    ->select([
                        DB::raw("'withdraw_merch' as type"),
                        'merch_withdrawals.id',
                        'events.title as title',
                        DB::raw("CONCAT('Withdraw dana merchandise senilai Rp ', FORMAT(merch_withdrawals.amount, 0, 'id_ID')) as description"),
                        'merch_withdrawals.owner_note as rejected_reason', 
                        'merch_withdrawals.status',
                        DB::raw("COALESCE(merch_withdrawals.approved_at, merch_withdrawals.updated_at) as activity_time")
                    ]);
                $masterQuery = $masterQuery->unionAll($merchWithdrawLogs);
            }

            // AMAN: Tarik data ke Collection baru di-sort desc berdasarkan activity_time
            $allLogs = $masterQuery->get()->sortByDesc('activity_time')->values();

            return response()->json([
                'success' => true,
                'message' => 'History kegiatan owner berhasil dimuat.',
                'data' => $allLogs->map(function($log) {
                    return [
                        'type'            => $log->type,
                        'id'              => $log->id,
                        'title'           => $log->title,
                        'description'     => $log->description,
                        'rejected_reason' => $log->rejected_reason ?? '', 
                        'status'          => $log->status,
                        'timestamp'       => $log->activity_time,
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat log history',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function getPlatformRevenueDetail(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $filter = $request->query('filter', 'all');
        $txCondition = "1=1";
        $tmCondition = "1=1";
        $bindings = [];

        switch ($filter) {
            case 'today':
                $txCondition = "DATE(t.paid_time) = CURDATE()";
                $tmCondition = "DATE(tm.paid_time) = CURDATE()";
                break;
            case 'week':
                $txCondition = "t.paid_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $tmCondition = "tm.paid_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $txCondition = "t.paid_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $tmCondition = "tm.paid_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'custom':
                if ($request->has('start_date') && $request->has('end_date')) {
                    $startDate = Carbon::parse($request->query('start_date'))->startOfDay()->toDateTimeString();
                    $endDate = Carbon::parse($request->query('end_date'))->endOfDay()->toDateTimeString();
                    $txCondition = "t.paid_time BETWEEN ? AND ?";
                    $bindings[] = $startDate;
                    $bindings[] = $endDate;
                }
                break;
        }

        try {
            // 🔥 Ambil list detail tiket dengan logika net_service_tax yang lurus dan jujur
            $ticketTransactions = DB::select("
                SELECT 
                    t.kode_unik,
                    e.title as event_name,
                    t.payment_status,
                    t.service_tax as original_service_tax,
                    IF(t.payment_status = 'refunded', COALESCE(r.refunds_tax, 0), 0) as refund_operational_cost,
                    -- Logika net_service_tax disamakan dengan dashboard:
                    -- Jika refunded, service_tax tetap dihitung masuk (karena di dashboard dijumlahkan), namun nanti di total ringkasan dikurangi biaya refund
                    t.service_tax as net_service_tax,
                    IF(t.payment_status = 'refunded', JSON_OBJECT(
                        'status', r.status,
                        'account_name', r.account_name,
                        'bank_name', r.bank_name,
                        'account_number', r.account_number,
                        'grand_total_refunded', r.grand_total_refunded
                    ), NULL) as refund_info,
                    'tiket' as product_type
                FROM transactions t
                JOIN events e ON t.event_id = e.id
                LEFT JOIN refunds r ON t.id = r.transaction_id
                WHERE t.payment_status IN ('paid', 'refunded') AND $txCondition
            ", $bindings);

            // 🔥 Masukkan juga transaksi merchandise (karena omset merch masuk hitungan service_tax dashboard!)
            $merchTransactions = DB::select("
                SELECT 
                    tm.kode_unik,
                    'Pembelian Merchandise' as event_name,
                    tm.payment_status,
                    tm.service_tax as original_service_tax,
                    0 as refund_operational_cost,
                    tm.service_tax as net_service_tax,
                    NULL as refund_info,
                    'merchandise' as product_type
                FROM transaction_merch tm
                WHERE tm.payment_status = 'paid' AND $tmCondition
            ");

            // Gabungkan data tiket & merchandise agar adil sesuai cakupan dashboard
            $allTransactions = array_merge($ticketTransactions, $merchTransactions);

            $formattedData = array_map(function($item) {
                $item->original_service_tax = (int) $item->original_service_tax;
                $item->refund_operational_cost = (int) $item->refund_operational_cost;
                $item->net_service_tax = (int) $item->net_service_tax;
                $item->refund_info = $item->refund_info ? json_decode($item->refund_info, true) : null;
                return $item;
            }, $allTransactions);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil memuat detail transaksi platform',
                'data' => $formattedData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat rincian transaksi: ' . $e->getMessage()
            ], 500);
        }
    }
}
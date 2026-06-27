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
    /**
     * Mengambil data khusus halaman Owner beserta data statistik finansial, volume item terjual,
     * status approval, akumulasi data global, serta jumlah event yang sedang berjalan.
     */
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

        // =====================================================================
        // GENERATE FULL URL AVATAR AGAR BISA DIBACA FLUTTER (GOOGLE AUTH FRIENDLY)
        // =====================================================================
        $avatarUrl = null;
        if ($user->avatar) {
            if (filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                $avatarUrl = $user->avatar;
            } else {
                $avatarUrl = asset($user->avatar); 
            }
        }

        // =====================================================================
        // PROSES FILTER TANGGAL BERDASARKAN PARAMETER FILTER TIME (DENGAN BINDINGS)
        // =====================================================================
        $filter = $request->query('filter', 'all');
        
        $txCondition = "1=1";
        $tmCondition = "1=1";
        $bindings = [];

        switch ($filter) {
            case 'today':
                $txCondition = "DATE(transactions.paid_time) = CURDATE()";
                $tmCondition = "DATE(transaction_merch.paid_time) = CURDATE()";
                break;
            case 'week':
                $txCondition = "transactions.paid_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $tmCondition = "transaction_merch.paid_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $txCondition = "transactions.paid_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $tmCondition = "transaction_merch.paid_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'custom':
                if ($request->has('start_date') && $request->has('end_date')) {
                    $startDate = Carbon::parse($request->query('start_date'))->startOfDay()->toDateTimeString();
                    $endDate = Carbon::parse($request->query('end_date'))->endOfDay()->toDateTimeString();
                    
                    // Menggunakan placeholder ? untuk mencegah SQL Injection
                    $txCondition = "transactions.paid_time BETWEEN ? AND ?";
                    $tmCondition = "transaction_merch.paid_time BETWEEN ? AND ?";
                    
                    // Diisi berulang menyesuaikan jumlah placeholder di sub-query finansial & volume barang
                    $bindings[] = $startDate;
                    $bindings[] = $endDate;
                    $bindings[] = $startDate;
                    $bindings[] = $endDate;
                    $bindings[] = $startDate;
                    $bindings[] = $endDate;
                    $bindings[] = $startDate;
                    $bindings[] = $endDate;
                }
                break;
        }

        // =====================================================================
        // QUERY STATISTIK REAL-TIME (DENGAN KONDISI TANGGAL YANG DINAMIS)
        // =====================================================================
        
        $hasWithdrawals = Schema::hasTable('withdrawals');
        $hasMerchWithdrawals = Schema::hasTable('merch_withdrawals');

        $sqlWithdrawals = $hasWithdrawals ? "(SELECT COUNT(*) FROM withdrawals WHERE status = 'pending')" : "0";
        $sqlMerchWithdrawals = $hasMerchWithdrawals ? "(SELECT COUNT(*) FROM merch_withdrawals WHERE status = 'pending')" : "0";

        // Jalankan raw query dengan bindings parameter yang aman
        $stats = DB::select("
            SELECT 
                -- Finansial Berdasarkan Filter Periode
                (SELECT COALESCE(SUM(grand_total), 0) FROM transactions WHERE payment_status = 'paid' AND $txCondition) AS total_ticket_revenue,
                (SELECT COALESCE(SUM(grand_total), 0) FROM transaction_merch WHERE payment_status = 'paid' AND $tmCondition) AS total_merch_revenue,

                -- Volume Total Kuantitas Item Terjual Berdasarkan Filter Periode
                (SELECT COUNT(*) FROM ticket_attendees 
                 JOIN transactions ON ticket_attendees.transaction_id = transactions.id 
                 WHERE transactions.payment_status = 'paid' AND $txCondition) AS total_tickets_sold,

                (SELECT COALESCE(SUM(tmd.quantity), 0) FROM transaction_merch_details tmd
                 JOIN transaction_merch ON tmd.transaction_merch_id = transaction_merch.id 
                 WHERE transaction_merch.payment_status = 'paid' AND $tmCondition) AS total_merch_sold,

                -- Antrean Approval Real-time (Global/Aktual)
                (SELECT COUNT(*) FROM eo WHERE status = 'pending') AS pending_eo,
                (SELECT COUNT(*) FROM events WHERE status = 'pending') AS pending_events,
                (SELECT COUNT(*) FROM events WHERE status IN ('pending_cancel', 'pending_reschedule')) AS pending_data_changes,
                ($sqlWithdrawals + $sqlMerchWithdrawals) AS pending_withdraws,

                -- Akumulasi Total Data Global (Seluruh Baris)
                (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_users_count,
                (SELECT COUNT(*) FROM eo) AS total_eo_count,
                (SELECT COUNT(*) FROM events) AS total_events_count,

                -- LOGIKANYA DISAMAKAN DENGAN HOMEAPICONTROLLER (Mengecek End Date dari Jadwal / Hari Ini)
                (SELECT COUNT(*) FROM events 
                 WHERE status = 'approved' 
                 AND (
                     -- Kondisi A: Jika punya jadwal, ambil tanggal paling akhir dari tabel jadwal dan pastikan >= hari ini (DATE format)
                     COALESCE(
                         (SELECT MAX(DATE(jadwal.tanggal)) FROM jadwal WHERE jadwal.event_id = events.id), 
                         DATE(events.date)
                     ) >= CURDATE()
                 )
                ) AS active_events_count
        ", $bindings)[0];

        // Hitung total gabungan revenue platform secara dinamis
        $totalRevenue = (int)$stats->total_ticket_revenue + (int)$stats->total_merch_revenue;

        // =====================================================================
        // RESPONSE JSON UNTUK FLUTTER
        // =====================================================================
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
                'active_filter'         => $filter,
                'total_revenue'         => $totalRevenue,
                'total_ticket_revenue'  => (int) $stats->total_ticket_revenue,
                'total_merch_revenue'   => (int) $stats->total_merch_revenue,
                
                'total_tickets_sold'    => (int) $stats->total_tickets_sold,
                'total_merch_sold'      => (int) $stats->total_merch_sold,
                
                'pending_eo'            => (int) $stats->pending_eo,
                'pending_events'        => (int) $stats->pending_events,
                'pending_data_changes'  => (int) $stats->pending_data_changes,
                'pending_withdraws'     => (int) $stats->pending_withdraws,

                'total_users'           => (int) $stats->total_users_count,
                'total_eo'              => (int) $stats->total_eo_count,
                'total_events'          => (int) $stats->total_events_count,
                
                // UBAH KEY INI AGAR SESUAI DENGAN FLUTTER:
                'total_active_events'   => (int) $stats->active_events_count, 
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
}
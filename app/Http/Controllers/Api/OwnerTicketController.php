<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OwnerTicketController extends Controller
{
    /**
     * Mengambil data statistik komprehensif penjualan tiket untuk Owner (Halaman Utama).
     * GET /api/owner/ticket-sales
     */
    public function getTicketSalesData(Request $request)
    {
        $user = Auth::user();

        // Proteksi Hak Akses khusus Owner
        if (!$user || $user->role !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Halaman ini khusus Owner.'
            ], 403);
        }

        try {
            // 1. MENGHITUNG SUMMARY UTAMA
            $summary = DB::table('transactions')
                ->where('payment_status', 'paid')
                ->whereNotNull('event_id')
                ->select([
                    DB::raw('IFNULL(SUM(grand_total), 0) as total_revenue'),
                    DB::raw('COUNT(id) as transaction_count')
                ])
                ->first();

            // Total tiket terjual dari transaksi lunas (paid)
            $ticketsSold = 0;
            if (Schema::hasTable('ticket_attendees')) {
                $ticketsSold = DB::table('ticket_attendees')
                    ->join('transactions', 'ticket_attendees.transaction_id', '=', 'transactions.id')
                    ->where('transactions.payment_status', 'paid')
                    ->count();
            }

            // 2. STATISTIK PER EVENT
            $eventsPerformance = DB::table('events as e')
                ->select([
                    'e.id',
                    'e.title as event_name',
                    DB::raw('IFNULL((SELECT SUM(t.stock) FROM tickets t WHERE t.event_id = e.id), 0) as total_stock'),
                    DB::raw('(
                        SELECT COUNT(ta.id) 
                        FROM ticket_attendees ta
                        JOIN transactions tr ON ta.transaction_id = tr.id
                        JOIN tickets tk ON ta.ticket_id = tk.id
                        WHERE tk.event_id = e.id AND tr.payment_status = \'paid\'
                    ) as total_sold'),
                    DB::raw('IFNULL((
                        SELECT SUM(tr.grand_total) 
                        FROM transactions tr 
                        WHERE tr.event_id = e.id AND tr.payment_status = \'paid\'
                    ), 0) as total_omset')
                ])
                ->orderBy('total_omset', 'desc')
                ->get();

            // 3. RIWAYAT TRANSAKSI TERBARU (DENGAN PROTEKSI KOLOM NYATA)
            $txColumns = Schema::getColumnListing('transactions');
            
            $txSelect = [
                'tr.id',
                'tr.kode_unik',
                'tr.email',
                'tr.grand_total',
                'tr.paid_time',
                'tr.checkout_time',
                'tr.payment_method',
                'tr.payment_status as status',
                'e.title as event_name',
            ];

            // Cek ketersediaan kolom absensi/qr secara dinamis agar tidak memicu query Error 500
            if (in_array('is_registered', $txColumns)) $txSelect[] = 'tr.is_registered';
            if (in_array('registered_at', $txColumns)) $txSelect[] = 'tr.registered_at';
            if (in_array('qr_code', $txColumns)) $txSelect[] = 'tr.qr_code as tx_qr_code';

            $recentTransactions = DB::table('transactions as tr')
                ->leftJoin('events as e', 'tr.event_id', '=', 'e.id')
                ->select($txSelect)
                ->orderBy('tr.id', 'desc') 
                ->limit(50) 
                ->get();

            $recentTransactionsArray = [];

            // Berdasarkan struktur tabel ticket_attendees riil: 'name' dan 'phone_number'
            $hasTableAttendees = Schema::hasTable('ticket_attendees');

            foreach ($recentTransactions as $tx) {
                $attendeesArray = [];

                if ($hasTableAttendees) {
                    $attendees = DB::table('ticket_attendees as ta')
                        ->join('tickets as tk', 'ta.ticket_id', '=', 'tk.id')
                        ->where('ta.transaction_id', $tx->id)
                        ->select([
                            'tk.name as ticket_name', 
                            'tk.price as ticket_price',
                            'ta.name as attendee_name',
                            'ta.phone_number as attendee_phone'
                        ])
                        ->get();

                    $attendeesArray = $attendees->map(function ($attendee) use ($tx) {
                        // Karena tabel ticket_attendees Anda tidak memiliki kolom qr_code, ambil dari transaksi
                        $rawQrData = $tx->tx_qr_code ?? $tx->kode_unik ?? '';

                        return [
                            'attendee_name'  => $attendee->attendee_name ?? $tx->email, 
                            'attendee_phone' => $attendee->attendee_phone ?? '-',
                            'qr_code'        => $rawQrData, 
                            'ticket_name'    => $attendee->ticket_name,
                            'ticket_price'   => (int) $attendee->ticket_price,
                        ];
                    })->all();
                }

                $cleanStatus = strtoupper($tx->status ?? 'UNPAID');
                
                // Fallback teks data QR utama transaksi
                $mainTxQr = $tx->kode_unik;
                if (isset($tx->tx_qr_code) && !empty($tx->tx_qr_code)) {
                    $mainTxQr = $tx->tx_qr_code;
                } elseif (!empty($attendeesArray) && !empty($attendeesArray[0]['qr_code'])) {
                    $mainTxQr = $attendeesArray[0]['qr_code'];
                }

                $recentTransactionsArray[] = [
                    'id'             => $tx->id,
                    'kode_unik'      => $tx->kode_unik,
                    'email'          => $tx->email,
                    'grand_total'    => (int) $tx->grand_total,
                    'paid_time'      => $tx->paid_time,
                    'checkout_time'  => $tx->checkout_time,
                    'payment_method' => $tx->payment_method,
                    'status'         => $cleanStatus,
                    'event_name'     => $tx->event_name,
                    'qr_code'        => $mainTxQr,
                    'is_registered'  => isset($tx->is_registered) ? (int) $tx->is_registered : 0, 
                    'registered_at'  => $tx->registered_at ?? null,              
                    'attendees'      => $attendeesArray
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data analisis penjualan tiket owner',
                'data' => [
                    'summary' => [
                        'total_revenue'     => (int) ($summary->total_revenue ?? 0),
                        'tickets_sold'      => (int) $ticketsSold,
                        'transaction_count' => (int) ($summary->transaction_count ?? 0),
                    ],
                    'events_performance' => $eventsPerformance->map(function ($ev) {
                        return [
                            'id'          => (int) $ev->id,
                            'event_name'  => $ev->event_name,
                            'total_stock' => (int) $ev->total_stock,
                            'total_sold'  => (int) $ev->total_sold,
                            'total_omset' => (int) $ev->total_omset,
                        ];
                    }),
                    'recent_transactions' => $recentTransactionsArray
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal server saat mengolah data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengambil rincian breakdown penjualan per produk tiket (Halaman Detail).
     * GET /api/owner/ticket-sales-summary?event_title=...
     */
    public function getTicketSalesSummary(Request $request)
    {
        $user = Auth::user();

        // Proteksi Hak Akses khusus Owner
        if (!$user || $user->role !== 'owner') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Khusus Owner.'
            ], 403);
        }

        try {
            $eventTitleFilter = $request->query('event_title');

            // Inisialisasi query dasar untuk mengambil breakdown per tiket sesuai struktur database Anda
            $query = DB::table('tickets')
                ->join('events', 'tickets.event_id', '=', 'events.id')
                ->leftJoin('jadwal', 'tickets.jadwal_id', '=', 'jadwal.id')
                ->select([
                    'events.title as event_title',
                    'tickets.id as ticket_id',
                    'tickets.name as ticket_name',
                    'tickets.price as ticket_price',
                    'jadwal.info as schedule_info',
                    DB::raw('(
                        SELECT COUNT(ta.id) 
                        FROM ticket_attendees ta
                        JOIN transactions tr ON ta.transaction_id = tr.id
                        WHERE ta.ticket_id = tickets.id AND tr.payment_status = \'paid\'
                    ) as total_sold'),
                    DB::raw('(
                        SELECT COUNT(ta.id) 
                        FROM ticket_attendees ta
                        JOIN transactions tr ON ta.transaction_id = tr.id
                        WHERE ta.ticket_id = tickets.id AND tr.payment_status = \'paid\'
                    ) * tickets.price as total_revenue')
                ]);

            // Terapkan filter jika memilih event spesifik
            if ($eventTitleFilter && strtolower($eventTitleFilter) !== 'semua event') {
                $query->where('events.title', $eventTitleFilter);
            }

            $salesData = $query->orderBy('events.title', 'asc')
                ->orderBy('tickets.name', 'asc')
                ->get();

            // Hitung akumulasi grand total untuk statistik atas screen detail
            $grandTotalSold = $salesData->sum('total_sold');
            $grandTotalRevenue = $salesData->sum('total_revenue');

            return response()->json([
                'status' => 'success',
                'message' => 'Data rincian jenis produk tiket berhasil dimuat.',
                'grand_total_sold' => (int) $grandTotalSold,
                'grand_total_revenue' => (int) $grandTotalRevenue,
                'data' => $salesData->map(function($item) {
                    return [
                        'event_title' => $item->event_title,
                        'ticket_name' => $item->ticket_name,
                        'schedule_info' => $item->schedule_info ?? 'Tanpa Jadwal',
                        'price' => (int) $item->ticket_price,
                        'total_sold' => (int) $item->total_sold,
                        'total_revenue' => (int) $item->total_revenue,
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat rincian penjualan produk tiket: ' . $e->getMessage()
            ], 500);
        }
    }
}
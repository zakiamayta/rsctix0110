<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
// 🔄 FIX SALDO REAL-TIME: dipakai untuk memaksa recompute event_wallets & merch_wallets
// (lihat catatan lengkap di getRealRevenue() di bawah)
use App\Http\Controllers\Api\EOTicketController;
use App\Http\Controllers\Api\EOMerchController;

class EODashboardController extends Controller
{
    /**
     * 🔥 UTAMA: Mengambil data ringkasan untuk Dashboard EO
     * Route: GET /api/eo/dashboard
     */
    public function getDashboardData(Request $request)
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
                        'eo_id' => null,
                        'eo_name' => 'Event Organizer',
                        'eo_status' => 'pending',
                        'penanggung_jawab' => null,
                        'logo' => null,
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
                        'pending_reschedule_events' => 0,
                        'pending_cancel_events' => 0,
                        'reschedule_rejected_events' => 0,
                        'cancelled_events' => 0,
                        'events' => [],
                    ]
                ]);
            }

            $eoId = $eo->id;

            /// 🔥 AMBIL SEMUA ID EVENT MILIK EO INI
            $eventIds = DB::table('events')->where('eo_id', $eoId)->pluck('id');

            /// 🎫 TOTAL REAL TIKET TERJUAL (Menghitung jumlah attendee dari transaksi yang 'paid')
            $totalTickets = DB::table('ticket_attendees')
                ->join('transactions', 'transactions.id', '=', 'ticket_attendees.transaction_id')
                ->whereIn('transactions.event_id', $eventIds)
                ->where('transactions.payment_status', 'paid')
                ->count();

            /// 📦 HITUNG JUMLAH QUANTITY MERCHANDISE TERJUAL
            $totalMerch = DB::table('transaction_merch_details')
                ->join('transaction_merch', 'transaction_merch.id', '=', 'transaction_merch_details.transaction_merch_id')
                ->whereIn('transaction_merch_details.product_id', function($query) use ($eoId) {
                    $query->select('id')->from('products')->whereIn('event_id', function($sub) use ($eoId) {
                        $sub->select('id')->from('events')->where('eo_id', $eoId);
                    });
                })
                ->where('transaction_merch.payment_status', 'paid')
                ->sum('transaction_merch_details.quantity'); 

            /// 💰 1. HITUNG PENDAPATAN TIKET UTAMA (Menggunakan total_amount sebelum service tax platform)
            $totalTicketRevenue = DB::table('transactions')
                ->whereIn('event_id', $eventIds)
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            /// 🛍️ 2. HITUNG PENDAPATAN MERCHANDISE 
            $totalMerchRevenue = DB::table('transaction_merch_details')
                ->join('transaction_merch', 'transaction_merch.id', '=', 'transaction_merch_details.transaction_merch_id')
                ->whereIn('transaction_merch_details.product_id', function($query) use ($eoId) {
                    $query->select('id')->from('products')->whereIn('event_id', function($sub) use ($eoId) {
                        $sub->select('id')->from('events')->where('eo_id', $eoId);
                    });
                })
                ->where('transaction_merch.payment_status', 'paid')
                ->sum('transaction_merch_details.subtotal');

            /// 💵 3. AKUMULASI GABUNGAN TOTAL REVENUE
            $totalRevenue = $totalTicketRevenue + $totalMerchRevenue;

            /// 📅 PENJUALAN HARI INI (Invoice Tiket + Invoice Merch Terbayar Hari Ini)
            $todayTicketSales = DB::table('transactions')
                ->whereIn('event_id', $eventIds)
                ->where('payment_status', 'paid')
                ->whereDate('created_at', Carbon::today())
                ->count();

            $todayMerchSales = DB::table('transaction_merch')
                ->whereIn('id', function($query) use ($eoId) {
                    $query->select('transaction_merch_id')
                          ->from('transaction_merch_details')
                          ->join('products', 'products.id', '=', 'transaction_merch_details.product_id')
                          ->join('events', 'events.id', '=', 'products.event_id')
                          ->where('events.eo_id', $eoId);
                })
                ->where('payment_status', 'paid')
                ->whereDate('created_at', Carbon::today())
                ->count();

            $todaySales = $todayTicketSales + $todayMerchSales;

            /// 🎯 COUNTER STATISTIK STATUS EVENT (Sesuai Enum DB & UI Flutter)
            $approvedEvents = DB::table('events')->where('eo_id', $eoId)->where('status', 'approved')->whereNull('reschedule_rejected_reason')->count();
            $pendingEvents = DB::table('events')->where('eo_id', $eoId)->where('status', 'pending')->count();
            $rejectedEvents = DB::table('events')->where('eo_id', $eoId)->where('status', 'rejected')->count();
            $pendingRescheduleEvents = DB::table('events')->where('eo_id', $eoId)->where('status', 'pending_reschedule')->count();
            $pendingCancelEvents = DB::table('events')->where('eo_id', $eoId)->where('status', 'pending_cancel')->count();
            $cancelledEvents = DB::table('events')->where('eo_id', $eoId)->where('status', 'cancelled')->count();
            $rescheduleRejectedEvents = DB::table('events')->where('eo_id', $eoId)->where('status', 'approved')->whereNotNull('reschedule_rejected_reason')->count();
        
            /// 🚀 EVENT AKTIF Berjalan (Approved/Reschedule yang tanggalnya belum lewat hari ini)
            $activeEvents = DB::table('events')
                ->leftJoin('jadwal', 'events.id', '=', 'jadwal.event_id')
                ->where('events.eo_id', $eoId)
                ->whereIn('events.status', ['approved', 'pending_reschedule'])
                ->where(function($query) {
                    $query->whereDate('jadwal.tanggal', '>=', Carbon::today())
                          ->orWhere(function($sub) {
                              $sub->whereNull('jadwal.tanggal')
                                  ->whereDate('events.date', '>=', Carbon::today());
                          });
                })
                ->distinct('events.id')
                ->count();

            /// 🎫 LIST EVENT UNTUK LIVE PREVIEW DASHBOARD
            $events = DB::table('events')
                ->where('events.eo_id', $eoId)
                ->select(
                    'events.id',
                    'events.title',
                    'events.date',
                    'events.proposed_date', 
                    'events.status',
                    'events.poster',
                    'events.reschedule_reason',
                    'events.reschedule_rejected_reason',
                    'events.created_at'
                )
                ->orderByDesc('events.created_at')
                ->get();

            /// ✨ TRANSFORM DATA AGAR AMAN DI FLUTTER (No Null / No Type Mismatch)
            $events->transform(function ($event) {
                // Formatting Poster URL
                if ($event->poster) {
                    $event->poster = filter_var($event->poster, FILTER_VALIDATE_URL) 
                        ? $event->poster 
                        : Storage::url($event->poster);
                } else {
                    $event->poster = null;
                }
                
                // Cari real end date dari max jadwal tanggal
                $maxJadwal = DB::table('jadwal')->where('event_id', $event->id)->max('tanggal');
                
                $event->date = $event->date ? Carbon::parse($event->date)->toDateString() : Carbon::today()->toDateString();
                $event->proposed_date = $event->proposed_date ? Carbon::parse($event->proposed_date)->toDateString() : null;
                $event->end_date = $maxJadwal ? Carbon::parse($maxJadwal)->toDateString() : $event->date;
                $event->real_end_date = $event->end_date;

                // Hitung tiket terjual (sold) riil per event id ini
                $event->sold = (int) DB::table('ticket_attendees')
                    ->join('transactions', 'transactions.id', '=', 'ticket_attendees.transaction_id')
                    ->where('transactions.event_id', $event->id)
                    ->where('transactions.payment_status', 'paid')
                    ->count();

                // Hitung revenue tiket per event ini
                $event->revenue = (int) DB::table('transactions')
                    ->where('event_id', $event->id)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');
                
                return $event;
            });

            // Formatting EO Logo URL jika ada
            $eoLogo = $eo->logo ? (filter_var($eo->logo, FILTER_VALIDATE_URL) ? $eo->logo : Storage::url($eo->logo)) : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'eo_id' => $eo->id,
                    'eo_name' => $eo->nama_badan_usaha,
                    'eo_status' => $eo->status,
                    'penanggung_jawab' => $eo->penanggung_jawab,
                    'logo' => $eoLogo,

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
                    'pending_reschedule_events' => (int) $pendingRescheduleEvents, 
                    'pending_cancel_events' => (int) $pendingCancelEvents,
                    'reschedule_rejected_events' => (int) $rescheduleRejectedEvents,
                    'cancelled_events' => (int) $cancelledEvents,

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

    /**
     * 🔄 ENDPOINT TAMBAHAN: Mengambil list event aktif untuk ActiveEventsScreen Flutter
     * Route: GET /api/eo/active-events
     */
    /**
     * 🔄 ENDPOINT TAMBAHAN: Mengambil list event aktif untuk ActiveEventsScreen Flutter
     * Route: GET /api/eo/active-events
     *
     * 🛠️ PERBAIKAN BUG:
     * 1. Kolom tanggal sebelumnya di-alias jadi `start_date`, padahal Flutter
     *    membaca `event['date']` -> tanggal selalu tampil "-" di UI lama.
     *    Sekarang dikirim sebagai `date` (konsisten dengan getDashboardData).
     * 2. `->distinct('events.id')` pada query builder Laravel untuk MySQL TIDAK
     *    melakukan DISTINCT per-kolom (beda dgn Postgres DISTINCT ON) -> berpotensi
     *    duplikat baris saat 1 event punya banyak baris `jadwal`. Diperbaiki dengan
     *    mengambil ID unik dulu (pluck + distinct), baru query detail per event.
     * 3. Ditambahkan statistik ringkas (total tiket terjual, total pendapatan,
     *    penjualan hari ini) baik secara agregat maupun per event, sesuai skema
     *    tabel `transactions` & `ticket_attendees`.
     */
    public function getActiveEvents(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $eo = DB::table('eo')->where('user_id', $user->id)->first();
            if (!$eo) {
                return response()->json(['success' => false, 'message' => 'EO tidak ditemukan'], 404);
            }

            $eoId = $eo->id;

            /// 🔎 Ambil ID event aktif (approved / pending_reschedule & belum lewat tanggal)
            $activeEventIds = DB::table('events')
                ->leftJoin('jadwal', 'events.id', '=', 'jadwal.event_id')
                ->where('events.eo_id', $eoId)
                ->whereIn('events.status', ['approved', 'pending_reschedule'])
                ->where(function ($query) {
                    $query->whereDate('jadwal.tanggal', '>=', Carbon::today())
                          ->orWhere(function ($sub) {
                              $sub->whereNull('jadwal.tanggal')
                                  ->whereDate('events.date', '>=', Carbon::today());
                          });
                })
                ->distinct()
                ->pluck('events.id');

            /// 📋 Ambil detail event berdasarkan ID unik di atas
            $events = DB::table('events')
                ->whereIn('events.id', $activeEventIds)
                ->select(
                    'events.id',
                    'events.title',
                    'events.date',
                    'events.status',
                    'events.poster',
                    DB::raw("COALESCE(events.location, 'Lokasi Belum Ditentukan') as location")
                )
                ->orderBy('events.date', 'asc')
                ->get();

            $totalTicketsSold = 0;
            $totalRevenue = 0;
            $todaySoldCount = 0;
            $todayRevenue = 0;

            $events->transform(function ($event) use (&$totalTicketsSold, &$totalRevenue, &$todaySoldCount, &$todayRevenue) {
                // Poster URL
                $event->poster = $event->poster
                    ? (filter_var($event->poster, FILTER_VALIDATE_URL) ? $event->poster : Storage::url($event->poster))
                    : null;

                // Tanggal mulai (key `date`, dipakai langsung oleh Flutter)
                $event->date = $event->date ? Carbon::parse($event->date)->toIso8601String() : null;

                // Tanggal akhir riil, diambil dari jadwal terakhir (jika ada)
                $maxJadwal = DB::table('jadwal')->where('event_id', $event->id)->max('tanggal');
                $event->end_date = $maxJadwal ? Carbon::parse($maxJadwal)->toIso8601String() : $event->date;

                // 🎟️ Statistik tiket terjual (attendee dari transaksi paid) per event
                $sold = (int) DB::table('ticket_attendees')
                    ->join('transactions', 'transactions.id', '=', 'ticket_attendees.transaction_id')
                    ->where('transactions.event_id', $event->id)
                    ->where('transactions.payment_status', 'paid')
                    ->count();

                // 💰 Pendapatan tiket per event
                $revenue = (int) DB::table('transactions')
                    ->where('event_id', $event->id)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');

                // 📅 Penjualan & pendapatan hari ini khusus event ini
                $todaySold = (int) DB::table('transactions')
                    ->where('event_id', $event->id)
                    ->where('payment_status', 'paid')
                    ->whereDate('created_at', Carbon::today())
                    ->count();

                $todayRev = (int) DB::table('transactions')
                    ->where('event_id', $event->id)
                    ->where('payment_status', 'paid')
                    ->whereDate('created_at', Carbon::today())
                    ->sum('total_amount');

                $event->sold = $sold;
                $event->revenue = $revenue;
                $event->today_sold = $todaySold;
                $event->today_revenue = $todayRev;

                $totalTicketsSold += $sold;
                $totalRevenue += $revenue;
                $todaySoldCount += $todaySold;
                $todayRevenue += $todayRev;

                return $event;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_active_events' => $events->count(),
                        'total_tickets_sold' => $totalTicketsSold,
                        'total_revenue' => $totalRevenue,
                        'today_sales_count' => $todaySoldCount,
                        'today_sales_revenue' => $todayRevenue,
                    ],
                    'events' => $events,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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

        $eo = DB::table('eo')->where('user_id', $user->id)->first();

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
                $item->poster = filter_var($item->poster, FILTER_VALIDATE_URL) ? $item->poster : Storage::url($item->poster);
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
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $eo = DB::table('eo')->where('user_id', $user->id)->first();
        if (!$eo) {
            return response()->json(['success' => false, 'message' => 'EO tidak ditemukan'], 403);
        }

        $transaction = DB::table('transactions')
            ->join('events', 'events.id', '=', 'transactions.event_id')
            ->where('transactions.id', $id)
            ->where('events.eo_id', $eo->id) 
            ->select('transactions.*', 'events.title as event_title')
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
            ->select('ticket_attendees.name', 'ticket_attendees.phone_number', 'tickets.name as ticket_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'transaction' => [
                    'id' => $transaction->id,
                    'kode_unik' => $transaction->kode_unik ?? '-', 
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
        $user = $request->user(); 
        $token = Str::random(40);
        Cache::put('web_autologin_' . $token, $user->id, now()->addMinutes(2));

        return response()->json([
            'success' => true,
            'token' => $token 
        ]);
    }

    public function getSalesRecap(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $eo = DB::table('eo')->where('user_id', $user->id)->first();
            if (!$eo) {
                return response()->json([
                    'success' => true,
                    'data' => ['tickets' => [], 'merch' => []]
                ], 200);
            }

            $eoId = $eo->id;

            // --- 1. DATA TIKET + INFO JADWAL ---
            $ticketsData = DB::table('tickets')
                ->join('events', 'tickets.event_id', '=', 'events.id')
                ->leftJoin('jadwal', 'tickets.jadwal_id', '=', 'jadwal.id')
                ->where('events.eo_id', $eoId)
                ->select(
                    'tickets.id',
                    'tickets.event_id',
                    'events.title as event_title',
                    'tickets.name as item_name',
                    'tickets.stock as remaining_stock',
                    'tickets.jadwal_id',
                    DB::raw("COALESCE(jadwal.info, 'Jadwal Umum') as jadwal_info")
                )
                ->groupBy('tickets.id', 'tickets.event_id', 'events.title', 'tickets.name', 'tickets.stock', 'tickets.jadwal_id', 'jadwal.info')
                ->get();

            foreach ($ticketsData as $ticket) {
                $buyers = DB::table('transactions')
                    ->where('transactions.event_id', $ticket->event_id)
                    ->where('transactions.payment_status', 'paid')
                    ->where(function($query) use ($ticket) {
                        if ($ticket->jadwal_id) {
                            $query->where('transactions.jadwal_id', $ticket->jadwal_id);
                        }
                    })
                    ->select(
                        'transactions.id as transaction_id',
                        'transactions.email as buyer_email',
                        'transactions.kode_unik as order_code',
                        'transactions.qr_code as qr_data', 
                        'transactions.is_registered',
                        'transactions.registered_at',
                        'transactions.jadwal_id'
                    )
                    ->get();

                foreach ($buyers as $buyer) {
                    $buyer->attendees = DB::table('ticket_attendees')
                        ->where('transaction_id', $buyer->transaction_id)
                        ->where('ticket_id', $ticket->id)
                        ->select('name', 'phone_number')
                        ->get();
                }

                $ticket->sold = count($buyers);
                $ticket->buyers = $buyers;
            }

            // --- 2. DATA MERCHANDISE ---
            $merchData = DB::table('products_ukuran')
                ->join('products_varian', 'products_ukuran.varian_id', '=', 'products_varian.id')
                ->join('products', 'products_varian.product_id', '=', 'products.id')
                ->join('events', 'products.event_id', '=', 'events.id')
                ->where('events.eo_id', $eoId)
                ->select(
                    'products_ukuran.id',
                    'events.title as event_title',
                    DB::raw("CONCAT(products.name, ' (', products_varian.varian, ' - ', products_ukuran.ukuran, ')') as item_name"),
                    'products_ukuran.stok as remaining_stock'
                )
                ->groupBy('products_ukuran.id', 'events.title', 'products.name', 'products_varian.varian', 'products_ukuran.ukuran', 'products_ukuran.stok')
                ->get();

            foreach ($merchData as $merch) {
                $buyers = DB::table('transaction_merch_details')
                    ->join('transaction_merch', 'transaction_merch_details.transaction_merch_id', '=', 'transaction_merch.id')
                    ->where('transaction_merch_details.ukuran_id', $merch->id)
                    ->where('transaction_merch.payment_status', 'paid')
                    ->select(
                        'transaction_merch.email as buyer_email',
                        'transaction_merch_details.buyer_name',
                        'transaction_merch_details.buyer_phone',
                        'transaction_merch.kode_unik as order_code',
                        'transaction_merch.qr_code as qr_data', 
                        'transaction_merch_details.quantity'
                    )->get();

                $merch->sold = $buyers->sum('quantity');
                $merch->buyers = $buyers;
            }

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diambil',
                'data' => [
                    'tickets' => $ticketsData,
                    'merch' => $merchData
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 💰 OMSET RIIL / SALDO REAL EO (real-time)
     * Route: GET /api/eo/real-revenue
     *
     * Berbeda dari getDashboardData() yang menampilkan OMSET KOTOR
     * (total_amount sebelum potongan), endpoint ini menampilkan SALDO RIIL
     * yang sudah dipotong refund & penarikan (withdrawal), diambil langsung
     * dari tabel `event_wallets` & `merch_wallets` (sumber kebenaran saldo).
     *
     * 🛠️ DEBUG-FRIENDLY VERSION:
     * - Setiap blok query dibungkus try/catch TERPISAH supaya kalau satu
     *   tabel bermasalah (mis. `withdrawals` belum ke-migrate), bagian lain
     *   tetap bisa dikembalikan, dan errornya masuk ke `warnings[]` alih-alih
     *   membuat SELURUH endpoint gagal (500) dengan pesan generik.
     * - Semua exception dicatat ke storage/logs/laravel.log via Log::error()
     *   lengkap dengan trace, supaya bisa dilihat real errornya di server.
     * - Saat APP_DEBUG=true, response error menyertakan pesan asli exception
     *   + nama file & baris, supaya gampang di-debug dari Flutter tanpa perlu
     *   buka log server.
     */
    public function getRealRevenue(Request $request)
    {
        $warnings = [];

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $eo = DB::table('eo')->where('user_id', $user->id)->first();
            if (!$eo) {
                return response()->json(['success' => false, 'message' => 'EO tidak ditemukan'], 404);
            }

            $eoId = $eo->id;
            $eventIds = DB::table('events')->where('eo_id', $eoId)->pluck('id');

            /// 🛠️ FIX BUG SALDO TIDAK REAL-TIME:
            /// Sebelumnya endpoint ini HANYA membaca `available_balance`/`held_balance`/
            /// `negative_balance` apa adanya dari `event_wallets` & `merch_wallets`, tanpa
            /// pernah menghitung ulang. Kolom-kolom itu sendiri hanya ter-update sebagai
            /// EFEK SAMPING ketika EO membuka halaman Tarik Dana Tiket/Merch (yang memanggil
            /// EOTicketController::eventWallets() / EOMerchController::merchWallets()).
            /// Akibatnya, setelah transaksi baru / refund / withdrawal terjadi, saldo di
            /// layar Omset Riil tetap diam sampai EO kebetulan membuka halaman withdrawal.
            ///
            /// Perbaikannya: paksa recompute di sini, SEBELUM dibaca, dengan memanggil
            /// method recompute yang SUDAH ADA di controller lain (bukan duplikasi logika
            /// plafon/H-10/skala event yang kompleks). Efek sampingnya menulis ulang
            /// `event_wallets`/`merch_wallets` dengan angka terbaru; JSON response dari
            /// kedua method itu sendiri tidak dipakai di sini, hanya efek tulisnya.
            try {
                app(EOTicketController::class)->eventWallets($eoId);
            } catch (\Throwable $e) {
                $warnings[] = 'sync event_wallets: ' . $e->getMessage();
                Log::error('[RealRevenue] Gagal sinkronisasi event_wallets: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }

            try {
                app(EOMerchController::class)->merchWallets($eoId);
            } catch (\Throwable $e) {
                $warnings[] = 'sync merch_wallets: ' . $e->getMessage();
                Log::error('[RealRevenue] Gagal sinkronisasi merch_wallets: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }

            /// 🧾 1. SALDO WALLET TIKET (setelah baris di atas, datanya sudah fresh)
            $ticketWallets = collect();
            try {
                $ticketWallets = DB::table('event_wallets')
                    ->join('events', 'events.id', '=', 'event_wallets.event_id')
                    ->where('event_wallets.eo_id', $eoId)
                    ->select(
                        'event_wallets.event_id',
                        'events.title',
                        'event_wallets.available_balance',
                        'event_wallets.held_balance',
                        'event_wallets.negative_balance',
                        'event_wallets.withdraw_locked',
                        'event_wallets.updated_at'
                    )
                    ->get();
            } catch (\Throwable $e) {
                $warnings[] = 'event_wallets: ' . $e->getMessage();
                Log::error('[RealRevenue] event_wallets query failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }

            /// 🧾 2. SALDO WALLET MERCH
            $merchWallets = collect();
            try {
                $merchWallets = DB::table('merch_wallets')
                    ->join('events', 'events.id', '=', 'merch_wallets.event_id')
                    ->where('merch_wallets.eo_id', $eoId)
                    ->select(
                        'merch_wallets.event_id',
                        'events.title',
                        'merch_wallets.available_balance',
                        'merch_wallets.held_balance',
                        'merch_wallets.negative_balance',
                        'merch_wallets.withdraw_locked',
                        'merch_wallets.updated_at'
                    )
                    ->get();
            } catch (\Throwable $e) {
                $warnings[] = 'merch_wallets: ' . $e->getMessage();
                Log::error('[RealRevenue] merch_wallets query failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }

            $ticketAvailable = (int) $ticketWallets->sum('available_balance');
            $ticketHeld = (int) $ticketWallets->sum('held_balance');
            $ticketNegative = (int) $ticketWallets->sum('negative_balance');

            $merchAvailable = (int) $merchWallets->sum('available_balance');
            $merchHeld = (int) $merchWallets->sum('held_balance');
            $merchNegative = (int) $merchWallets->sum('negative_balance');

            /// 💵 SALDO RIIL GABUNGAN (sudah kepotong refund, BISA MINUS)
            $totalAvailable = $ticketAvailable + $merchAvailable;
            $totalHeld = $ticketHeld + $merchHeld;
            $totalNegative = $ticketNegative + $merchNegative;
            $isNegative = $totalAvailable < 0 || $totalNegative > 0;

            /// 💸 3. TOTAL SUDAH DITARIK (withdrawal disetujui)
            $ticketWithdrawn = 0;
            if (Schema::hasTable('withdrawals')) {
                try {
                    $ticketWithdrawn = (int) DB::table('withdrawals')
                        ->where('eo_id', $eoId)
                        ->where('status', 'approved')
                        ->sum('amount');
                } catch (\Throwable $e) {
                    $warnings[] = 'withdrawals: ' . $e->getMessage();
                    Log::error('[RealRevenue] withdrawals query failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                }
            } else {
                $warnings[] = "Tabel 'withdrawals' tidak ditemukan di database (belum migrate?). total_withdrawn tiket = 0.";
                Log::warning("[RealRevenue] Tabel 'withdrawals' tidak ditemukan.");
            }

            $merchWithdrawn = 0;
            if (Schema::hasTable('merch_withdrawals')) {
                try {
                    $merchWithdrawn = (int) DB::table('merch_withdrawals')
                        ->where('eo_id', $eoId)
                        ->where('status', 'approved')
                        ->sum('amount');
                } catch (\Throwable $e) {
                    $warnings[] = 'merch_withdrawals: ' . $e->getMessage();
                    Log::error('[RealRevenue] merch_withdrawals query failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                }
            } else {
                $warnings[] = "Tabel 'merch_withdrawals' tidak ditemukan di database.";
                Log::warning("[RealRevenue] Tabel 'merch_withdrawals' tidak ditemukan.");
            }

            /// 🔁 4. STATISTIK REFUND (tiket + merch), dikelompokkan per status
            $refundStats = [
                'waiting'  => ['count' => 0, 'amount' => 0],
                'pending'  => ['count' => 0, 'amount' => 0],
                'refunded' => ['count' => 0, 'amount' => 0],
                'rejected' => ['count' => 0, 'amount' => 0],
            ];

            try {
                // Refund tiket -> transaksi tiket milik event EO ini
                $ticketRefundRows = DB::table('refunds')
                    ->whereNotNull('refunds.transaction_id')
                    ->whereIn('refunds.transaction_id', function ($q) use ($eventIds) {
                        $q->select('id')->from('transactions')->whereIn('event_id', $eventIds);
                    })
                    ->select('status', DB::raw('COUNT(*) as cnt'), DB::raw('COALESCE(SUM(grand_total_refunded),0) as amt'))
                    ->groupBy('status')
                    ->get();

                // Refund merch -> transaksi merch yang produknya milik event EO ini
                $merchRefundRows = DB::table('refunds')
                    ->whereNotNull('refunds.transaction_merch_id')
                    ->whereIn('refunds.transaction_merch_id', function ($q) use ($eventIds) {
                        $q->select('transaction_merch_details.transaction_merch_id')
                            ->from('transaction_merch_details')
                            ->join('products', 'products.id', '=', 'transaction_merch_details.product_id')
                            ->whereIn('products.event_id', $eventIds);
                    })
                    ->select('status', DB::raw('COUNT(*) as cnt'), DB::raw('COALESCE(SUM(grand_total_refunded),0) as amt'))
                    ->groupBy('status')
                    ->get();

                foreach ($ticketRefundRows->merge($merchRefundRows) as $row) {
                    if (!isset($refundStats[$row->status])) {
                        $refundStats[$row->status] = ['count' => 0, 'amount' => 0];
                    }
                    $refundStats[$row->status]['count'] += (int) $row->cnt;
                    $refundStats[$row->status]['amount'] += (int) $row->amt;
                }
            } catch (\Throwable $e) {
                $warnings[] = 'refunds: ' . $e->getMessage();
                Log::error('[RealRevenue] refunds query failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }

            $totalRefundRequests = array_sum(array_column($refundStats, 'count'));
            $totalRefundedAmount = $refundStats['refunded']['amount'];
            $totalPendingRefundAmount = $refundStats['waiting']['amount'] + $refundStats['pending']['amount'];

            /// 📋 5. DETAIL PER EVENT
            $perEvent = [];
            try {
                foreach ($eventIds as $evId) {
                    $tw = $ticketWallets->firstWhere('event_id', $evId);
                    $mw = $merchWallets->firstWhere('event_id', $evId);

                    if (!$tw && !$mw) continue;

                    $title = $tw->title ?? ($mw->title ?? DB::table('events')->where('id', $evId)->value('title'));

                    $perEvent[] = [
                        'event_id' => $evId,
                        'title' => $title,
                        'ticket_available' => (int) ($tw->available_balance ?? 0),
                        'ticket_held' => (int) ($tw->held_balance ?? 0),
                        'ticket_negative' => (int) ($tw->negative_balance ?? 0),
                        'merch_available' => (int) ($mw->available_balance ?? 0),
                        'merch_held' => (int) ($mw->held_balance ?? 0),
                        'merch_negative' => (int) ($mw->negative_balance ?? 0),
                        'withdraw_locked' => (bool) (($tw->withdraw_locked ?? false) || ($mw->withdraw_locked ?? false)),
                    ];
                }
            } catch (\Throwable $e) {
                $warnings[] = 'per_event: ' . $e->getMessage();
                Log::error('[RealRevenue] per_event build failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }

            if (!empty($warnings)) {
                Log::warning('[RealRevenue] Selesai dengan warning(s)', ['eo_id' => $eoId, 'warnings' => $warnings]);
            }

            return response()->json([
                'success' => true,
                'warnings' => $warnings, // 👈 kalau tidak kosong, cek isi ini duluan saat debug
                'data' => [
                    'eo_id' => $eoId,
                    'eo_name' => $eo->nama_badan_usaha,
                    'server_time' => now()->toIso8601String(),

                    'is_negative' => $isNegative,
                    'total_available_balance' => $totalAvailable,
                    'total_held_balance' => $totalHeld,
                    'total_negative_balance' => $totalNegative,

                    'ticket_wallet' => [
                        'available_balance' => $ticketAvailable,
                        'held_balance' => $ticketHeld,
                        'negative_balance' => $ticketNegative,
                    ],
                    'merch_wallet' => [
                        'available_balance' => $merchAvailable,
                        'held_balance' => $merchHeld,
                        'negative_balance' => $merchNegative,
                    ],

                    'total_withdrawn' => $ticketWithdrawn + $merchWithdrawn,
                    'ticket_withdrawn' => $ticketWithdrawn,
                    'merch_withdrawn' => $merchWithdrawn,

                    'refund_stats' => $refundStats,
                    'total_refund_requests' => $totalRefundRequests,
                    'total_refunded_amount' => $totalRefundedAmount,
                    'total_pending_refund_amount' => $totalPendingRefundAmount,

                    'per_event' => $perEvent,
                ],
            ], 200);

        } catch (\Throwable $e) {
            // 🔥 Ini exception FATAL yang bikin seluruh endpoint gagal.
            // Dicatat lengkap ke laravel.log supaya bisa dicek `tail -f storage/logs/laravel.log`.
            Log::error('[RealRevenue] FATAL: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $payload = [
                'success' => false,
                'message' => 'Gagal memuat data omset riil: ' . $e->getMessage(),
            ];

            // Saat APP_DEBUG=true, sertakan detail teknis biar gampang dilacak dari Flutter.
            if (config('app.debug')) {
                $payload['debug'] = [
                    'exception' => get_class($e),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }

            return response()->json($payload, 500);
        }
    }
}
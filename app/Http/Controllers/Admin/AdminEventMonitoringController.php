<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminEventMonitoringController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || auth()->user()->role !== 'admin') {
                abort(403, 'Akses hanya untuk Admin.');
            }
            return $next($request);
        });
    }

    /**
     * 📋 Halaman 1: Daftar semua EO + ringkasan cepat per EO + statistik platform
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $statusFilter = in_array($request->input('status'), ['approved', 'pending', 'rejected'])
            ? $request->input('status')
            : null;
        $sortBy = in_array($request->input('sort'), ['name', 'gmv', 'debt', 'balance'])
            ? $request->input('sort')
            : 'name';

        $eoList = DB::table('eo')
            ->when($search, fn ($q) => $q->where('eo.nama_badan_usaha', 'like', "%{$search}%"))
            ->when($statusFilter, fn ($q) => $q->where('eo.status', $statusFilter))
            ->select([
                'eo.id',
                'eo.nama_badan_usaha',
                'eo.logo',
                'eo.status',
                'eo.created_at',
                DB::raw("(SELECT COUNT(*) FROM events WHERE events.eo_id = eo.id) as total_event"),
                DB::raw("(SELECT COUNT(*) FROM events WHERE events.eo_id = eo.id AND events.status = 'approved') as total_event_approved"),
                DB::raw("(
                    SELECT COALESCE(SUM(t.grand_total), 0)
                    FROM transactions t
                    JOIN events e ON e.id = t.event_id
                    WHERE e.eo_id = eo.id AND t.payment_status = 'paid'
                ) as ticket_gmv"),
                DB::raw("(
                    SELECT COALESCE(SUM(tm.grand_total), 0)
                    FROM transaction_merch tm
                    JOIN events e ON e.id = tm.event_id
                    WHERE e.eo_id = eo.id AND tm.payment_status = 'paid'
                ) as merch_gmv"),
                DB::raw("(
                    SELECT COALESCE(SUM(ew.available_balance + ew.held_balance), 0)
                    FROM event_wallets ew
                    JOIN events e ON e.id = ew.event_id
                    WHERE e.eo_id = eo.id
                ) as wallet_balance"),
                DB::raw("(
                    SELECT COALESCE(SUM(ed.remaining_debt), 0)
                    FROM eo_debts ed
                    WHERE ed.eo_id = eo.id AND ed.status != 'paid'
                ) as outstanding_debt"),
                 DB::raw("(
                    SELECT COALESCE(SUM(t.total_amount), 0)
                    FROM refunds r
                    JOIN transactions t ON t.id = r.transaction_id
                    JOIN events e2 ON e2.id = t.event_id
                    WHERE e2.eo_id = eo.id AND r.status IN ('waiting', 'pending')
                ) as ticket_pending_refund"),
                DB::raw("(
                    SELECT COALESCE(SUM(tm.total_amount), 0)
                    FROM refunds r
                    JOIN transaction_merch tm ON tm.id = r.transaction_merch_id
                    JOIN events e2 ON e2.id = tm.event_id
                    WHERE e2.eo_id = eo.id AND r.status IN ('waiting', 'pending')
                ) as merch_pending_refund"),
                DB::raw("(
                    SELECT COALESCE(SUM(r.grand_total_refunded), 0)
                    FROM refunds r
                    JOIN transactions t ON t.id = r.transaction_id
                    JOIN events e2 ON e2.id = t.event_id
                    WHERE e2.eo_id = eo.id AND r.status = 'refunded'
                ) as ticket_refunded_out"),
                DB::raw("(
                    SELECT COALESCE(SUM(r.grand_total_refunded), 0)
                    FROM refunds r
                    JOIN transaction_merch tm ON tm.id = r.transaction_merch_id
                    JOIN events e2 ON e2.id = tm.event_id
                    WHERE e2.eo_id = eo.id AND r.status = 'refunded'
                ) as merch_refunded_out"),
                DB::raw("(
                    SELECT EXISTS(
                        SELECT 1 FROM event_wallets ew
                        JOIN events e ON e.id = ew.event_id
                        WHERE e.eo_id = eo.id AND ew.withdraw_locked = 1
                    )
                ) as is_locked"),
            ])
            ->when($sortBy === 'gmv', fn ($q) => $q->orderByRaw('(ticket_gmv + merch_gmv) desc'))
            ->when($sortBy === 'debt', fn ($q) => $q->orderByDesc('outstanding_debt'))
            ->when($sortBy === 'balance', fn ($q) => $q->orderByDesc('wallet_balance'))
            ->when($sortBy === 'name', fn ($q) => $q->orderBy('eo.nama_badan_usaha'))
            ->paginate(12)
            ->withQueryString();

        // ===== Statistik ringkasan platform (dihitung dari SELURUH data, bukan cuma 1 halaman) =====
        $platformStats = [
            'total_eo' => DB::table('eo')->count(),
            'total_eo_approved' => DB::table('eo')->where('status', 'approved')->count(),
            'total_gmv' => (float) DB::table('transactions')->where('payment_status', 'paid')->sum('grand_total')
                         + (float) DB::table('transaction_merch')->where('payment_status', 'paid')->sum('grand_total'),
            'total_wallet_balance' => (float) DB::table('event_wallets')->sum(DB::raw('available_balance + held_balance')),
            'total_debt' => (float) DB::table('eo_debts')->whereIn('status', ['unpaid', 'partially_paid'])->sum('remaining_debt'),
            'total_pending_refund' => (float) DB::table('refunds')
                ->leftJoin('transactions', 'refunds.transaction_id', '=', 'transactions.id')
                ->leftJoin('transaction_merch', 'refunds.transaction_merch_id', '=', 'transaction_merch.id')
                ->whereIn('refunds.status', ['waiting', 'pending']) // tanggungan = refund belum dieksekusi (waiting + pending)
                ->sum(DB::raw('COALESCE(transactions.total_amount, transaction_merch.total_amount)')),
            'total_refunded_out' => (float) DB::table('refunds')->where('status', 'refunded')->sum('grand_total_refunded'),
            'total_locked' => DB::table('events')
                ->join('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                ->where('event_wallets.withdraw_locked', 1)
                ->distinct('events.eo_id')
                ->count('events.eo_id'),
        ];

        return view('admin.monitoring.index', compact(
            'eoList', 'search', 'statusFilter', 'sortBy', 'platformStats'
        ));
    }

    /**
     * 📊 Halaman 2: Dashboard finansial & portofolio event 1 EO
     */
    public function showEo($eoId)
    {
        $eo = DB::table('eo')->where('id', $eoId)->first();

        if (!$eo) {
            abort(404, 'EO tidak ditemukan.');
        }

        $eoEmail = optional(DB::table('users')->where('id', $eo->user_id)->first())->email;

        // Total akumulasi pendapatan EO (bagian EO dari tiket + merch, transaksi paid)
        $ticketEoRevenue = (float) DB::table('transactions')
            ->join('events', 'events.id', '=', 'transactions.event_id')
            ->where('events.eo_id', $eoId)
            ->where('transactions.payment_status', 'paid')
            ->sum('transactions.total_amount');

        $merchEoRevenue = (float) DB::table('transaction_merch')
            ->join('events', 'events.id', '=', 'transaction_merch.event_id')
            ->where('events.eo_id', $eoId)
            ->where('transaction_merch.payment_status', 'paid')
            ->sum(DB::raw('transaction_merch.grand_total - transaction_merch.service_tax'));

        $totalRevenue = $ticketEoRevenue + $merchEoRevenue;

        // Saldo dompet EO saat ini (available + held, seluruh event miliknya)
        $walletBalance = (float) DB::table('events')
            ->join('event_wallets', 'events.id', '=', 'event_wallets.event_id')
            ->where('events.eo_id', $eoId)
            ->sum(DB::raw('event_wallets.available_balance + event_wallets.held_balance'));

        // Total tanggungan utang aktif
        $outstandingDebt = (float) DB::table('eo_debts')
            ->where('eo_id', $eoId)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->sum('remaining_debt');

        // Tanggungan Refund yang belum diproses (masih menunggu, BUKAN utang)
        $pendingRefundLiability = (float) DB::table('refunds')
            ->leftJoin('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->leftJoin('transaction_merch', 'refunds.transaction_merch_id', '=', 'transaction_merch.id')
            ->leftJoin('events', DB::raw('COALESCE(transactions.event_id, transaction_merch.event_id)'), '=', 'events.id')
            ->where('events.eo_id', $eoId)
            ->whereIn('refunds.status', ['waiting', 'pending']) // tanggungan = refund belum dieksekusi (waiting + pending)
            ->sum(DB::raw('COALESCE(transactions.total_amount, transaction_merch.total_amount)'));

        // Total Dana Keluar untuk Refund yang SUDAH direalisasikan (history, final)
        $totalRefundedOut = (float) DB::table('refunds')
            ->leftJoin('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->leftJoin('transaction_merch', 'refunds.transaction_merch_id', '=', 'transaction_merch.id')
            ->leftJoin('events', DB::raw('COALESCE(transactions.event_id, transaction_merch.event_id)'), '=', 'events.id')
            ->where('events.eo_id', $eoId)
            ->where('refunds.status', 'refunded')
            ->sum('refunds.grand_total_refunded');

        // Track record distribusi status event
        $now = Carbon::now();
        $statusCount = [
            'selesai' => DB::table('events')->where('eo_id', $eoId)->where('status', 'approved')->where('date', '<', $now)->count(),
            'aktif'   => DB::table('events')->where('eo_id', $eoId)->where('status', 'approved')->where('date', '>=', $now)->count(),
            'resched' => DB::table('events')->where('eo_id', $eoId)->where('is_rescheduled', '>', 0)->count(),
            'batal'   => DB::table('events')->where('eo_id', $eoId)->where('status', 'cancelled')->count(),
        ];

        // ===== Tren pendapatan EO 6 bulan terakhir (tiket + merch) untuk mini chart =====
        $since = Carbon::now()->startOfMonth()->subMonths(5);
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->startOfMonth()->subMonths($i);
            $months[$m->format('Y-m')] = ['label' => $m->translatedFormat('M'), 'total' => 0.0];
        }

        $applyTrend = function ($rows) use (&$months) {
            foreach ($rows as $r) {
                if (isset($months[$r->ym])) {
                    $months[$r->ym]['total'] += (float) $r->total;
                }
            }
        };

        $applyTrend(DB::table('transactions')
            ->join('events', 'events.id', '=', 'transactions.event_id')
            ->where('events.eo_id', $eoId)
            ->where('transactions.payment_status', 'paid')
            ->whereNotNull('transactions.paid_time')
            ->where('transactions.paid_time', '>=', $since)
            ->selectRaw("DATE_FORMAT(transactions.paid_time, '%Y-%m') as ym, SUM(transactions.grand_total) as total")
            ->groupBy('ym')->get());

        $applyTrend(DB::table('transaction_merch')
            ->join('events', 'events.id', '=', 'transaction_merch.event_id')
            ->where('events.eo_id', $eoId)
            ->where('transaction_merch.payment_status', 'paid')
            ->whereNotNull('transaction_merch.paid_time')
            ->where('transaction_merch.paid_time', '>=', $since)
            ->selectRaw("DATE_FORMAT(transaction_merch.paid_time, '%Y-%m') as ym, SUM(transaction_merch.grand_total) as total")
            ->groupBy('ym')->get());

        $revenueTrend = array_values($months);
        $revenueTrendMax = max(1, ...array_column($revenueTrend, 'total')); // hindari div by zero di blade

        // Portofolio semua event milik EO ini + ringkasan cepat (1 query, tanpa N+1)
        $events = DB::table('events')
            ->leftJoin('event_wallets', 'events.id', '=', 'event_wallets.event_id')
            ->where('events.eo_id', $eoId)
            ->select([
                'events.id', 'events.title', 'events.status', 'events.date', 'events.location', 'events.is_rescheduled',
                'event_wallets.available_balance', 'event_wallets.held_balance', 'event_wallets.withdraw_locked',
                DB::raw("(
                    SELECT COUNT(*) FROM ticket_attendees ta
                    JOIN transactions t ON t.id = ta.transaction_id
                    WHERE t.event_id = events.id AND t.payment_status = 'paid'
                ) as tickets_sold"),
                DB::raw("(
                    SELECT COALESCE(SUM(t.grand_total), 0) FROM transactions t
                    WHERE t.event_id = events.id AND t.payment_status = 'paid'
                ) as ticket_gmv"),
                DB::raw("(
                    SELECT COALESCE(SUM(tm.grand_total), 0) FROM transaction_merch tm
                    WHERE tm.event_id = events.id AND tm.payment_status = 'paid'
                ) as merch_gmv"),
            ])
            ->orderByDesc('events.date')
            ->get();

        return view('admin.monitoring.eo-show', compact(
            'eo', 'eoEmail', 'totalRevenue', 'walletBalance', 'outstandingDebt',
            'pendingRefundLiability', 'totalRefundedOut',
            'statusCount', 'events', 'revenueTrend', 'revenueTrendMax'
        ));
    }

    /**
     * 🔽 AJAX: Ringkasan dropdown per event
     * (dipanggil saat card event di-klik/expand)
     */
    public function eventSummary(Request $request, $eventId)
    {
        $event = DB::table('events')->where('id', $eventId)->first();

        if (!$event) {
            return response()->json(['error' => 'Event tidak ditemukan.'], 404);
        }

        $groupBy = in_array($request->input('group_by'), ['day', 'week', 'month'])
            ? $request->input('group_by')
            : 'day';

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::parse($event->created_at)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $ticketSummary = DB::table('transactions')
            ->where('event_id', $eventId)
            ->where('payment_status', 'paid')
            ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(grand_total),0) as gmv, COALESCE(SUM(total_amount),0) as eo_rev, COALESCE(SUM(service_tax),0) as tax')
            ->first();

        $ticketsSoldCount = DB::table('ticket_attendees')
            ->join('transactions', 'ticket_attendees.transaction_id', '=', 'transactions.id')
            ->where('transactions.event_id', $eventId)
            ->where('transactions.payment_status', 'paid')
            ->count();

        $merchSummary = DB::table('transaction_merch')
            ->where('event_id', $eventId)
            ->where('payment_status', 'paid')
            ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(grand_total),0) as gmv, COALESCE(SUM(service_tax),0) as tax')
            ->first();

        $merchQtySold = (int) DB::table('transaction_merch_details')
            ->join('transaction_merch', 'transaction_merch_details.transaction_merch_id', '=', 'transaction_merch.id')
            ->where('transaction_merch.event_id', $eventId)
            ->where('transaction_merch.payment_status', 'paid')
            ->sum('transaction_merch_details.quantity');

        $dateFormat = match ($groupBy) {
            'week'  => '%x-W%v',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $ticketTrend = DB::table('transactions')
            ->where('event_id', $eventId)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_time', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(paid_time, '{$dateFormat}') as period, COUNT(*) as trx_count, SUM(grand_total) as gmv")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $merchTrend = DB::table('transaction_merch')
            ->where('event_id', $eventId)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_time', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(paid_time, '{$dateFormat}') as period, COUNT(*) as trx_count, SUM(grand_total) as gmv")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $trend = [];
        foreach ($ticketTrend as $row) {
            $trend[$row->period]['period'] = $row->period;
            $trend[$row->period]['ticket_gmv'] = (float) $row->gmv;
            $trend[$row->period]['ticket_trx'] = (int) $row->trx_count;
        }
        foreach ($merchTrend as $row) {
            $trend[$row->period]['period'] = $row->period;
            $trend[$row->period]['merch_gmv'] = (float) $row->gmv;
            $trend[$row->period]['merch_trx'] = (int) $row->trx_count;
        }
        ksort($trend);

        $trend = array_map(function ($t) {
            $ticketGmv = $t['ticket_gmv'] ?? 0;
            $merchGmv = $t['merch_gmv'] ?? 0;
            return [
                'period'     => $t['period'],
                'ticket_gmv' => $ticketGmv,
                'ticket_trx' => $t['ticket_trx'] ?? 0,
                'merch_gmv'  => $merchGmv,
                'merch_trx'  => $t['merch_trx'] ?? 0,
                'total_gmv'  => $ticketGmv + $merchGmv,
            ];
        }, array_values($trend));

        return response()->json([
            'event' => ['id' => $event->id, 'title' => $event->title],
            'summary' => [
                'ticket_gmv'     => (float) $ticketSummary->gmv,
                'ticket_eo_rev'  => (float) $ticketSummary->eo_rev,
                'ticket_tax'     => (float) $ticketSummary->tax,
                'ticket_trx'     => (int) $ticketSummary->trx_count,
                'tickets_sold'   => $ticketsSoldCount,
                'merch_gmv'      => (float) $merchSummary->gmv,
                'merch_tax'      => (float) $merchSummary->tax,
                'merch_trx'      => (int) $merchSummary->trx_count,
                'merch_qty_sold' => $merchQtySold,
                'total_gmv'      => (float) $ticketSummary->gmv + (float) $merchSummary->gmv,
            ],
            'filter' => [
                'group_by'   => $groupBy,
                'start_date' => $startDate->toDateString(),
                'end_date'   => $endDate->toDateString(),
            ],
            'trend' => $trend,
        ]);
    }
}
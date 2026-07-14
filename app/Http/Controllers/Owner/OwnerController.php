<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Models\Withdrawal;
use App\Models\MerchWithdrawal;
use Carbon\Carbon;

class OwnerController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (!auth()->check()) {
                return redirect()->route('login');
            }

            if (auth()->user()->role !== 'owner') {
                abort(403, 'Akses hanya untuk owner');
            }

            return $next($request);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | OWNER DASHBOARD
    |--------------------------------------------------------------------------
    */
    public function dashboard(Request $request)
    {
        // =====================================================================
        // 1. LOGIKA SORTIR / FILTER PERIODE WAKTU
        // =====================================================================
        $period = $request->get('period', '30_days'); 
        $startDate = null;
        $endDate = Carbon::now();

        if ($period == 'today') {
            $startDate = Carbon::today();
        } elseif ($period == '7_days') {
            $startDate = Carbon::now()->subDays(7);
        } elseif ($period == '30_days') {
            $startDate = Carbon::now()->subDays(30);
        } elseif ($period == 'this_month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } elseif ($period == 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        }

        $applyDateFilter = function($query, $column) use ($startDate, $endDate) {
            if ($startDate) {
                return $query->whereBetween($column, [$startDate, $endDate]);
            }
            return $query;
        };

        // =====================================================================
        // 2. METRIK OPERASIONAL
        // =====================================================================
        $totalEO = DB::table('eo')->count();
        $approvedEO = DB::table('eo')->where('status', 'approved')->count();
        $pendingEO = DB::table('eo')->where('status', 'pending')->count();
        $rejectedEO = DB::table('eo')->where('status', 'rejected')->count();

        $totalEvents = DB::table('events')->count();
        $approvedEvents = DB::table('events')->where('status', 'approved')->count();
        $pendingEvents = DB::table('events')->where('status', 'pending')->count();
        $rejectedEvents = DB::table('events')->where('status', 'rejected')->count();

        $pendingEventBaru = DB::table('events')->where('status', 'pending')->count();
        $pendingReschedule = DB::table('events')->where('status', 'pending_reschedule')->count();
        $pendingCancel = DB::table('events')->where('status', 'pending_cancel')->count();

        $totalUsers = DB::table('users')->count();

        $pendingWithdrawal = Withdrawal::where('status', 'pending')->count();
        $pendingMerchWithdrawal = MerchWithdrawal::where('status', 'pending')->count();

        $totalPending = $pendingEO
            + $pendingEventBaru
            + $pendingReschedule
            + $pendingCancel
            + $pendingWithdrawal
            + $pendingMerchWithdrawal;

        // =====================================================================
        // 3. METRIK FINANSIAL & KEUNTUNGAN SERVICE TAX (REVISI)
        // =====================================================================
        // Transaksi tiket yang PERNAH terbayar (paid + refunded). Wajib menyertakan 'refunded'
        // karena service tax TIDAK dikembalikan saat refund — platform tetap menyimpannya.
        // Tanpa 'refunded', keuntungan platform keliru jadi 0 saat semua pembeli refund.
        $ticketSalesQuery = DB::table('transactions')->whereIn('payment_status', ['paid', 'refunded']);
        $ticketSalesQuery = $applyDateFilter($ticketSalesQuery, 'created_at');
        $totalTicketSales = $ticketSalesQuery->sum('grand_total');
        $platformTicketTax = $ticketSalesQuery->sum('service_tax'); // Untung platform dari tiket (tetap dihitung walau refund)

        // Idem untuk merchandise: service tax tetap milik platform meski transaksi di-refund.
        $merchSalesQuery = DB::table('transaction_merch')->whereIn('payment_status', ['paid', 'refunded']);
        $merchSalesQuery = $applyDateFilter($merchSalesQuery, 'created_at');
        $totalMerchSales = $merchSalesQuery->sum('grand_total');
        $platformMerchTax = $merchSalesQuery->sum('service_tax'); // Untung platform dari merch (tetap dihitung walau refund)

        // TOTAL OMZET PLATFORM (Semua dana masuk kotor sebelum WD EO & Refund Pembeli)
        $totalPlatformSales = $totalTicketSales + $totalMerchSales;

        // Biaya transfer Xendit (Rp2.500 per refund) untuk setiap refund yang BERHASIL diproses.
        // Biaya ini ditanggung platform (dipotong dari service tax), jadi keuntungan yang
        // ditampilkan adalah BERSIH. Difilter status 'refunded' karena fee baru benar-benar
        // dibebankan saat transfer sukses (konsisten dgn platform_wallets.total_refund_fees_spent).
        $refundFeeQuery = DB::table('refunds')->where('status', 'refunded');
        $refundFeeQuery = $applyDateFilter($refundFeeQuery, 'processed_at');
        $totalRefundFees = $refundFeeQuery->sum('refunds_tax');

        // TOTAL KEUNTUNGAN BERSIH PLATFORM = Service Tax (paid+refunded) − Biaya transfer refund Xendit
        $totalPlatformEarnings = ($platformTicketTax + $platformMerchTax) - $totalRefundFees;

        // =====================================================================
        // 4. GRAFIK TREN HARIAN INTERAKTIF
        // =====================================================================
        $chartDays = $period == 'today' ? 0 : ($period == '7_days' ? 7 : 30);
        if ($period == 'custom' && $startDate) {
            $chartDays = min(90, $startDate->diffInDays($endDate)); 
        }
        
        $graphStart = $startDate ? $startDate->copy() : Carbon::now()->subDays(30);
        $chartLabels = [];
        $chartTicketData = [];
        $chartMerchData = [];

        for ($i = 0; $i <= $chartDays; $i++) {
            $currentDay = $graphStart->copy()->addDays($i);
            if ($currentDay->greaterThan(Carbon::now()) || ($period == 'custom' && $currentDay->greaterThan($endDate))) {
                break;
            }
            
            $dateString = $currentDay->format('Y-m-d');
            $chartLabels[] = $currentDay->format('d M');

            $chartTicketData[] = DB::table('transactions')
                ->where('payment_status', 'paid')
                ->whereDate('created_at', $dateString)
                ->sum('grand_total');

            $chartMerchData[] = DB::table('transaction_merch')
                ->where('payment_status', 'paid')
                ->whereDate('created_at', $dateString)
                ->sum('grand_total');
        }

        // =====================================================================
        // 5. RINGKASAN DATA TOP PERFORMING EO
        // =====================================================================
        $eoPerformances = DB::table('eo')
            ->leftJoin('events', 'eo.id', '=', 'events.eo_id')
            ->leftJoin('transactions', function($join) use ($startDate, $endDate) {
                $join->on('events.id', '=', 'transactions.event_id')
                     ->where('transactions.payment_status', '=', 'paid');
                if ($startDate) {
                    $join->whereBetween('transactions.created_at', [$startDate, $endDate]);
                }
            })
            ->select(
                'eo.id',
                'eo.nama_badan_usaha',
                DB::raw('COUNT(DISTINCT events.id) as total_events'),
                DB::raw('COUNT(DISTINCT transactions.id) as tickets_sold'),
                DB::raw('SUM(transactions.grand_total) as total_revenue')
            )
            ->groupBy('eo.id', 'eo.nama_badan_usaha')
            ->orderBy('total_revenue', 'desc')
            ->take(5)
            ->get();

        // =====================================================================
        // 6. STACKED BAR CHART & DATA FEED TERBARU
        // =====================================================================
        $trendLabels   = [];
        $trendApproved = [];
        $trendPending  = [];
        $trendRejected = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $trendLabels[] = $bulan->translatedFormat('M');

            $trendApproved[] = DB::table('events')
                ->where('status', 'approved')
                ->whereMonth('created_at', $bulan->month)
                ->whereYear('created_at', $bulan->year)
                ->count();

            $trendPending[] = DB::table('events')
                ->whereIn('status', ['pending', 'pending_reschedule', 'pending_cancel'])
                ->whereMonth('created_at', $bulan->month)
                ->whereYear('created_at', $bulan->year)
                ->count();

            $trendRejected[] = DB::table('events')
                ->where('status', 'rejected')
                ->whereMonth('created_at', $bulan->month)
                ->whereYear('created_at', $bulan->year)
                ->count();
        }

        $recentEO = DB::table('eo')
            ->join('users', 'eo.user_id', '=', 'users.id')
            ->select('eo.*', 'users.name', 'users.email')
            ->latest('eo.created_at')
            ->take(5)
            ->get();

        $recentEvents = Event::with('eo')
            ->latest()
            ->take(5)
            ->get();

        $actEO = DB::table('eo')
            ->join('users', 'eo.user_id', '=', 'users.id')
            ->select(
                DB::raw("'eo' as type"),
                'users.name as title',
                DB::raw("CONCAT('Pengajuan EO baru') as subtitle"),
                'eo.status',
                'eo.created_at'
            )
            ->latest('eo.created_at')
            ->take(5);

        $actEvent = DB::table('events')
            ->select(
                DB::raw("'event' as type"),
                'title',
                DB::raw("CASE
                    WHEN status = 'pending_reschedule' THEN 'Pengajuan reschedule'
                    WHEN status = 'pending_cancel'     THEN 'Pengajuan pembatalan'
                    ELSE 'Pengajuan event baru'
                END as subtitle"),
                'status',
                'created_at'
            )
            ->latest()
            ->take(5);

        $actWithdrawal = DB::table('withdrawals')
            ->join('events', 'withdrawals.event_id', '=', 'events.id')
            ->select(
                DB::raw("'withdrawal' as type"),
                DB::raw("CONCAT('Withdrawal Rp', FORMAT(withdrawals.amount,0)) as title"),
                'events.title as subtitle',
                'withdrawals.status',
                'withdrawals.created_at'
            )
            ->latest('withdrawals.created_at')
            ->take(5);

        $recentActivity = $actEO
            ->unionAll($actEvent)
            ->unionAll($actWithdrawal)
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        return view('owner.dashboard', compact(
            'totalEO', 'approvedEO', 'pendingEO', 'rejectedEO',
            'totalEvents', 'approvedEvents', 'pendingEvents', 'rejectedEvents',
            'pendingEventBaru', 'pendingReschedule', 'pendingCancel', 'pendingWithdrawal', 'pendingMerchWithdrawal', 'totalPending',
            'totalUsers', 'totalTicketSales', 'totalMerchSales', 'totalPlatformSales', 'totalPlatformEarnings', 'totalRefundFees',
            'chartLabels', 'chartTicketData', 'chartMerchData', 'eoPerformances',
            'trendLabels', 'trendApproved', 'trendPending', 'trendRejected',
            'recentEO', 'recentEvents', 'recentActivity', 'period'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | EO APPROVAL
    |--------------------------------------------------------------------------
    */
    public function eoIndex()
    {
        $eoList = DB::table('eo')
            ->join('users', 'eo.user_id', '=', 'users.id')
            ->select('eo.*', 'users.name', 'users.email')
            ->orderBy('eo.created_at', 'desc')
            ->get();

        return view('owner.eo-approval', compact('eoList'));
    }

    public function approve($id)
    {
        $eo = DB::table('eo')->where('id', $id)->first();

        if (!$eo) {
            abort(404);
        }

        DB::table('eo')->where('id', $id)->update(['status' => 'approved']);
        DB::table('users')->where('id', $eo->user_id)->update(['role' => 'eo']);

        return back()->with('success', 'EO berhasil di-approve');
    }

public function reject(Request $request, $id)
{
    $eo = DB::table('eo')->where('id', $id)->first();

    if (!$eo) {
        abort(404);
    }

    $request->validate([
        'rejected_reason' => 'required|string|max:1000',
    ], [
        'rejected_reason.required' => 'Alasan penolakan wajib diisi',
    ]);

    DB::table('eo')->where('id', $id)->update([
        'status'          => 'rejected',
        'rejected_reason' => $request->rejected_reason,
        'updated_at'      => now(),
    ]);

    return back()->with('success', 'EO berhasil ditolak');
}

    /*
    |--------------------------------------------------------------------------
    | EVENT APPROVAL
    |--------------------------------------------------------------------------
    */
    public function eventIndex()
    {
        $events = Event::with('eo')->latest()->get();
        return view('owner.event-approval', compact('events'));
    }

    public function approveEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->status = 'approved';
        $event->save();

        return back()->with('success', 'Event berhasil di-approve');
    }

    public function rejectEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->status = 'rejected';
        $event->save();

        return back()->with('error', 'Event berhasil ditolak');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\TicketAttendee;
use App\Models\Event; // pastikan di atas ada ini
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class DashboardController extends Controller
{
public function __construct()
{
    $this->middleware(function ($request, $next) {

        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->role !== 'admin') {
            abort(403, 'Akses hanya untuk admin');
        }

        return $next($request);
    });
}

    public function absensi(Request $request)
    {
        $attendees = TicketAttendee::with(['transaction.event'])
            ->whereHas('transaction', function ($tq) {
                $tq->where('payment_status', 'paid');
            })
            ->when($request->event_id, fn($q) =>
                $q->whereHas('transaction.event', fn($q2) => $q2->where('id', $request->event_id))
            )
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('ticket_attendees.name', 'like', "%{$search}%")
                        ->orWhere('ticket_attendees.phone_number', 'like', "%{$search}%")
                        ->orWhereHas('transaction', function ($tq) use ($search) {
                            $tq->where('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->status, function ($q) use ($request) {
                if ($request->status === 'sudah') {
                    // Hanya yang punya transaction dan is_registered = true
                    $q->whereHas('transaction', fn($tq) => $tq->where('is_registered', true));
                } elseif ($request->status === 'belum') {
                    // Termasuk yang tidak punya transaction (atau punya transaksi tapi is_registered = false)
                    $q->where(function ($sub) {
                        $sub->whereDoesntHave('transaction')
                            ->orWhereHas('transaction', fn($tq) => $tq->where('is_registered', false));
                    });
                }
            })
            ->select('ticket_attendees.*')
            ->get();

        $events = Event::all();

        return view('admin.admin-absensi', [
            'attendees' => $attendees,
            'events' => $events,
            'search' => $request->search,
            'status_absen' => $request->status_absen,
            'event_id' => $request->event_id,
        ]);
    }

/**
 * 📊 DASHBOARD RINGKASAN PLATFORM (Tab "Dashboard")
 *
 * Semua angka dihitung langsung dari tabel sumber agar selalu akurat,
 * tidak bergantung pada kolom cache yang mungkin belum tersinkron.
 */
public function index(Request $request)
{
    // ===================== TIKET =====================
    $ticket = DB::table('transactions')->selectRaw("
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN grand_total  ELSE 0 END), 0) AS gmv,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN service_tax  ELSE 0 END), 0) AS tax,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS eo_rev,
        SUM(CASE WHEN payment_status = 'paid'     THEN 1 ELSE 0 END) AS paid_count,
        SUM(CASE WHEN payment_status = 'unpaid'   THEN 1 ELSE 0 END) AS unpaid_count,
        SUM(CASE WHEN payment_status = 'refunded' THEN 1 ELSE 0 END) AS refunded_count
    ")->first();

    // ===================== MERCHANDISE =====================
    $merch = DB::table('transaction_merch')->selectRaw("
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN grand_total  ELSE 0 END), 0) AS gmv,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN service_tax  ELSE 0 END), 0) AS tax,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_count
    ")->first();

    // Tiket terjual (1 peserta = 1 tiket) dari transaksi lunas
    $ticketsSold = DB::table('ticket_attendees')
        ->join('transactions', 'ticket_attendees.transaction_id', '=', 'transactions.id')
        ->where('transactions.payment_status', 'paid')
        ->count();

    // ===================== FINANSIAL =====================
    $refundFeesSpent  = (float) DB::table('refunds')->where('status', 'refunded')->sum('refunds_tax');
    $refundedToBuyers = (float) DB::table('refunds')->where('status', 'refunded')->sum('grand_total_refunded');
    $platformRevenue  = (float) $ticket->tax + (float) $merch->tax;        // pendapatan platform (service tax)
    $platformNet      = $platformRevenue - $refundFeesSpent;              // bersih setelah biaya refund
    $eoDebtOutstanding = (float) DB::table('eo_debts')->where('status', '!=', 'paid')->sum('remaining_debt');
    $withdrawnToEo = (float) DB::table('withdrawals')->where('status', 'approved')->sum('amount')
                   + (float) DB::table('merch_withdrawals')->where('status', 'approved')->sum('amount');

    // ===================== OPERASIONAL =====================
    $eventStatus = DB::table('events')
        ->select('status', DB::raw('COUNT(*) as total'))
        ->groupBy('status')->pluck('total', 'status');

    $summary = [
        'platform_revenue' => $platformRevenue,
        'total_gmv'        => (float) $ticket->gmv + (float) $merch->gmv,
        'tickets_sold'     => (int) $ticketsSold,
        'paid_count'       => (int) $ticket->paid_count + (int) $merch->paid_count,

        'ticket_paid'      => (int) $ticket->paid_count,
        'ticket_unpaid'    => (int) $ticket->unpaid_count,
        'ticket_refunded'  => (int) $ticket->refunded_count,
        'ticket_gmv'       => (float) $ticket->gmv,
        'ticket_tax'       => (float) $ticket->tax,
        'merch_paid'       => (int) $merch->paid_count,
        'merch_gmv'        => (float) $merch->gmv,
        'merch_tax'        => (float) $merch->tax,

        'refund_fees'      => $refundFeesSpent,
        'refunded_buyers'  => $refundedToBuyers,
        'platform_net'     => $platformNet,
        'eo_debt'          => $eoDebtOutstanding,
        'withdrawn_eo'     => $withdrawnToEo,

        'total_events'     => (int) array_sum($eventStatus->all()),
        'total_eo'         => (int) DB::table('eo')->count(),
        'total_users'      => (int) DB::table('users')->where('role', 'user')->count(),
    ];

    $pending = [
        'events'      => (int) ($eventStatus['pending'] ?? 0),
        'refunds'     => (int) DB::table('refunds')->whereIn('status', ['waiting', 'pending'])->count(),
        'withdrawals' => (int) DB::table('withdrawals')->where('status', 'pending')->count()
                       + (int) DB::table('merch_withdrawals')->where('status', 'pending')->count(),
    ];

    // ===================== TREN PENDAPATAN 6 BULAN (tiket + merch) =====================
    $since  = Carbon::now()->startOfMonth()->subMonths(5);
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = Carbon::now()->startOfMonth()->subMonths($i);
        $months[$m->format('Y-m')] = ['label' => $m->translatedFormat('M Y'), 'total' => 0.0];
    }

    $applyTrend = function ($rows) use (&$months) {
        foreach ($rows as $r) {
            if (isset($months[$r->ym])) {
                $months[$r->ym]['total'] += (float) $r->total;
            }
        }
    };

    $applyTrend(DB::table('transactions')
        ->where('payment_status', 'paid')->whereNotNull('paid_time')->where('paid_time', '>=', $since)
        ->selectRaw("DATE_FORMAT(paid_time, '%Y-%m') as ym, SUM(grand_total) as total")
        ->groupBy('ym')->get());

    $applyTrend(DB::table('transaction_merch')
        ->where('payment_status', 'paid')->whereNotNull('paid_time')->where('paid_time', '>=', $since)
        ->selectRaw("DATE_FORMAT(paid_time, '%Y-%m') as ym, SUM(grand_total) as total")
        ->groupBy('ym')->get());

    $trendLabels = array_values(array_map(fn ($m) => $m['label'], $months));
    $trendData   = array_values(array_map(fn ($m) => round($m['total']), $months));

    // ===================== TOP EVENT =====================
    $topEvents = DB::table('transactions')
        ->join('events', 'transactions.event_id', '=', 'events.id')
        ->leftJoin('eo', 'events.eo_id', '=', 'eo.id')
        ->where('transactions.payment_status', 'paid')
        ->select(
            'events.title',
            'eo.nama_badan_usaha as eo_name',
            DB::raw('SUM(transactions.total_amount) as revenue'),
            DB::raw('COUNT(transactions.id) as trx_count')
        )
        ->groupBy('events.id', 'events.title', 'eo.nama_badan_usaha')
        ->orderByDesc('revenue')
        ->limit(5)->get();

    // ===================== TRANSAKSI TERBARU =====================
    $recent = DB::table('transactions')
        ->leftJoin('events', 'transactions.event_id', '=', 'events.id')
        ->where('transactions.payment_status', 'paid')
        ->select('transactions.email', 'transactions.grand_total', 'transactions.paid_time', 'events.title as event_title')
        ->orderByDesc('transactions.paid_time')
        ->limit(8)->get();

    return view('admin.admin-overview', compact(
        'summary', 'eventStatus', 'pending', 'trendLabels', 'trendData', 'topEvents', 'recent'
    ));
}

/**
 * 🎟️ DAFTAR TRANSAKSI TIKET (Tab "Transaksi Tiket")
 * Sebelumnya ini adalah isi index(); dipindah agar dashboard bisa jadi ringkasan.
 */
public function transactions(Request $request)
{
    $transactions = $this->getAllTransactionData($request);

    // Ambil total pembayaran paid
    $totalPaidAmount = Transaction::where('payment_status', 'paid')->sum('total_amount');

    // Tambahan: total count paid & unpaid
    $totalPaidCount = Transaction::where('payment_status', 'paid')->count();
    $totalUnpaidCount = Transaction::where('payment_status', 'unpaid')->count();

    // Ambil semua event untuk dropdown
    $events = Event::orderBy('title', 'asc')->get();

    return view('admin.admin-dashboard', compact(
        'transactions',
        'totalPaidAmount',
        'events',
        'totalPaidCount',
        'totalUnpaidCount'
    ));
}

    public function getAllTransactionData(Request $request)
    {
        $sortBy = $request->input('sort_by');
        $allowedSorts = ['email', 'payment_status', 'checkout_time', 'event_title', 'name'];

        $transactions = Transaction::with(['attendees', 'event'])
            // Filter berdasarkan event
            ->when($request->event_id, fn($q) =>
                $q->where('event_id', $request->event_id)
            )

            // Filter berdasarkan status pembayaran
            ->when($request->payment_status, fn($q) =>
                $q->where('payment_status', $request->payment_status)
            )

            // Pencarian berdasarkan email atau nama attendee
            ->when($request->q, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('email', 'like', '%' . $request->q . '%')
                        ->orWhereHas('attendees', fn($q2) =>
                            $q2->where('name', 'like', '%' . $request->q . '%')
                        );
                });
            })

            // Filter berdasarkan rentang tanggal checkout
            ->when($request->start_date && $request->end_date, function ($q) use ($request) {
                $q->whereBetween('checkout_time', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            });

        // Sorting
        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'event_title') {
                $transactions->join('events', 'transactions.event_id', '=', 'events.id')
                    ->addSelect('transactions.*') // Ambil semua kolom transactions
                    ->orderBy('events.title', 'asc');
            } elseif ($sortBy !== 'name') {
                $transactions->orderBy($sortBy, 'asc');
            }
        }

        $transactions = $transactions->get();

        // Sorting manual untuk nama attendee
        if ($sortBy === 'name') {
            $transactions = $transactions->sortBy(function ($transaction) {
                return optional($transaction->attendees->first())->name;
            })->values();
        }

        return $transactions;
    }

    public function exportPDF(Request $request)
    {
        $transactions = $this->getAllTransactionData($request);
        $totalPaidAmount = $transactions->where('payment_status', 'paid')->sum('total_amount');

        $pdf = Pdf::loadView('admin.export-pdf', compact('transactions', 'totalPaidAmount'));
        return $pdf->download('transactions.pdf');
    }

    public function exportSimpleExcel(Request $request): StreamedResponse
    {
        $transactions = $this->getAllTransactionData($request);
        $totalPaidAmount = $transactions->where('payment_status', 'paid')->sum('total_amount');

        return response()->streamDownload(function () use ($transactions, $totalPaidAmount) {
            $writer = SimpleExcelWriter::streamDownload('transactions.xlsx');

            // Header
            $writer->addRow([
                'Email', 'Name', 'Phone Number', 'Checkout Time', 'Paid Time', 'Payment Status', 'Total Amount'
            ]);

            foreach ($transactions as $transaction) {
                if ($transaction->attendees->isEmpty()) {
                    $writer->addRow([
                        $transaction->email,
                        '-',
                        '-',
                        $transaction->checkout_time,
                        $transaction->paid_time ?? '-',
                        $transaction->payment_status,
                        $transaction->total_amount,
                    ]);
                } else {
                    foreach ($transaction->attendees as $attendee) {
                        $writer->addRow([
                            $transaction->email,
                            $attendee->name,
                            $attendee->phone_number,
                            $transaction->checkout_time,
                            $transaction->paid_time ?? '-',
                            $transaction->payment_status,
                            $transaction->total_amount,
                        ]);
                    }
                }
            }

            // Tambahkan total di akhir
            $writer->addRow([
                '', '', '', '', '', 'Total Paid',
                $totalPaidAmount,
            ]);

            $writer->close();
        }, 'transactions.xlsx');
        
    } 

public function regenerateQR($id)
{
    $transaction = Transaction::find($id);

    if (!$transaction) {
        return back()->with('error', 'Transaksi tidak ditemukan.');
    }

    try {
        // Panggil fungsi existing untuk generate QR ulang
        app(\App\Http\Controllers\WebhookController::class)->generateTicketQRCode($transaction);

        return back()->with('success', 'QR Code untuk transaksi #'.$transaction->id.' berhasil digenerate ulang.');
    } catch (\Exception $e) {
        \Log::error('Gagal generate ulang QR: '.$e->getMessage());
        return back()->with('error', 'Gagal generate ulang QR.');
    }
}


public function regenerateAllQR()
{
    $transactions = Transaction::where('payment_status', 'paid')->get();
    $success = 0;
    $failed = 0;

    foreach ($transactions as $transaction) {
        try {
            // Gunakan fungsi existing
            app(\App\Http\Controllers\WebhookController::class)->generateTicketQRCode($transaction);
            $success++;
        } catch (\Exception $e) {
            \Log::error("Gagal regenerate QR untuk transaksi ID {$transaction->id}: " . $e->getMessage());
            $failed++;
        }
    }

    return redirect()
        ->route('admin.transactions')
        ->with('success', "QR Code berhasil diregenerate ulang. Sukses: {$success}, Gagal: {$failed}");
}



};

<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Event;
use App\Models\Eo;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (!auth()->check()) {
                return redirect()->route('login');
            }

            $user = auth()->user();

            $eo = Eo::where('user_id', $user->id)->first();

            if ($user->role !== 'eo') {
                abort(403, 'Akses hanya untuk EO');
            }

            if (!$eo || $eo->status !== 'approved') {
                abort(403, 'Akun EO belum diapprove');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $transactions = $this->getAllTransactionData($request);

        $user = auth()->user();
        $eo = Eo::where('user_id', $user->id)->first();

        $events = Event::where('eo_id', $eo->id)
            ->orderBy('title', 'asc')
            ->get();

        $baseQuery = Transaction::whereHas('event', function ($q) use ($eo) {
            $q->where('eo_id', $eo->id);
        });

        $totalPaidAmount = (clone $baseQuery)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $totalPaidCount = (clone $baseQuery)
            ->where('payment_status', 'paid')
            ->count();

        $totalUnpaidCount = (clone $baseQuery)
            ->where('payment_status', 'unpaid')
            ->count();

        return view('eo.transaction', compact(
            'transactions',
            'events',
            'totalPaidAmount',
            'totalPaidCount',
            'totalUnpaidCount'
        ));
    }

    public function getAllTransactionData(Request $request)
    {
        $user = auth()->user();
        $eo = Eo::where('user_id', $user->id)->first();

        $sortBy = $request->input('sort_by');

        $allowedSorts = [
            'email',
            'payment_status',
            'checkout_time',
            'event_title',
            'name'
        ];

        $transactions = Transaction::with(['attendees', 'event'])

            // FILTER EO
            ->whereHas('event', function ($q) use ($eo) {
                $q->where('eo_id', $eo->id);
            })

            ->when($request->event_id, fn($q) =>
                $q->where('event_id', $request->event_id)
            )

            ->when($request->payment_status, fn($q) =>
                $q->where('payment_status', $request->payment_status)
            )

            ->when($request->q, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {

                    $query->where('email', 'like', '%' . $request->q . '%')

                        ->orWhereHas('attendees', fn($q2) =>
                            $q2->where('name', 'like', '%' . $request->q . '%')
                        );
                });
            })

            ->when($request->start_date && $request->end_date, function ($q) use ($request) {

                $q->whereBetween('checkout_time', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            });

        // sorting
        if ($sortBy && in_array($sortBy, $allowedSorts)) {

            if ($sortBy === 'event_title') {

                $transactions->join('events', 'transactions.event_id', '=', 'events.id')
                    ->addSelect('transactions.*')
                    ->orderBy('events.title', 'asc');

            } elseif ($sortBy !== 'name') {

                $transactions->orderBy($sortBy, 'asc');
            }
        }

        $transactions = $transactions->get();

        // sorting nama attendee
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

        $totalPaidAmount = $transactions
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $pdf = Pdf::loadView('eo.export-pdf', compact(
            'transactions',
            'totalPaidAmount'
        ));

        return $pdf->download('eo-transactions.pdf');
    }

    public function exportSimpleExcel(Request $request): StreamedResponse
    {
        $transactions = $this->getAllTransactionData($request);

        $totalPaidAmount = $transactions
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        return response()->streamDownload(function () use ($transactions, $totalPaidAmount) {

            $writer = SimpleExcelWriter::streamDownload('eo-transactions.xlsx');

            $writer->addRow([
                'Email',
                'Name',
                'Phone Number',
                'Checkout Time',
                'Paid Time',
                'Payment Status',
                'Total Amount'
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

            $writer->addRow([
                '',
                '',
                '',
                '',
                '',
                'Total Paid',
                $totalPaidAmount,
            ]);

            $writer->close();

        }, 'eo-transactions.xlsx');
    }
}
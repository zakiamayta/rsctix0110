<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransactionMerch;
use App\Models\Event;
use App\Models\Eo;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MerchTransactionController extends Controller
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

        // hanya event milik EO
        $events = Event::where('eo_id', $eo->id)
            ->orderBy('title', 'asc')
            ->get();

        // base query merch milik EO
        $baseQuery = TransactionMerch::whereHas('details.product.event', function ($q) use ($eo) {
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

        return view('eo.merch-transaction', compact(
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

        $transactions = TransactionMerch::with([
                'details.product.event',
                'details.varian',
                'details.ukuran'
            ])

            // FILTER EO
            ->whereHas('details.product.event', function ($q) use ($eo) {
                $q->where('eo_id', $eo->id);
            })

            ->when($request->event_id, function ($q) use ($request) {
                $q->whereHas('details.product', function ($pq) use ($request) {
                    $pq->where('event_id', $request->event_id);
                });
            })

            // filter status
            ->when($request->payment_status, fn($q) =>
                $q->where('payment_status', $request->payment_status)
            )

            // search
            ->when($request->q, function ($q) use ($request) {

                $q->where(function ($query) use ($request) {

                    $query->where('email', 'like', '%' . $request->q . '%')

                        ->orWhereHas('details', function ($dq) use ($request) {
                            $dq->where('buyer_name', 'like', '%' . $request->q . '%');
                        });
                });
            })

            // filter tanggal
            ->when($request->start_date && $request->end_date, function ($q) use ($request) {

                $q->whereBetween('checkout_time', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            });

        // sorting
        if ($sortBy && in_array($sortBy, $allowedSorts)) {

            // skip event_title karena relasi lewat details
            if ($sortBy !== 'name' && $sortBy !== 'event_title') {

                $transactions->orderBy($sortBy, 'asc');
            }
        }

        $transactions = $transactions->get();

        // sorting nama buyer
        if ($sortBy === 'name') {

            $transactions = $transactions->sortBy(function ($trx) {
                return optional($trx->details->first())->buyer_name;
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

        $pdf = Pdf::loadView('eo.export-merch-pdf', compact(
            'transactions',
            'totalPaidAmount'
        ));

        return $pdf->download('eo-merch-transactions.pdf');
    }

    public function exportSimpleExcel(Request $request): StreamedResponse
    {
        $transactions = $this->getAllTransactionData($request);

        $totalPaidAmount = $transactions
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        return response()->streamDownload(function () use ($transactions, $totalPaidAmount) {

            $writer = SimpleExcelWriter::streamDownload('eo-merch-transactions.xlsx');

            $writer->addRow([
                'Email',
                'Buyer',
                'Phone',
                'Produk',
                'Varian',
                'Ukuran',
                'Qty',
                'Checkout',
                'Paid Time',
                'Status',
                'Total Amount'
            ]);

            foreach ($transactions as $trx) {

                if ($trx->details->isEmpty()) {

                    $writer->addRow([
                        $trx->email,
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        $trx->checkout_time,
                        $trx->paid_time ?? '-',
                        $trx->payment_status,
                        $trx->total_amount
                    ]);

                } else {

                    foreach ($trx->details as $detail) {

                        $writer->addRow([
                            $trx->email,
                            $detail->buyer_name,
                            $detail->buyer_phone,
                            optional($detail->product)->name,
                            optional($detail->varian)->varian,
                            optional($detail->ukuran)->ukuran,
                            $detail->quantity,
                            $trx->checkout_time,
                            $trx->paid_time ?? '-',
                            $trx->payment_status,
                            $trx->total_amount
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
                '',
                '',
                '',
                '',
                'Total Paid',
                $totalPaidAmount
            ]);

            $writer->close();

        }, 'eo-merch-transactions.xlsx');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\TransactionMerch;
use App\Models\Event;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardMerchController extends Controller
{
    public function index(Request $request)
    {
        $transactions = $this->getAllTransactionData($request);

        // Summary
        $totalPaidAmount  = TransactionMerch::where('payment_status', 'paid')->sum('total_amount');
        $totalPaidCount   = TransactionMerch::where('payment_status', 'paid')->count();
        $totalUnpaidCount = TransactionMerch::where('payment_status', 'unpaid')->count();

        $events = Event::orderBy('title', 'asc')->get();

        return view('admin.admin-dashboard-merch', compact(
            'transactions', 'totalPaidAmount', 'totalPaidCount', 'totalUnpaidCount', 'events'
        ));
    }

    public function getAllTransactionData(Request $request)
    {
        $sortBy = $request->input('sort_by');
        $allowedSorts = ['email', 'payment_status', 'checkout_time', 'event_title', 'name'];

        $transactions = TransactionMerch::with([
                'product.event',
                'details.product',
                'details.varian',
                'details.ukuran'
            ])
            ->when($request->event_id, fn($q) =>
                $q->whereHas('product', fn($pq) => $pq->where('event_id', $request->event_id))
            )
            ->when($request->payment_status, fn($q) =>
                $q->where('payment_status', $request->payment_status)
            )
            ->when($request->q, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('email', 'like', '%' . $request->q . '%')
                          ->orWhereHas('details', fn($dq) =>
                              $dq->where('buyer_name', 'like', '%' . $request->q . '%')
                          );
                });
            })
            ->when($request->start_date && $request->end_date, function ($q) use ($request) {
                $q->whereBetween('checkout_time', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            });

        // Sorting
        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'event_title') {
                $transactions->join('products', 'transaction_merch.product_id', '=', 'products.id')
                             ->join('events', 'products.event_id', '=', 'events.id')
                             ->addSelect('transaction_merch.*')
                             ->orderBy('events.title', 'asc');
            } elseif ($sortBy !== 'name') {
                $transactions->orderBy($sortBy, 'asc');
            }
        }

        $transactions = $transactions->get();

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
        $totalPaidAmount = $transactions->where('payment_status', 'paid')->sum('total_amount');

        $pdf = Pdf::loadView('admin.export-pdf', compact('transactions', 'totalPaidAmount'));

        return $pdf->download('transactions-merch.pdf');
    }

    public function exportSimpleExcel(Request $request): StreamedResponse
    {
        $transactions = $this->getAllTransactionData($request);
        $totalPaidAmount = $transactions->where('payment_status', 'paid')->sum('total_amount');

        return response()->streamDownload(function () use ($transactions, $totalPaidAmount) {
            $writer = SimpleExcelWriter::streamDownload('transactions-merch.xlsx');

            $writer->addRow([
                'Email','Name','Phone Number','Produk','Varian','Ukuran','Quantity','Checkout Time','Paid Time','Payment Status','Total Amount'
            ]);

            foreach ($transactions as $trx) {
                if ($trx->details->isEmpty()) {
                    $writer->addRow([
                        $trx->email,'-','-','-','-','-','-',
                        $trx->checkout_time,
                        $trx->paid_time ?? '-',
                        $trx->payment_status,
                        $trx->total_amount
                    ]);
                } else {
                    foreach ($trx->details as $d) {
                        $writer->addRow([
                            $trx->email,
                            $d->buyer_name,
                            $d->buyer_phone,
                            optional($d->product)->name,
                            optional($d->varian)->varian,
                            optional($d->ukuran)->ukuran,
                            $d->quantity,
                            $trx->checkout_time,
                            $trx->paid_time ?? '-',
                            $trx->payment_status,
                            $trx->total_amount
                        ]);
                    }
                }
            }

            $writer->addRow(['','','','','','','','','','Total Paid',$totalPaidAmount]);
            $writer->close();
        }, 'transactions-merch.xlsx');
    }

    public function regenerateQR($id)
    {
        $transaction = TransactionMerch::find($id);
        if (!$transaction) {
            return back()->with('error', 'Transaksi tidak ditemukan.');
        }

        try {
            // Service QR Code untuk merch
            app(\App\Http\Controllers\WebhookController::class)->generateMerchQRCode($transaction);

            return back()->with('success', 'QR Code untuk transaksi #' . $transaction->id . ' berhasil digenerate ulang.');
        } catch (\Exception $e) {
            \Log::error('Gagal generate ulang QR merch: ' . $e->getMessage());
            return back()->with('error', 'Gagal generate ulang QR.');
        }
    }

    public function regenerateAllQR()
    {
        $transactions = TransactionMerch::where('payment_status', 'paid')->get();
        $success = 0; $failed = 0;

        foreach ($transactions as $trx) {
            try {
                app(\App\Http\Controllers\WebhookController::class)->generateMerchQRCode($trx);
                $success++;
            } catch (\Exception $e) {
                \Log::error("Gagal regenerate QR merch untuk transaksi ID {$trx->id}: " . $e->getMessage());
                $failed++;
            }
        }

    return redirect()->route('admin.merch.dashboard')
         ->with('success', "QR Code berhasil diregenerate ulang. Sukses: {$success}, Gagal: {$failed}");

    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\TicketAttendee;
use App\Models\Event; // pastikan di atas ada ini


class DashboardController extends Controller
{
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

public function index(Request $request)
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
        ->route('admin.dashboard')
        ->with('success', "QR Code berhasil diregenerate ulang. Sukses: {$success}, Gagal: {$failed}");
}



};

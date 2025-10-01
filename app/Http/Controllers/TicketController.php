<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Xendit\Xendit;
use Xendit\Invoice;


class TicketController extends Controller
{
    public function form(Request $request)
    {
        $eventId = $request->query('event_id');

        if (!$eventId) {
            abort(404, 'Event tidak ditemukan.');
        }

        $event = DB::table('events')->where('id', $eventId)->first();
        if (!$event) {
            abort(404, 'Event tidak ditemukan.');
        }

        $tickets = DB::table('tickets')->where('event_id', $eventId)->get();

        return view('ticket.form', compact('event', 'tickets'));
    }

    public function store(Request $request)
    {
        // ambil event dari salah satu ticket
        $firstTicket = DB::table('tickets')->where('id', $request->ticket_id[0] ?? null)->first();
        if (!$firstTicket) {
            return back()->with('error', 'Tiket tidak ditemukan.')->withInput();
        }

        $event = DB::table('events')->where('id', $firstTicket->event_id)->first();
        if (!$event) {
            return back()->with('error', 'Event tidak ditemukan.')->withInput();
        }

        // ✅ validasi
        $request->validate([
            'email' => [
                'required', 'email',
                function ($attribute, $value, $fail) use ($event) {
                    if ($event->max_tickets_per_email == 1) {
                        $exists = DB::table('transactions')
                            ->join('ticket_attendees', 'transactions.id', '=', 'ticket_attendees.transaction_id')
                            ->join('tickets', 'ticket_attendees.ticket_id', '=', 'tickets.id')
                            ->where('tickets.event_id', $event->id)
                            ->where('transactions.email', $value)
                            ->exists();

                        if ($exists) {
                            $fail('Email ini sudah digunakan untuk event ini.');
                        }
                    }
                }
            ],
            'name'      => 'required|array|min:1|max:' . $event->max_tickets_per_email,
            'name.*'    => 'required|string',
            'phone'     => 'array',
            'phone.*'   => 'nullable|string',
            'ticket_id'   => 'required|array',
            'ticket_id.*' => 'integer|exists:tickets,id',
            'qty'         => 'required|array',
            'qty.*'       => 'integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $total = 0;

            // kurangi stok sesuai tiap ticket_id & qty
            foreach ($request->ticket_id as $i => $ticketId) {
                $ticket = DB::table('tickets')->where('id', $ticketId)->lockForUpdate()->first();

                if (!$ticket) {
                    return back()->with('error', 'Tiket tidak ditemukan.')->withInput();
                }

                $qty = $request->qty[$i] ?? 0;
                if ($ticket->stock < $qty) {
                    return back()->with('error', "Stok tiket {$ticket->name} tidak mencukupi.")->withInput();
                }

                // update stok
                DB::table('tickets')->where('id', $ticketId)
                    ->update(['stock' => $ticket->stock - $qty]);

                $total += $ticket->price * $qty;
            }

            // simpan transaksi
            $transactionId = DB::table('transactions')->insertGetId([
                'event_id'       => $event->id,
                'email'          => $request->email,
                'checkout_time'  => now(),
                'payment_status' => $total == 0 ? 'paid' : 'unpaid',
                'kode_unik'      => strtoupper(Str::random(10)), // 👈 generate kode unik
                'total_amount'   => $total,
                'created_at'     => now(),
                'updated_at'     => now()
            ]);

            // simpan data peserta (pakai urutan order visitor)
            foreach ($request->name as $i => $name) {
                DB::table('ticket_attendees')->insert([
                    'transaction_id' => $transactionId,
                    'ticket_id'      => $request->ticket_id[$i],
                    'name'           => $name,
                    'phone_number'   => $request->phone[$i] ?? null,
                ]);
            }

            if ($total == 0) {
                // transaksi gratis → langsung paid
                $transaction = \App\Models\Transaction::find($transactionId);
                $transaction->payment_status = 'paid';
                $transaction->paid_time = now();
                $transaction->save();

                app(\App\Http\Controllers\WebhookController::class)->generateTicketQRCode($transaction);
                app(\App\Http\Controllers\WebhookController::class)->sendTicketEmail($transaction);

                DB::commit();
                return redirect()->route('ticket.success', ['id' => $transactionId])
                    ->with('success', 'Pendaftaran berhasil. Tiket telah dikirim.');
            }

            // Tiket berbayar → proses Xendit
            Xendit::setApiKey(env('XENDIT_API_KEY'));
            $externalId = 'trx-' . $transactionId . '-' . time();
            $params = [
                'external_id'         => $externalId,
                'payer_email'         => $request->email,
                'description'         => 'Pembelian Tiket ' . $event->title,
                'amount'              => $total,
                'success_redirect_url'=> route('ticket.success', ['id' => $transactionId]),
                'failure_redirect_url'=> route('ticket.failed', ['id' => $transactionId]),
                'currency'            => 'IDR',
                'invoice_duration'    => 15 * 60,
                'payment_methods'     => ['QRIS'],

            ];

            $invoice = Invoice::create($params);
            DB::table('transactions')->where('id', $transactionId)->update([
                'xendit_invoice_url' => $invoice['invoice_url'],
                'xendit_invoice_id'  => $invoice['id'],
            ]);

            DB::commit();
            return redirect()->route('ticket.payment', ['id' => $transactionId]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saat checkout: '.$e->getMessage());
            return back()->with('error', 'DB Error: '.$e->getMessage());
        }
    }

    public function payment($id)
    {
        $transaction = DB::table('transactions')->find($id);
        $details = DB::table('ticket_attendees')->where('transaction_id', $id)->get();

        if (!$transaction) {
            abort(404, 'Transaksi tidak ditemukan');
        }

        $ticket = null;
        if ($details->isNotEmpty()) {
            $ticketId = $details->first()->ticket_id;
            $ticket = DB::table('tickets')->where('id', $ticketId)->first();
        }

        $hargaTiket = $ticket ? $ticket->price : 0;
        $totalBayar = $transaction->total_amount;

        return view('ticket.payment', compact('transaction', 'details', 'hargaTiket', 'totalBayar'));
    }

    public function processPayment($id)
    {
        $transaction = DB::table('transactions')->find($id);
        if (!$transaction) {
            abort(404, 'Transaksi tidak ditemukan');
        }

        $email = $transaction->email;

        Xendit::setApiKey(env('XENDIT_API_KEY'));

        $externalId = 'trx-' . $transaction->id . '-' . time();
        $params = [
            'external_id'         => $externalId,
            'payer_email'         => $email,
            'description'         => 'Pembelian Tiket Event',
            'amount'              => $transaction->total_amount,
            'success_redirect_url'=> route('ticket.success', ['id' => $transaction->id]),
            'failure_redirect_url'=> route('ticket.failed', ['id' => $transaction->id]),
            'currency'            => 'IDR',
            'invoice_duration'    => 15 * 60,
            'payment_methods'     => ['QRIS'],
            'kode_unik'           => strtoupper(Str::random(10)), 
        ];

        $invoice = Invoice::create($params);

        DB::table('transactions')->where('id', $transaction->id)->update([
            'xendit_invoice_url' => $invoice['invoice_url'],
            'xendit_invoice_id'  => $invoice['id'],
        ]);

        return redirect($invoice['invoice_url']);
    }

    public function cancel($id)
    {
        DB::beginTransaction();
        try {
            $transaction = DB::table('transactions')->where('id', $id)->first();

            if (!$transaction) {
                return back()->with('error', 'Transaksi tidak ditemukan.');
            }
            if ($transaction->payment_status == 'paid') {
                return back()->with('error', 'Transaksi ini sudah dibayar dan tidak dapat dibatalkan.');
            }

            $attendees = DB::table('ticket_attendees')->where('transaction_id', $id)->get();
            foreach ($attendees as $attendee) {
                DB::table('tickets')->where('id', $attendee->ticket_id)->increment('stock', 1);
            }

            DB::table('ticket_attendees')->where('transaction_id', $id)->delete();
            DB::table('transactions')->where('id', $id)->delete();

            DB::commit();
            return redirect('/')->with('success', 'Transaksi berhasil dibatalkan dan stok tiket telah dikembalikan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saat membatalkan transaksi: ' . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan transaksi.');
        }
    }

    public function success($id)
    {
        $transaction = DB::table('transactions')->find($id);
        if (!$transaction) {
            abort(404, 'Transaksi tidak ditemukan');
        }

        $details = DB::table('ticket_attendees')->where('transaction_id', $id)->get();

        if ($transaction->payment_status !== 'paid') {
            $hargaTiket = $details->isNotEmpty() ? 50000 : 0; 
            $totalBayar = $transaction->total_amount;

            return view('ticket.payment', [
                'transaction'  => $transaction,
                'details'      => $details,
                'hargaTiket'   => $hargaTiket,
                'totalBayar'   => $totalBayar,
                'errorMessage' => 'Pembayaran belum terverifikasi. Silakan selesaikan pembayaran Anda.'
            ]);
        }

        return view('ticket.success', [
            'transaction' => $transaction,
            'details'     => $details
        ]);
    }

    public function failed($id)
    {
        $transaction = DB::table('transactions')->find($id);
        if (!$transaction) {
            abort(404, 'Transaksi tidak ditemukan');
        }

        $details = DB::table('ticket_attendees')->where('transaction_id', $id)->get();

        return view('ticket.failed', [
            'transaction' => $transaction,
            'details'     => $details
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Xendit\Xendit;
use Xendit\Invoice;

class TicketController extends Controller
{
    public function form(Request $request)
    {
        $eventId = $request->query('event_id');
        $jadwalId = $request->query('jadwal_id');

        if (!$eventId) {
            abort(404, 'Event tidak ditemukan.');
        }

        $event = DB::table('events')->where('id', $eventId)->first();

        if (!$event) {
            abort(404, 'Event tidak ditemukan.');
        }

        $tickets = DB::table('tickets')
            ->where('event_id', $eventId)
            ->when($jadwalId, function ($query) use ($jadwalId) {
                return $query->where('jadwal_id', $jadwalId);
            })
            ->get();

        $jadwal = null;

        if ($jadwalId) {
            $jadwal = DB::table('jadwal')
                ->where('id', $jadwalId)
                ->first();
        }

        $user = Auth::guard('user')->user();

        return view('ticket.form', compact(
            'event',
            'tickets',
            'user',
            'jadwal'
        ));
    }

    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | AMBIL TIKET PERTAMA
        |--------------------------------------------------------------------------
        */

        $firstTicket = DB::table('tickets')
            ->where('id', $request->ticket_id[0] ?? null)
            ->first();

        if (!$firstTicket) {
            return back()
                ->with('error', 'Tiket tidak ditemukan.')
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | AMBIL JADWAL
        |--------------------------------------------------------------------------
        */

        $jadwal = DB::table('jadwal')
            ->where('id', $firstTicket->jadwal_id)
            ->first();

        if (!$jadwal) {
            return back()
                ->with('error', 'Jadwal tidak ditemukan.')
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | AMBIL EVENT
        |--------------------------------------------------------------------------
        */

        $event = DB::table('events')
            ->where('id', $jadwal->event_id)
            ->first();

        if (!$event) {
            return back()
                ->with('error', 'Event tidak ditemukan.')
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'email' => [
                'required',
                'email',

                function ($attribute, $value, $fail) use ($event) {

                    if ($event->max_tickets_per_email == 1) {

                        $exists = DB::table('transactions')
                            ->join(
                                'ticket_attendees',
                                'transactions.id',
                                '=',
                                'ticket_attendees.transaction_id'
                            )
                            ->join(
                                'tickets',
                                'ticket_attendees.ticket_id',
                                '=',
                                'tickets.id'
                            )
                            ->join(
                                'jadwal',
                                'tickets.jadwal_id',
                                '=',
                                'jadwal.id'
                            )
                            ->where('jadwal.event_id', $event->id)
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

            /*
            |--------------------------------------------------------------------------
            | TOTAL HARGA TIKET
            |--------------------------------------------------------------------------
            */

            $totalTicketAmount = 0;

            foreach ($request->ticket_id as $i => $ticketId) {

                $ticket = DB::table('tickets')
                    ->where('id', $ticketId)
                    ->lockForUpdate()
                    ->first();

                if (!$ticket) {

                    DB::rollBack();

                    return back()
                        ->with('error', 'Tiket tidak ditemukan.')
                        ->withInput();
                }

                if ($ticket->jadwal_id != $jadwal->id) {

                    DB::rollBack();

                    return back()
                        ->with(
                            'error',
                            'Semua tiket harus berasal dari jadwal yang sama.'
                        )
                        ->withInput();
                }

                $qty = $request->qty[$i] ?? 0;

                if ($ticket->stock < $qty) {

                    DB::rollBack();

                    return back()
                        ->with(
                            'error',
                            "Stok tiket {$ticket->name} tidak mencukupi."
                        )
                        ->withInput();
                }

                /*
                |--------------------------------------------------------------------------
                | KURANGI STOK
                |--------------------------------------------------------------------------
                */

                DB::table('tickets')
                    ->where('id', $ticketId)
                    ->update([
                        'stock' => $ticket->stock - $qty
                    ]);

                /*
                |--------------------------------------------------------------------------
                | TOTAL TIKET
                |--------------------------------------------------------------------------
                */

                $totalTicketAmount += ($ticket->price * $qty);
            }

            /*
            |--------------------------------------------------------------------------
            | SERVICE TAX ADMIN
            |--------------------------------------------------------------------------
            */

            $servicePercent = 10;

            $serviceTax = ($totalTicketAmount * $servicePercent) / 100;

            /*
            |--------------------------------------------------------------------------
            | TOTAL DIBAYAR USER
            |--------------------------------------------------------------------------
            */

            $grandTotal = $totalTicketAmount + $serviceTax;

            /*
            |--------------------------------------------------------------------------
            | SIMPAN TRANSAKSI
            |--------------------------------------------------------------------------
            */

            $transactionId = DB::table('transactions')->insertGetId([

                'event_id'       => $event->id,
                'jadwal_id'      => $jadwal->id,

                'email'          => $request->email,

                'checkout_time'  => now(),

                'payment_status' => $totalTicketAmount == 0
                    ? 'paid'
                    : 'unpaid',

                'kode_unik'      => strtoupper(Str::random(10)),

                /*
                |--------------------------------------------------------------------------
                | MILIK EO
                |--------------------------------------------------------------------------
                */

                'total_amount'   => $totalTicketAmount,

                /*
                |--------------------------------------------------------------------------
                | MILIK PLATFORM
                |--------------------------------------------------------------------------
                */

                'service_tax'    => $serviceTax,

                /*
                |--------------------------------------------------------------------------
                | TOTAL YANG DIBAYAR USER
                |--------------------------------------------------------------------------
                */

                'grand_total'    => $grandTotal,

                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | SIMPAN PESERTA
            |--------------------------------------------------------------------------
            */

            $attendeeIndex = 0;

            foreach ($request->ticket_id as $i => $ticketId) {

                $qty = $request->qty[$i];

                for ($j = 0; $j < $qty; $j++) {

                    DB::table('ticket_attendees')->insert([

                        'transaction_id' => $transactionId,
                        'ticket_id'      => $ticketId,

                        'name'           => $request->name[$attendeeIndex] ?? null,

                        'phone_number'   => $request->phone[$attendeeIndex] ?? null,
                    ]);

                    $attendeeIndex++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | TIKET GRATIS
            |--------------------------------------------------------------------------
            */

            if ($totalTicketAmount == 0) {

                $transaction = \App\Models\Transaction::find($transactionId);

                $transaction->payment_status = 'paid';
                $transaction->paid_time = now();

                $transaction->save();

                app(\App\Http\Controllers\WebhookController::class)
                    ->generateTicketQRCode($transaction);

                app(\App\Http\Controllers\WebhookController::class)
                    ->sendTicketEmail($transaction);

                DB::commit();

                return redirect()->route(
                    'ticket.success',
                    ['id' => $transactionId]
                )->with(
                    'success',
                    'Pendaftaran berhasil. Tiket telah dikirim.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | XENDIT
            |--------------------------------------------------------------------------
            */

            Xendit::setApiKey(env('XENDIT_API_KEY'));

            $externalId = 'trx-' . $transactionId . '-' . time();

            $params = [

                'external_id' => $externalId,

                'payer_email' => $request->email,

                'description' => 'Pembelian Tiket ' . $event->title,

                /*
                |--------------------------------------------------------------------------
                | USER BAYAR TOTAL + TAX
                |--------------------------------------------------------------------------
                */

                'amount' => $grandTotal,

                'success_redirect_url' => route(
                    'ticket.success',
                    ['id' => $transactionId]
                ),

                'failure_redirect_url' => route(
                    'ticket.failed',
                    ['id' => $transactionId]
                ),

                'currency' => 'IDR',

                'invoice_duration' => 15 * 60,

                'payment_methods' => ['QRIS'],
            ];

            $invoice = Invoice::create($params);

            DB::table('transactions')
                ->where('id', $transactionId)
                ->update([

                    'xendit_invoice_url' => $invoice['invoice_url'],

                    'xendit_invoice_id'  => $invoice['id'],
                ]);

            DB::commit();

            return redirect()->route(
                'ticket.payment',
                ['id' => $transactionId]
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'Error saat checkout: ' . $e->getMessage()
            );

            return back()->with(
                'error',
                'DB Error: ' . $e->getMessage()
            );
        }
    }

    public function payment($id)
    {
        $transaction = DB::table('transactions')->find($id);

        if (!$transaction) {
            abort(404, 'Transaksi tidak ditemukan');
        }

        $details = DB::table('ticket_attendees')
            ->join(
                'tickets',
                'ticket_attendees.ticket_id',
                '=',
                'tickets.id'
            )
            ->join(
                'jadwal',
                'tickets.jadwal_id',
                '=',
                'jadwal.id'
            )
            ->where('ticket_attendees.transaction_id', $id)
            ->select(
                'ticket_attendees.*',
                'tickets.name as ticket_name',
                'tickets.price',
                'jadwal.info as jadwal_info',
                'jadwal.tanggal as jadwal_tanggal'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RINGKASAN TIKET
        |--------------------------------------------------------------------------
        */

        $ticketSummary = $details
            ->groupBy('ticket_name')
            ->map(function ($items) {

                return [
                    'qty'   => $items->count(),
                    'price' => $items->first()->price,
                    'total' => $items->count() * $items->first()->price
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | TOTAL PEMBAYARAN
        |--------------------------------------------------------------------------
        */

        $totalTiket = $transaction->total_amount;

        $servicePercent = 10;

        $serviceFee = $transaction->service_tax;

        $totalBayar = $transaction->grand_total;

        return view('ticket.payment', compact(
            'transaction',
            'details',
            'ticketSummary',
            'totalBayar',
            'serviceFee',
            'servicePercent',
            'totalTiket'
        ));
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

            'external_id' => $externalId,

            'payer_email' => $email,

            'description' => 'Pembelian Tiket Event',

            /*
            |--------------------------------------------------------------------------
            | USER BAYAR GRAND TOTAL
            |--------------------------------------------------------------------------
            */

            'amount' => $transaction->grand_total,

            'success_redirect_url' => route(
                'ticket.success',
                ['id' => $transaction->id]
            ),

            'failure_redirect_url' => route(
                'ticket.failed',
                ['id' => $transaction->id]
            ),

            'currency' => 'IDR',

            'invoice_duration' => 15 * 60,

            'payment_methods' => ['QRIS'],
        ];

        $invoice = Invoice::create($params);

        DB::table('transactions')
            ->where('id', $transaction->id)
            ->update([

                'xendit_invoice_url' => $invoice['invoice_url'],

                'xendit_invoice_id' => $invoice['id'],
            ]);

        return redirect($invoice['invoice_url']);
    }

    public function cancel($id)
    {
        DB::beginTransaction();

        try {

            $transaction = DB::table('transactions')
                ->where('id', $id)
                ->first();

            if (!$transaction) {

                return back()->with(
                    'error',
                    'Transaksi tidak ditemukan.'
                );
            }

            if ($transaction->payment_status == 'paid') {

                return back()->with(
                    'error',
                    'Transaksi ini sudah dibayar dan tidak dapat dibatalkan.'
                );
            }

            $attendees = DB::table('ticket_attendees')
                ->where('transaction_id', $id)
                ->get();

            foreach ($attendees as $attendee) {

                DB::table('tickets')
                    ->where('id', $attendee->ticket_id)
                    ->increment('stock', 1);
            }

            DB::table('ticket_attendees')
                ->where('transaction_id', $id)
                ->delete();

            DB::table('transactions')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return redirect('/')
                ->with(
                    'success',
                    'Transaksi berhasil dibatalkan dan stok tiket telah dikembalikan.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'Error saat membatalkan transaksi: ' .
                $e->getMessage()
            );

            return back()->with(
                'error',
                'Gagal membatalkan transaksi.'
            );
        }
    }

    public function success($id)
    {
        $transaction = DB::table('transactions')->find($id);

        if (!$transaction) {
            abort(404, 'Transaksi tidak ditemukan');
        }

        $details = DB::table('ticket_attendees')
            ->join(
                'tickets',
                'ticket_attendees.ticket_id',
                '=',
                'tickets.id'
            )
            ->join(
                'jadwal',
                'tickets.jadwal_id',
                '=',
                'jadwal.id'
            )
            ->where('ticket_attendees.transaction_id', $id)
            ->select(
                'ticket_attendees.*',
                'tickets.name as ticket_name',
                'tickets.price',
                'jadwal.info as jadwal_info',
                'jadwal.tanggal as jadwal_tanggal'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RINGKASAN TIKET
        |--------------------------------------------------------------------------
        */

        $ticketSummary = $details
            ->groupBy('ticket_name')
            ->map(function ($items) {

                return [
                    'qty'   => $items->count(),
                    'price' => $items->first()->price,
                    'total' => $items->count() * $items->first()->price
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $totalTiket = $transaction->total_amount;

        $servicePercent = 10;

        $serviceFee = $transaction->service_tax;

        $totalBayar = $transaction->grand_total;

        /*
        |--------------------------------------------------------------------------
        | BELUM PAID
        |--------------------------------------------------------------------------
        */

        if ($transaction->payment_status !== 'paid') {

            return view('ticket.payment', [

                'transaction'    => $transaction,

                'details'        => $details,

                'ticketSummary'  => $ticketSummary,

                'totalBayar'     => $totalBayar,

                'serviceFee'     => $serviceFee,

                'servicePercent' => $servicePercent,

                'totalTiket'     => $totalTiket,

                'errorMessage'   =>
                    'Pembayaran belum terverifikasi. Silakan selesaikan pembayaran Anda.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SUCCESS PAGE
        |--------------------------------------------------------------------------
        */

        return view('ticket.success', [

            'transaction'    => $transaction,

            'details'        => $details,

            'ticketSummary'  => $ticketSummary,

            'totalBayar'     => $totalBayar,

            'serviceFee'     => $serviceFee,

            'servicePercent' => $servicePercent,

            'totalTiket'     => $totalTiket,
        ]);
    }

    public function failed($id)
    {
        $transaction = DB::table('transactions')->find($id);

        if (!$transaction) {
            abort(404, 'Transaksi tidak ditemukan');
        }

        $details = DB::table('ticket_attendees')
            ->join(
                'tickets',
                'ticket_attendees.ticket_id',
                '=',
                'tickets.id'
            )
            ->join(
                'jadwal',
                'tickets.jadwal_id',
                '=',
                'jadwal.id'
            )
            ->where('ticket_attendees.transaction_id', $id)
            ->select(
                'ticket_attendees.*',
                'tickets.name as ticket_name',
                'jadwal.info as jadwal_info',
                'jadwal.tanggal as jadwal_tanggal'
            )
            ->get();

        return view('ticket.failed', [

            'transaction' => $transaction,

            'details'     => $details
        ]);
    }
}
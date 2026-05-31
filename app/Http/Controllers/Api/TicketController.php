<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Xendit\Xendit;
use Xendit\Invoice;

class TicketController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'jadwal_id' => 'required|exists:jadwal,id',

            'tickets' => 'required|array|min:1',
            'tickets.*.ticket_id' => 'required|exists:tickets,id',
            'tickets.*.qty' => 'required|integer|min:1',
            'tickets.*.name' => 'required|string',
            'tickets.*.phone' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $totalAmount = 0;
            $serviceTax = 0;
            $grandTotal = 0;

            /// =========================
            /// AMBIL TIKET PERTAMA
            /// =========================
            $firstTicket = DB::table('tickets')
                ->where('id', $request->tickets[0]['ticket_id'])
                ->first();

            if (!$firstTicket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tiket tidak ditemukan'
                ], 404);
            }

            /// =========================
            /// AMBIL EVENT
            /// =========================
            $event = DB::table('events')
                ->where('id', $firstTicket->event_id)
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event tidak ditemukan'
                ], 404);
            }

            /// =========================
            /// VALIDASI JADWAL
            /// =========================
            $jadwal = DB::table('jadwal')
                ->where('id', $request->jadwal_id)
                ->where('event_id', $event->id)
                ->first();

            if (!$jadwal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal event tidak valid'
                ], 400);
            }

            /// =========================
            /// VALIDASI EVENT SAMA
            /// =========================
            foreach ($request->tickets as $item) {

                $ticket = DB::table('tickets')
                    ->where('id', $item['ticket_id'])
                    ->first();

                if (!$ticket) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tiket tidak ditemukan'
                    ], 404);
                }

                if ($ticket->event_id != $event->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tiket tidak sesuai dengan event'
                    ], 400);
                }
            }

            /// =========================
            /// VALIDASI MAX TICKET
            /// =========================
            if ($event->max_tickets_per_email == 1) {

                $exists = DB::table('transactions')
                    ->where('event_id', $event->id)
                    ->where('jadwal_id', $jadwal->id)
                    ->where('email', $request->email)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email sudah pernah membeli tiket di hari ini'
                    ], 400);
                }
            }

            /// =========================
            /// CEK STOK & HITUNG TOTAL
            /// =========================
            foreach ($request->tickets as $item) {

                $ticket = DB::table('tickets')
                    ->where('id', $item['ticket_id'])
                    ->lockForUpdate()
                    ->first();

                /// VALIDASI START SALE
                if (
                    $ticket->start_sale &&
                    now()->lt($ticket->start_sale)
                ) {
                    return response()->json([
                        'success' => false,
                        'message' => "Penjualan tiket {$ticket->name} belum dimulai"
                    ], 400);
                }

                /// VALIDASI END SALE
                if (
                    $ticket->end_sale &&
                    now()->gt($ticket->end_sale)
                ) {
                    return response()->json([
                        'success' => false,
                        'message' => "Penjualan tiket {$ticket->name} sudah berakhir"
                    ], 400);
                }

                /// VALIDASI STOK
                if ($ticket->stock < $item['qty']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stok tiket {$ticket->name} tidak cukup"
                    ], 400);
                }

                /// KURANGI STOK
                DB::table('tickets')
                    ->where('id', $ticket->id)
                    ->update([
                        'stock' => $ticket->stock - $item['qty']
                    ]);

                $subtotal =
                    $ticket->price * $item['qty'];

                $totalAmount += $subtotal;
            }

            /// =========================
            /// SERVICE TAX 10%
            /// =========================
            $serviceTax = round(
                $totalAmount * 0.10
            );

            /// =========================
            /// GRAND TOTAL
            /// =========================
            $grandTotal =
                $totalAmount + $serviceTax;

            $kodeUnik = strtoupper(
                Str::random(10)
            );

            /// =========================
            /// SIMPAN TRANSACTION
            /// =========================
            $transactionId = DB::table('transactions')
                ->insertGetId([

                    'event_id' => $event->id,

                    'jadwal_id' => $jadwal->id,

                    'email' => $request->email,

                    'checkout_time' => now(),

                    'payment_status' =>
                        $grandTotal == 0
                            ? 'paid'
                            : 'unpaid',

                    'kode_unik' => $kodeUnik,

                    /// FIELD BARU
                    'total_amount' => $totalAmount,
                    'service_tax' => $serviceTax,
                    'grand_total' => $grandTotal,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            /// =========================
            /// SIMPAN ATTENDEES
            /// =========================
            foreach ($request->tickets as $item) {

                for ($i = 0; $i < $item['qty']; $i++) {

                    DB::table('ticket_attendees')
                        ->insert([

                            'transaction_id' =>
                                $transactionId,

                            'ticket_id' =>
                                $item['ticket_id'],

                            'name' =>
                                $item['name'],

                            'phone_number' =>
                                $item['phone'],

                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            /// =========================
            /// FREE TICKET
            /// =========================
            if ($grandTotal == 0) {

                DB::table('transactions')
                    ->where('id', $transactionId)
                    ->update([
                        'payment_status' => 'paid',
                        'paid_time' => now()
                    ]);

                /// UPDATE EVENT WALLET
                $this->updateEventWallet(
                    $event->id,
                    $totalAmount
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'is_free' => true,

                    'transaction_id' =>
                        $transactionId,

                    'data' => [

                        'kode_unik' =>
                            $kodeUnik,

                        'email' =>
                            $request->email,

                        'payment_status' =>
                            'paid',

                        'total_amount' =>
                            $totalAmount,

                        'service_tax' =>
                            $serviceTax,

                        'grand_total' =>
                            $grandTotal,

                        'jadwal' => [
                            'id' => $jadwal->id,
                            'info' => $jadwal->info,
                            'tanggal' => $jadwal->tanggal,
                        ],
                    ]
                ]);
            }

            /// =========================
            /// XENDIT
            /// =========================
            Xendit::setApiKey(
                env('XENDIT_API_KEY')
            );

            $externalId =
                'trx-' .
                $transactionId .
                '-' .
                time();

            $invoice = Invoice::create([

                'external_id' => $externalId,

                'payer_email' =>
                    $request->email,

                'description' =>
                    'Pembelian Tiket Event #' .
                    $event->id,

                /// PAKAI GRAND TOTAL
                'amount' => $grandTotal,

                'currency' => 'IDR',

                'invoice_duration' => 900,

                'payment_methods' => ['QRIS'],

                'success_redirect_url' =>
                    'myapp://payment-success?trx_id=' .
                    $transactionId,

                'failure_redirect_url' =>
                    'myapp://payment-failed',
            ]);

            /// =========================
            /// SIMPAN INVOICE
            /// =========================
            DB::table('transactions')
                ->where('id', $transactionId)
                ->update([
                    'xendit_invoice_url' =>
                        $invoice['invoice_url'],

                    'xendit_invoice_id' =>
                        $invoice['id'],
                ]);

            DB::commit();

            return response()->json([

                'success' => true,

                'payment_url' =>
                    $invoice['invoice_url'],

                'transaction_id' =>
                    $transactionId,

                'data' => [

                    'kode_unik' =>
                        $kodeUnik,

                    'email' =>
                        $request->email,

                    'payment_status' =>
                        'unpaid',

                    'total_amount' =>
                        $totalAmount,

                    'service_tax' =>
                        $serviceTax,

                    'grand_total' =>
                        $grandTotal,

                    'jadwal' => [
                        'id' => $jadwal->id,
                        'info' => $jadwal->info,
                        'tanggal' => $jadwal->tanggal,
                    ],
                ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Checkout Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Checkout gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function detail($id)
    {
        $trx = DB::table('transactions')
            ->where('id', $id)
            ->first();

        if (!$trx) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        $attendee = DB::table('ticket_attendees')
            ->where('transaction_id', $trx->id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'kode_unik' => $trx->kode_unik,
                'email' => $trx->email,
                'name' => $attendee->name ?? '-',
                'payment_status' => $trx->payment_status,

                'total_amount' => (int) $trx->total_amount,
                'service_tax' => (int) $trx->service_tax,
                'grand_total' => (int) $trx->grand_total,
            ]
        ]);
    }

    public function myTickets(Request $request)
    {
        $email = trim($request->query('email'));

        Log::info('MY TICKET EMAIL', [
            'email' => $email
        ]);

        $transactions = DB::table('transactions as t')

            ->leftJoin(
                'events as e',
                't.event_id',
                '=',
                'e.id'
            )

            ->leftJoin(
                'jadwal as j',
                't.jadwal_id',
                '=',
                'j.id'
            )

            ->whereRaw('LOWER(t.email) = ?', [
                strtolower($email)
            ])

            /// paid + unpaid
            ->whereIn('t.payment_status', [
                'paid',
                'unpaid'
            ])

            ->orderBy(
                't.created_at',
                'desc'
            )

            ->select(

                /// TRANSACTION
                't.id',
                't.kode_unik',
                't.qr_code',

                /// FIELD BARU
                't.total_amount',
                't.service_tax',
                't.grand_total',

                't.checkout_time',
                't.payment_status',
                't.paid_time',

                /// EVENT
                'e.title as event_title',

                /// JADWAL
                'j.id as jadwal_id',
                'j.info as jadwal_info',
                'j.tanggal as tanggal_event',
                'j.deskripsi as jadwal_deskripsi'
            )

            ->get();

        Log::info('MY TICKET RESULT', [
            'count' => $transactions->count(),
        ]);

        $result = [];

        foreach ($transactions as $trx) {

            $attendees = DB::table(
                'ticket_attendees as ta'
            )

                ->leftJoin(
                    'tickets as tk',
                    'ta.ticket_id',
                    '=',
                    'tk.id'
                )

                ->where(
                    'ta.transaction_id',
                    $trx->id
                )

                ->select(
                    'ta.id',
                    'ta.name',
                    'ta.phone_number',

                    'tk.id as ticket_id',
                    'tk.name as ticket_name',
                    'tk.price'
                )

                ->get();

            /// =========================
            /// GROUP DETAIL TIKET
            /// =========================
            $details = $attendees
                ->groupBy('ticket_name')
                ->map(function ($items, $ticketName) {

                    $first = $items->first();

                    $qty = $items->count();

                    $price = (int) $first->price;

                    return [
                        'name' => $ticketName,
                        'qty' => $qty,
                        'subtotal' => $price * $qty,
                    ];
                })
                ->values()
                ->toArray();

            $result[] = [

                'id' => $trx->id,

                'kode_unik' =>
                    $trx->kode_unik,

                'qr_code' =>
                    $trx->qr_code ??
                    $trx->kode_unik,

                /// FIELD BARU
                'total_amount' =>
                    (int) $trx->total_amount,

                'service_tax' =>
                    (int) $trx->service_tax,

                'grand_total' =>
                    (int) $trx->grand_total,

                /// UNTUK FLUTTER HISTORY
                'total' =>
                    (int) $trx->grand_total,

                'qty' =>
                    $attendees->count(),

                'title' =>
                    $trx->event_title,

                'kode' =>
                    $trx->kode_unik,

                'status' =>
                    $trx->payment_status,

                'date' =>
                    $trx->checkout_time,

                'paid_time' =>
                    $trx->paid_time,

                'details' =>
                    $details,

                /// BACKWARD COMPATIBILITY
                'total_price' =>
                    (int) $trx->grand_total,

                'checkout_time' =>
                    $trx->checkout_time,

                'payment_status' =>
                    $trx->payment_status,

                'event_title' =>
                    $trx->event_title,

                'jadwal_id' =>
                    $trx->jadwal_id,

                'jadwal_info' =>
                    $trx->jadwal_info,

                'tanggal' =>
                    $trx->tanggal_event,

                'jadwal_deskripsi' =>
                    $trx->jadwal_deskripsi,

                'attendees' =>
                    $attendees
                        ->map(function ($a) {

                            return [

                                'id' =>
                                    $a->id,

                                'name' =>
                                    $a->name,

                                'phone_number' =>
                                    $a->phone_number,

                                'ticket_id' =>
                                    $a->ticket_id,

                                'ticket_name' =>
                                    $a->ticket_name,

                                'price' =>
                                    (int) $a->price,
                            ];
                        })
                        ->values()
                        ->toArray(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
    private function updateEventWallet(
        $eventId,
        $amount
    ) {

        Log::info('UPDATE WALLET CALLED', [
            'event_id' => $eventId,
            'amount' => $amount
        ]);

        $event = DB::table('events')
            ->where('id', $eventId)
            ->first();

        if (!$event) {
            return;
        }

        $wallet = DB::table('event_wallets')
            ->where('event_id', $eventId)
            ->first();

        /// JIKA BELUM ADA WALLET
        if (!$wallet) {

            DB::table('event_wallets')
                ->insert([

                    'eo_id' => $event->eo_id,

                    'event_id' => $eventId,

                    /// MASUK KE HELD
                    'held_balance' => $amount,

                    'available_balance' => 0,

                    'negative_balance' => 0,

                    'withdraw_locked' => 0,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        } else {

            DB::table('event_wallets')

                ->where('event_id', $eventId)

                ->update([

                    'held_balance' =>
                        $wallet->held_balance + $amount,

                    'updated_at' => now(),
                ]);
        }
    }
}
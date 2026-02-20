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
            'tickets' => 'required|array|min:1',
            'tickets.*.ticket_id' => 'required|exists:tickets,id',
            'tickets.*.qty' => 'required|integer|min:1',
            'tickets.*.name' => 'required|string',
            'tickets.*.phone' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $total = 0;

            // 🔹 AMBIL TICKET PERTAMA
            $firstTicketId = $request->tickets[0]['ticket_id'];

            $firstTicket = DB::table('tickets')->where('id', $firstTicketId)->first();
            if (!$firstTicket) {
                return response()->json(['success' => false, 'message' => 'Tiket tidak ditemukan'], 404);
            }

            $event = DB::table('events')->where('id', $firstTicket->event_id)->first();

            // 🔹 VALIDASI MAX TICKET PER EMAIL
            if ($event->max_tickets_per_email == 1) {

                $exists = DB::table('transactions')
                    ->join('ticket_attendees', 'transactions.id', '=', 'ticket_attendees.transaction_id')
                    ->join('tickets', 'ticket_attendees.ticket_id', '=', 'tickets.id')
                    ->where('tickets.event_id', $event->id)
                    ->where('transactions.email', $request->email)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email ini sudah pernah membeli tiket untuk event ini'
                    ], 400);
                }
            }

            // 🔹 CEK STOK + HITUNG TOTAL
            foreach ($request->tickets as $item) {

                $ticket = DB::table('tickets')
                    ->where('id', $item['ticket_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$ticket) {
                    return response()->json(['success' => false, 'message' => 'Tiket tidak ditemukan'], 404);
                }

                if ($ticket->stock < $item['qty']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stok tiket {$ticket->name} tidak cukup"
                    ], 400);
                }

                DB::table('tickets')->where('id', $ticket->id)
                    ->update(['stock' => $ticket->stock - $item['qty']]);

                $total += $ticket->price * $item['qty'];
            }

            // 🔹 BUAT TRANSAKSI
            $transactionId = DB::table('transactions')->insertGetId([
                'event_id' => $event->id,
                'email' => $request->email,
                'checkout_time' => now(),
                'payment_status' => $total == 0 ? 'paid' : 'unpaid',
                'kode_unik' => strtoupper(Str::random(10)),
                'total_amount' => $total,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 🔹 SIMPAN ATTENDEES
            foreach ($request->tickets as $item) {
                for ($i = 0; $i < $item['qty']; $i++) {

                    DB::table('ticket_attendees')->insert([
                        'transaction_id' => $transactionId,
                        'ticket_id' => $item['ticket_id'],
                        'name' => $item['name'],
                        'phone_number' => $item['phone'],
                    ]);
                }
            }

            // 🔹 GRATIS → AUTO PAID
            if ($total == 0) {
                DB::table('transactions')->where('id', $transactionId)->update([
                    'payment_status' => 'paid',
                    'paid_time' => now()
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'is_free' => true,
                    'transaction_id' => $transactionId
                ]);
            }

            // 🔹 XENDIT INVOICE
            Xendit::setApiKey(env('XENDIT_API_KEY'));

            $invoice = Invoice::create([
                'external_id' => 'trx-' . $transactionId . '-' . time(),
                'payer_email' => $request->email,
                'description' => 'Pembelian Tiket Event',
                'amount' => $total,
                'currency' => 'IDR',
                'invoice_duration' => 15 * 60,
                'payment_methods' => ['QRIS'],
            ]);

            DB::table('transactions')->where('id', $transactionId)->update([
                'xendit_invoice_url' => $invoice['invoice_url'],
                'xendit_invoice_id' => $invoice['id'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'payment_url' => $invoice['invoice_url'],
                'transaction_id' => $transactionId
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Checkout gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

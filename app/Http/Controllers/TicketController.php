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
        Log::info('[Ticket Form] Open', ['event_id' => $eventId, 'jadwal_id' => $jadwalId]);

        if (!$eventId) {
            Log::warning('[Ticket Form] event_id kosong');
            abort(404, 'Event tidak ditemukan.');
        }

        $event = DB::table('events')->where('id', $eventId)->first();
        if (!$event) {
            Log::warning('[Ticket Form] Event tidak ditemukan', ['event_id' => $eventId]);
            abort(404, 'Event tidak ditemukan.');
        }

        $tickets = DB::table('tickets')
            ->where('event_id', $eventId)
            ->when($jadwalId, fn($q) => $q->where('jadwal_id', $jadwalId))
            ->get();
        Log::info('[Ticket Form] Tiket ditemukan', ['jumlah' => $tickets->count()]);

        $jadwal = $jadwalId ? DB::table('jadwal')->where('id', $jadwalId)->first() : null;
        if ($jadwalId && !$jadwal) {
            Log::warning('[Ticket Form] Jadwal tidak ditemukan', ['jadwal_id' => $jadwalId]);
        }

        $user = Auth::guard('user')->user();

        return view('ticket.form', compact('event', 'tickets', 'user', 'jadwal'));
    }

    // Hitung biaya layanan berdasarkan total harga tiket
    private function calcServiceTax($total)
    {
        if ($total == 0) return 0;
        if ($total <= 500000) return max(2500, ($total * 5) / 100); // 5%, min Rp2.500
        if ($total <= 1500000) return ($total * 3) / 100; // 3%
        if ($total <= 2500000) return ($total * 2) / 100; // 2%
        return 50000; // flat
    }

    // Label biaya layanan untuk ditampilkan di view
    private function serviceLabel($totalTiket, $serviceFee)
    {
        if ($totalTiket == 0) return 'Gratis';
        if ($totalTiket <= 500000) {
            $calculated = round(($totalTiket * 5) / 100);
            return ($serviceFee == 2500 && $calculated < 2500) ? 'Minimal Rp2.500' : '5%';
        }
        if ($totalTiket <= 1500000) return '3%';
        if ($totalTiket <= 2500000) return '2%';
        return 'Flat Rp50.000';
    }

    // Ambil detail peserta + ringkasan tiket untuk satu transaksi
    private function getTransactionDetails($id, $withPrice = true)
    {
        $select = ['ticket_attendees.*', 'tickets.name as ticket_name', 'jadwal.info as jadwal_info', 'jadwal.tanggal as jadwal_tanggal'];
        if ($withPrice) $select[] = 'tickets.price';

        $details = DB::table('ticket_attendees')
            ->join('tickets', 'ticket_attendees.ticket_id', '=', 'tickets.id')
            ->join('jadwal', 'tickets.jadwal_id', '=', 'jadwal.id')
            ->where('ticket_attendees.transaction_id', $id)
            ->select($select)
            ->get();

        $summary = $withPrice
            ? $details->groupBy('ticket_name')->map(fn($items) => [
                'qty' => $items->count(),
                'price' => $items->first()->price,
                'total' => $items->count() * $items->first()->price,
            ])
            : collect();

        return [$details, $summary];
    }

    public function store(Request $request)
    {
        Log::info('[Ticket Store] Mulai checkout', ['ticket_id' => $request->ticket_id, 'qty' => $request->qty, 'email' => $request->email]);

        $firstTicket = DB::table('tickets')->where('id', $request->ticket_id[0] ?? null)->first();
        if (!$firstTicket) {
            Log::warning('[Ticket Store] Tiket pertama tidak ditemukan');
            return back()->with('error', 'Tiket tidak ditemukan.')->withInput();
        }

        $jadwal = DB::table('jadwal')->where('id', $firstTicket->jadwal_id)->first();
        if (!$jadwal) {
            Log::warning('[Ticket Store] Jadwal tidak ditemukan', ['jadwal_id' => $firstTicket->jadwal_id]);
            return back()->with('error', 'Jadwal tidak ditemukan.')->withInput();
        }

        $event = DB::table('events')->where('id', $jadwal->event_id)->first();
        if (!$event) {
            Log::warning('[Ticket Store] Event tidak ditemukan', ['event_id' => $jadwal->event_id]);
            return back()->with('error', 'Event tidak ditemukan.')->withInput();
        }

        Log::info('[Ticket Store] Mulai validasi', ['event_id' => $event->id]);

        $request->validate([
            'email' => ['required', 'email', function ($attribute, $value, $fail) use ($event) {
                if ($event->max_tickets_per_email == 1) {
                    $exists = DB::table('transactions')
                        ->join('ticket_attendees', 'transactions.id', '=', 'ticket_attendees.transaction_id')
                        ->join('tickets', 'ticket_attendees.ticket_id', '=', 'tickets.id')
                        ->join('jadwal', 'tickets.jadwal_id', '=', 'jadwal.id')
                        ->where('jadwal.event_id', $event->id)
                        ->where('transactions.email', $value)
                        ->exists();
                    if ($exists) {
                        Log::warning('[Ticket Store] Email sudah dipakai untuk event ini', ['email' => $value]);
                        $fail('Email ini sudah digunakan untuk event ini.');
                    }
                }
            }],
            'name'        => 'required|array|min:1|max:' . $event->max_tickets_per_email,
            'name.*'      => 'required|string',
            'phone'       => 'array',
            'phone.*'     => 'nullable|string',
            'ticket_id'   => 'required|array',
            'ticket_id.*' => 'integer|exists:tickets,id',
            'qty'         => 'required|array',
            'qty.*'       => 'integer|min:1',
        ]);

        Log::info('[Ticket Store] Validasi sukses, mulai DB transaction');
        DB::beginTransaction();

        try {
            // Validasi & kurangi stok tiap tiket, sambil hitung total harga
            $totalTicketAmount = 0;

            foreach ($request->ticket_id as $i => $ticketId) {
                $ticket = DB::table('tickets')->where('id', $ticketId)->lockForUpdate()->first();

                if (!$ticket) {
                    Log::warning('[Ticket Store] Tiket tidak ditemukan saat lock', ['ticket_id' => $ticketId]);
                    DB::rollBack();
                    return back()->with('error', 'Tiket tidak ditemukan.')->withInput();
                }

                if ($ticket->jadwal_id != $jadwal->id) {
                    Log::warning('[Ticket Store] Tiket beda jadwal', ['ticket_id' => $ticketId]);
                    DB::rollBack();
                    return back()->with('error', 'Semua tiket harus berasal dari jadwal yang sama.')->withInput();
                }

                $qty = $request->qty[$i] ?? 0;

                if ($ticket->stock < $qty) {
                    Log::warning('[Ticket Store] Stok tidak cukup', ['ticket_id' => $ticketId, 'stock' => $ticket->stock, 'qty' => $qty]);
                    DB::rollBack();
                    return back()->with('error', "Stok tiket {$ticket->name} tidak mencukupi.")->withInput();
                }

                DB::table('tickets')->where('id', $ticketId)->update(['stock' => $ticket->stock - $qty]);
                Log::info('[Ticket Store] Stok dikurangi', ['ticket_id' => $ticketId, 'sisa' => $ticket->stock - $qty]);

                $totalTicketAmount += ($ticket->price * $qty);
            }

            // Hitung biaya layanan (milik platform) & total yang dibayar user
            $serviceTax = $this->calcServiceTax($totalTicketAmount);
            $grandTotal = $totalTicketAmount + $serviceTax;
            Log::info('[Ticket Store] Service tax dihitung', ['total' => $totalTicketAmount, 'tax' => $serviceTax]);

            $transactionId = DB::table('transactions')->insertGetId([
                'event_id'       => $event->id,
                'jadwal_id'      => $jadwal->id,
                'email'          => $request->email,
                'checkout_time'  => now(),
                'payment_status' => $totalTicketAmount == 0 ? 'paid' : 'unpaid',
                'kode_unik'      => strtoupper(Str::random(10)),
                'total_amount'   => $totalTicketAmount, // milik EO
                'service_tax'    => $serviceTax,        // milik platform
                'grand_total'    => $grandTotal,        // dibayar user
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            Log::info('[Ticket Store] Transaksi dibuat', ['transaction_id' => $transactionId, 'grand_total' => $grandTotal]);

            // Simpan tiap peserta sesuai qty per tiket
            $attendeeIndex = 0;
            foreach ($request->ticket_id as $i => $ticketId) {
                for ($j = 0; $j < $request->qty[$i]; $j++) {
                    DB::table('ticket_attendees')->insert([
                        'transaction_id' => $transactionId,
                        'ticket_id'      => $ticketId,
                        'name'           => $request->name[$attendeeIndex] ?? null,
                        'phone_number'   => $request->phone[$attendeeIndex] ?? null,
                    ]);
                    $attendeeIndex++;
                }
            }
            Log::info('[Ticket Store] Peserta disimpan', ['jumlah' => $attendeeIndex]);

            // Tiket gratis: langsung paid, generate QR & kirim email
            if ($totalTicketAmount == 0) {
                Log::info('[Ticket Store] Tiket gratis, set paid langsung', ['transaction_id' => $transactionId]);

                $transaction = \App\Models\Transaction::find($transactionId);
                $transaction->payment_status = 'paid';
                $transaction->paid_time = now();
                $transaction->save();

                try {
                    app(WebhookController::class)->generateTicketQRCode($transaction);
                    Log::info('[Ticket Store] QR Code digenerate', ['transaction_id' => $transactionId]);
                } catch (\Exception $e) {
                    Log::error('[Ticket Store] Gagal generate QR Code', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);
                    throw $e;
                }

                try {
                    app(WebhookController::class)->sendTicketEmail($transaction);
                    Log::info('[Ticket Store] Email tiket dikirim', ['transaction_id' => $transactionId]);
                } catch (\Exception $e) {
                    Log::error('[Ticket Store] Gagal kirim email tiket', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);
                    throw $e;
                }

                DB::commit();
                Log::info('[Ticket Store] Commit transaksi gratis', ['transaction_id' => $transactionId]);

                return redirect()->route('ticket.success', ['id' => $transactionId])
                    ->with('success', 'Pendaftaran berhasil. Tiket telah dikirim.');
            }

            // Tiket berbayar: buat invoice Xendit
            Log::info('[Ticket Store] Membuat invoice Xendit', ['transaction_id' => $transactionId, 'grand_total' => $grandTotal]);
            Xendit::setApiKey(env('XENDIT_API_KEY'));

            $invoice = Invoice::create([
                'external_id'           => 'trx-' . $transactionId . '-' . time(),
                'payer_email'           => $request->email,
                'description'           => 'Pembelian Tiket ' . $event->title,
                'amount'                => $grandTotal, // user bayar total + tax
                'success_redirect_url'  => route('ticket.success', ['id' => $transactionId]),
                'failure_redirect_url'  => route('ticket.failed', ['id' => $transactionId]),
                'currency'              => 'IDR',
                'invoice_duration'      => 15 * 60,
                'payment_methods'       => ['QRIS'],
            ]);
            Log::info('[Ticket Store] Invoice Xendit dibuat', ['transaction_id' => $transactionId, 'invoice_id' => $invoice['id'] ?? null]);

            DB::table('transactions')->where('id', $transactionId)->update([
                'xendit_invoice_url' => $invoice['invoice_url'],
                'xendit_invoice_id'  => $invoice['id'],
            ]);

            DB::commit();
            Log::info('[Ticket Store] Commit transaksi berbayar', ['transaction_id' => $transactionId]);

            return redirect()->route('ticket.payment', ['id' => $transactionId]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saat checkout: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'DB Error: ' . $e->getMessage());
        }
    }

    public function payment($id)
    {
        Log::info('[Ticket Payment] Buka halaman payment', ['transaction_id' => $id]);

        $transaction = DB::table('transactions')->find($id);
        if (!$transaction) {
            Log::warning('[Ticket Payment] Transaksi tidak ditemukan', ['transaction_id' => $id]);
            abort(404, 'Transaksi tidak ditemukan');
        }

        [$details, $ticketSummary] = $this->getTransactionDetails($id);

        $totalTiket = $transaction->total_amount;
        $serviceFee = $transaction->service_tax;
        $totalBayar = $transaction->grand_total;
        $serviceLabel = $this->serviceLabel($totalTiket, $serviceFee);

        Log::info('[Ticket Payment] Data dimuat', ['transaction_id' => $id, 'totalBayar' => $totalBayar, 'status' => $transaction->payment_status]);

        return view('ticket.payment', compact('transaction', 'details', 'ticketSummary', 'totalBayar', 'serviceFee', 'serviceLabel', 'totalTiket'));
    }

    public function processPayment($id)
    {
        Log::info('[Ticket ProcessPayment] Mulai ulang pembayaran', ['transaction_id' => $id]);

        $transaction = DB::table('transactions')->find($id);
        if (!$transaction) {
            Log::warning('[Ticket ProcessPayment] Transaksi tidak ditemukan', ['transaction_id' => $id]);
            abort(404, 'Transaksi tidak ditemukan');
        }

        Xendit::setApiKey(env('XENDIT_API_KEY'));

        try {
            $invoice = Invoice::create([
                'external_id'          => 'trx-' . $transaction->id . '-' . time(),
                'payer_email'          => $transaction->email,
                'description'          => 'Pembelian Tiket Event',
                'amount'               => $transaction->grand_total, // grand total
                'success_redirect_url' => route('ticket.success', ['id' => $transaction->id]),
                'failure_redirect_url' => route('ticket.failed', ['id' => $transaction->id]),
                'currency'             => 'IDR',
                'invoice_duration'     => 15 * 60,
                'payment_methods'      => ['QRIS'],
            ]);
        } catch (\Exception $e) {
            Log::error('[Ticket ProcessPayment] Gagal buat invoice Xendit', ['transaction_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }

        Log::info('[Ticket ProcessPayment] Invoice baru dibuat', ['transaction_id' => $id, 'invoice_id' => $invoice['id'] ?? null]);

        DB::table('transactions')->where('id', $transaction->id)->update([
            'xendit_invoice_url' => $invoice['invoice_url'],
            'xendit_invoice_id'  => $invoice['id'],
        ]);

        return redirect($invoice['invoice_url']);
    }

    public function cancel($id)
    {
        Log::info('[Ticket Cancel] Mulai pembatalan', ['transaction_id' => $id]);
        DB::beginTransaction();

        try {
            $transaction = DB::table('transactions')->where('id', $id)->first();
            if (!$transaction) {
                Log::warning('[Ticket Cancel] Transaksi tidak ditemukan', ['transaction_id' => $id]);
                return back()->with('error', 'Transaksi tidak ditemukan.');
            }

            if ($transaction->payment_status == 'paid') {
                Log::warning('[Ticket Cancel] Ditolak, sudah dibayar', ['transaction_id' => $id]);
                return back()->with('error', 'Transaksi ini sudah dibayar dan tidak dapat dibatalkan.');
            }

            $attendees = DB::table('ticket_attendees')->where('transaction_id', $id)->get();
            foreach ($attendees as $attendee) {
                DB::table('tickets')->where('id', $attendee->ticket_id)->increment('stock', 1);
            }
            Log::info('[Ticket Cancel] Stok dikembalikan', ['transaction_id' => $id, 'jumlah_attendee' => $attendees->count()]);

            DB::table('ticket_attendees')->where('transaction_id', $id)->delete();
            DB::table('transactions')->where('id', $id)->delete();

            DB::commit();
            Log::info('[Ticket Cancel] Commit pembatalan', ['transaction_id' => $id]);

            return redirect('/')->with('success', 'Transaksi berhasil dibatalkan dan stok tiket telah dikembalikan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saat membatalkan transaksi: ' . $e->getMessage(), ['transaction_id' => $id, 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Gagal membatalkan transaksi.');
        }
    }

    public function success($id)
    {
        Log::info('[Ticket Success] Buka halaman success', ['transaction_id' => $id]);

        $transaction = DB::table('transactions')->find($id);
        if (!$transaction) {
            Log::warning('[Ticket Success] Transaksi tidak ditemukan', ['transaction_id' => $id]);
            abort(404, 'Transaksi tidak ditemukan');
        }

        [$details, $ticketSummary] = $this->getTransactionDetails($id);

        $totalTiket = $transaction->total_amount;
        $serviceFee = $transaction->service_tax;
        $totalBayar = $transaction->grand_total;
        $serviceLabel = $this->serviceLabel($totalTiket, $serviceFee);

        $viewData = compact('transaction', 'details', 'ticketSummary', 'totalBayar', 'serviceFee', 'serviceLabel', 'totalTiket');

        if ($transaction->payment_status !== 'paid') {
            Log::warning('[Ticket Success] Belum terverifikasi, tampilkan payment', ['transaction_id' => $id, 'status' => $transaction->payment_status]);
            $viewData['errorMessage'] = 'Pembayaran belum terverifikasi. Silakan selesaikan pembayaran Anda.';
            return view('ticket.payment', $viewData);
        }

        Log::info('[Ticket Success] Terverifikasi, tampilkan success', ['transaction_id' => $id]);
        return view('ticket.success', $viewData);
    }

    public function failed($id)
    {
        Log::info('[Ticket Failed] Buka halaman failed', ['transaction_id' => $id]);

        $transaction = DB::table('transactions')->find($id);
        if (!$transaction) {
            Log::warning('[Ticket Failed] Transaksi tidak ditemukan', ['transaction_id' => $id]);
            abort(404, 'Transaksi tidak ditemukan');
        }

        [$details] = $this->getTransactionDetails($id, withPrice: false);

        return view('ticket.failed', compact('transaction', 'details'));
    }
}
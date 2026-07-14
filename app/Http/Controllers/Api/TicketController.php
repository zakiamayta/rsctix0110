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
    /// ===================================================
    /// CHECKOUT TIKET
    /// ===================================================
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
            // 📧 EMAIL PER PEMEGANG TIKET: wajib diisi agar QR tiket bisa dikirim
            // ke masing-masing pemilik tiket, bukan hanya ke email pembeli.
            'tickets.*.email' => 'required|email',
        ]);

        // 🚫 CEK DUPLIKAT NAMA / NO HP ANTAR PEMEGANG TIKET DALAM SATU TRANSAKSI
        // Setiap pemegang tiket wajib punya identitas berbeda karena tiap tiket akan
        // punya QR unik sendiri. Jika ada nama atau no HP yang sama antar attendee,
        // tolak checkout sebelum masuk ke proses DB/stok/pembayaran.
        $seenNames = [];
        $seenPhones = [];
        foreach ($request->tickets as $idx => $item) {
            $normalizedName = strtolower(trim($item['name'] ?? ''));
            $normalizedPhone = preg_replace('/\D/', '', $item['phone'] ?? '');

            if ($normalizedName !== '') {
                if (isset($seenNames[$normalizedName])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Nama pemegang tiket \"{$item['name']}\" digunakan lebih dari satu kali. Setiap pemegang tiket harus punya nama berbeda.",
                    ], 422);
                }
                $seenNames[$normalizedName] = true;
            }

            if ($normalizedPhone !== '') {
                if (isset($seenPhones[$normalizedPhone])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Nomor HP \"{$item['phone']}\" digunakan lebih dari satu kali. Setiap pemegang tiket harus punya nomor HP berbeda.",
                    ], 422);
                }
                $seenPhones[$normalizedPhone] = true;
            }
        }

        DB::beginTransaction();

        try {
            $totalAmount = 0;

            // AMBIL TIKET PERTAMA & EVENT
            $firstTicket = DB::table('tickets')->where('id', $request->tickets[0]['ticket_id'])->first();
            if (!$firstTicket) {
                return response()->json(['success' => false, 'message' => 'Tiket tidak ditemukan'], 404);
            }

            $event = DB::table('events')->where('id', $firstTicket->event_id)->first();
            if (!$event) {
                return response()->json(['success' => false, 'message' => 'Event tidak ditemukan'], 404);
            }

            // VALIDASI JADWAL
            $jadwal = DB::table('jadwal')->where('id', $request->jadwal_id)->where('event_id', $event->id)->first();
            if (!$jadwal) {
                return response()->json(['success' => false, 'message' => 'Jadwal event tidak valid'], 400);
            }

            // VALIDASI EVENT SAMA & CEK STOK
            foreach ($request->tickets as $item) {
                $ticket = DB::table('tickets')->where('id', $item['ticket_id'])->lockForUpdate()->first();
                if (!$ticket) {
                    return response()->json(['success' => false, 'message' => 'Tiket tidak ditemukan'], 404);
                }
                if ($ticket->event_id != $event->id) {
                    return response()->json(['success' => false, 'message' => 'Tiket tidak sesuai dengan event'], 400);
                }
                if ($ticket->start_sale && now()->lt($ticket->start_sale)) {
                    return response()->json(['success' => false, 'message' => "Penjualan tiket {$ticket->name} belum dimulai"], 400);
                }
                if ($ticket->end_sale && now()->gt($ticket->end_sale)) {
                    return response()->json(['success' => false, 'message' => "Penjualan tiket {$ticket->name} sudah berakhir"], 400);
                }
                if ($ticket->stock < $item['qty']) {
                    return response()->json(['success' => false, 'message' => "Stok tiket {$ticket->name} tidak cukup"], 400);
                }

                // KURANGI STOK
                DB::table('tickets')->where('id', $ticket->id)->update(['stock' => $ticket->stock - $item['qty']]);
                $totalAmount += ($ticket->price * $item['qty']);
            }

            // VALIDASI MAX TICKET PER EMAIL
            if ($event->max_tickets_per_email == 1) {
                $exists = DB::table('transactions')
                    ->where('event_id', $event->id)
                    ->where('jadwal_id', $jadwal->id)
                    ->where('email', $request->email)
                    ->exists();
                if ($exists) {
                    return response()->json(['success' => false, 'message' => 'Email sudah pernah membeli tiket di hari ini'], 400);
                }
            }

            // =========================================================================
            // 🧮 LOGIKA PERHITUNGAN BIAYA LAYANAN (SERVICE TAX) BERJENJANG ANTI-MINUS
            // =========================================================================
            if ($totalAmount == 0) {
                $serviceTax = 0;
            } elseif ($totalAmount <= 500000) {
                // Tiket Rp1 - Rp500.000: Biaya layanan 5%, minimal Rp2.500
                $calculatedTax = ($totalAmount * 5) / 100;
                $serviceTax = max(2500, $calculatedTax);
            } elseif ($totalAmount <= 1500000) {
                // Tiket Rp500.001 - Rp1.500.000: Biaya layanan 3%
                $serviceTax = ($totalAmount * 3) / 100;
            } elseif ($totalAmount <= 2500000) {
                // Tiket Rp1.500.001 - Rp2.500.000: Biaya layanan 2%
                $serviceTax = ($totalAmount * 2) / 100;
            } else {
                // Tiket di atas Rp2.500.000: Flat Rp50.000
                $serviceTax = 50000;
            }

            $serviceTax = round($serviceTax);
            $grandTotal = $totalAmount + $serviceTax;
            $kodeUnik = strtoupper(Str::random(10));

            // SIMPAN TRANSACTION
            $transactionId = DB::table('transactions')->insertGetId([
                'event_id' => $event->id,
                'jadwal_id' => $jadwal->id,
                'email' => $request->email,
                'checkout_time' => now(),
                'payment_status' => $grandTotal == 0 ? 'paid' : 'unpaid',
                'payment_method' => $grandTotal == 0 ? 'Free' : 'Xendit Gateway',
                'kode_unik' => $kodeUnik,
                'total_amount' => $totalAmount,
                'service_tax' => $serviceTax,
                'grand_total' => $grandTotal,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // SIMPAN ATTENDEES
            // 📧 Setiap attendee disimpan dengan emailnya masing-masing. Email inilah yang
            // dipakai WebhookController::sendAttendeeEmails() untuk mengirim e-tiket dengan
            // QR unik ke tiap pemegang tiket (bukan hanya ke email pembeli/emailController).
            foreach ($request->tickets as $item) {
                for ($i = 0; $i < $item['qty']; $i++) {
                    DB::table('ticket_attendees')->insert([
                        'transaction_id' => $transactionId,
                        'ticket_id' => $item['ticket_id'],
                        'jadwal_id' => $jadwal->id,
                        'name' => $item['name'],
                        'phone_number' => $item['phone'],
                        'email' => $item['email'],
                    ]);
                }
            }

            // PROSES FREE TICKET
            if ($grandTotal == 0) {
                DB::table('transactions')->where('id', $transactionId)->update([
                    'payment_status' => 'paid',
                    'paid_time' => now()
                ]);

                $this->updateEventWallet($event->id, $totalAmount);
                DB::commit();

                return response()->json([
                    'success' => true,
                    'is_free' => true,
                    'transaction_id' => $transactionId,
                    'data' => [
                        'kode_unik' => $kodeUnik,
                        'email' => $request->email,
                        'payment_status' => 'paid',
                        'payment_method' => 'Free',
                        'total_amount' => $totalAmount,
                        'service_tax' => $serviceTax,
                        'grand_total' => $grandTotal,
                        'jadwal' => ['id' => $jadwal->id, 'info' => $jadwal->info, 'tanggal' => $jadwal->tanggal],
                    ]
                ]);
            }

            // PROSES XENDIT GATEWAY
            Xendit::setApiKey(env('XENDIT_API_KEY'));
            $externalId = 'trx-' . $transactionId . '-' . time();

            $invoice = Invoice::create([
                'external_id' => $externalId,
                'payer_email' => $request->email,
                'description' => 'Pembelian Tiket Event #' . $event->id,
                'amount' => $grandTotal,
                'currency' => 'IDR',
                'invoice_duration' => 900,
                'success_redirect_url' => 'myapp://payment-success?trx_id=' . $transactionId,
                'failure_redirect_url' => 'myapp://payment-failed',
            ]);

            // UPDATE DATA INVOICE DARI XENDIT
            DB::table('transactions')->where('id', $transactionId)->update([
                'xendit_invoice_url' => $invoice['invoice_url'],
                'xendit_invoice_id' => $invoice['id'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'payment_url' => $invoice['invoice_url'],
                'transaction_id' => $transactionId,
                'data' => [
                    'kode_unik' => $kodeUnik,
                    'email' => $request->email,
                    'payment_status' => 'unpaid',
                    'payment_method' => 'Xendit Gateway',
                    'total_amount' => $totalAmount,
                    'service_tax' => $serviceTax,
                    'grand_total' => $grandTotal,
                    'jadwal' => ['id' => $jadwal->id, 'info' => $jadwal->info, 'tanggal' => $jadwal->tanggal],
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Checkout gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /// ===================================================
    /// DETAIL TRANSAKSI
    /// ===================================================
    public function detail($id)
    {
        $trx = DB::table('transactions')->where('id', $id)->first();
        if (!$trx) {
            return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan'], 404);
        }

        $attendee = DB::table('ticket_attendees')->where('transaction_id', $trx->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'kode_unik' => $trx->kode_unik,
                'email' => $trx->email,
                'name' => $attendee->name ?? '-',
                'payment_status' => $trx->payment_status,
                'payment_method' => $trx->payment_method ?? 'Xendit Gateway',
                'total_amount' => (int) $trx->total_amount,
                'service_tax' => (int) $trx->service_tax,
                'grand_total' => (int) $trx->grand_total,
            ]
        ]);
    }

    /// ===================================================
    /// HISTORY TIKET SAYA (MY TICKETS)
    /// ===================================================
    public function myTickets(Request $request)
    {
        $email = trim($request->query('email'));

        $transactions = DB::table('transactions as t')
            ->leftJoin('events as e', 't.event_id', '=', 'e.id')
            ->leftJoin('jadwal as j', 't.jadwal_id', '=', 'j.id')
            ->whereRaw('LOWER(t.email) = ?', [strtolower($email)])
            ->where('t.payment_status', 'paid')
            ->orderBy('t.created_at', 'desc')
            ->select(
                't.id', 't.kode_unik', 't.qr_code', 't.total_amount', 't.service_tax', 't.grand_total',
                't.checkout_time', 't.payment_status', 't.paid_time', 't.payment_method',
                'e.id as event_id', 'e.title as event_title', 'e.status as event_status',
                'e.reschedule_reason', 'e.date as event_date', 'e.is_rescheduled',
                'j.id as jadwal_id', 'j.info as jadwal_info', 'j.tanggal as tanggal_event', 'j.deskripsi as jadwal_deskripsi'
            )->get();

        $result = [];

        foreach ($transactions as $trx) {
            $attendees = DB::table('ticket_attendees as ta')
                ->leftJoin('tickets as tk', 'ta.ticket_id', '=', 'tk.id')
                ->where('ta.transaction_id', $trx->id)
                ->select(
                    'ta.id', 'ta.name', 'ta.phone_number', 'ta.email',
                    'ta.qr_code', 'ta.kode_unik', 'ta.is_registered',
                    'tk.id as ticket_id', 'tk.name as ticket_name', 'tk.price'
                )
                ->get();

            // AMBIL DATA REFUND TERBARU UNTUK TRANSAKSI INI (JIKA ADA)
            $refund = DB::table('refunds')
                ->where('transaction_id', $trx->id)
                ->orderBy('id', 'desc')
                ->first();
            $refundStatus = $refund->status ?? null; // waiting | pending | refunded | rejected | null

            // JIKA REFUND SUDAH SELESAI (REFUNDED), TIKET TIDAK PERLU DITAMPILKAN LAGI
            if ($refundStatus === 'refunded') {
                continue;
            }

            // STATUS EVENT & PENENTUAN APAKAH TIKET PERLU TINDAKAN (CANCEL/RESCHEDULE)
            $eventStatus = $trx->event_status ?? 'approved';
            $isCancelled = $eventStatus === 'cancelled';
            $isRescheduled = ((int) ($trx->is_rescheduled ?? 0)) >= 1 && $eventStatus === 'approved';
            $isTicketInvalid = $isCancelled || $isRescheduled;

            // "PERLU TINDAKAN" HANYA JIKA BELUM ADA PENGAJUAN REFUND, ATAU PENGAJUAN SEBELUMNYA DITOLAK
            $needsAction = $isTicketInvalid && ($refundStatus === null || $refundStatus === 'rejected');

            // CEK APAKAH EVENT SUDAH SELESAI (BERDASARKAN TANGGAL JADWAL, FALLBACK KE TANGGAL EVENT)
            $refDate = $trx->tanggal_event ?? $trx->event_date;
            $isFinished = $refDate ? now()->gt(\Carbon\Carbon::parse($refDate)->endOfDay()) : false;

            // JIKA EVENT SUDAH SELESAI SECARA NORMAL (BUKAN CANCEL/RESCHEDULE), TIKET SUDAH TIDAK BERLAKU -> SEMBUNYIKAN
            if ($isFinished && !$isTicketInvalid) {
                continue;
            }

            // GROUP DETAIL TIKET
            $details = $attendees->groupBy('ticket_name')->map(function ($items, $ticketName) {
                $first = $items->first();
                $qty = $items->count();
                return [
                    'name' => $ticketName,
                    'qty' => $qty,
                    'subtotal' => ((int) $first->price) * $qty,
                ];
            })->values()->toArray();

            // HELPER: UBAH PATH QR RELATIF MENJADI FULL URL
            $toFullQrUrl = function ($qrPath) {
                if (empty($qrPath)) return '';
                return str_starts_with($qrPath, 'http') ? $qrPath : url($qrPath);
            };

            // LOGIKA URL QR CODE (LEVEL TRANSAKSI)
            // 🔁 KONSEP BARU: QR tidak lagi dibuat per-transaksi, melainkan per-attendee
            // (lihat WebhookController::generateAttendeeQRCodes). Kolom transactions.qr_code
            // dipertahankan untuk kompatibilitas lama, tapi jika kosong kita fallback ke QR
            // milik pemegang tiket pertama supaya tampilan kartu utama tetap ada QR-nya.
            $fullQrUrl = $toFullQrUrl($trx->qr_code);
            if (empty($fullQrUrl) && $attendees->isNotEmpty()) {
                $fullQrUrl = $toFullQrUrl($attendees->first()->qr_code);
            }
            if (empty($fullQrUrl)) {
                $fullQrUrl = $trx->kode_unik;
            }

            $result[] = [
                'id' => $trx->id,
                'kode_unik' => $trx->kode_unik,
                'qr_code' => $fullQrUrl,
                'total_amount' => (int) $trx->total_amount,
                'service_tax' => (int) $trx->service_tax,
                'grand_total' => (int) $trx->grand_total,
                'total' => (int) $trx->grand_total,
                'qty' => $attendees->count(),
                'title' => $trx->event_title,
                'kode' => $trx->kode_unik,
                'status' => $trx->payment_status,
                'payment_method' => $trx->payment_method ?? 'Xendit Gateway',
                'date' => $trx->checkout_time,
                'paid_time' => $trx->paid_time,
                'details' => $details,
                'total_price' => (int) $trx->grand_total,
                'checkout_time' => $trx->checkout_time,
                'payment_status' => $trx->payment_status,
                'event_title' => $trx->event_title,
                'event_status' => $eventStatus,
                'is_rescheduled' => (int) ($trx->is_rescheduled ?? 0),
                'reschedule_reason' => $trx->reschedule_reason,
                'refund_status' => $refundStatus,
                'needs_action' => $needsAction,
                'is_finished' => $isFinished,
                'jadwal_id' => $trx->jadwal_id,
                'jadwal_info' => $trx->jadwal_info,
                'tanggal' => $trx->tanggal_event,
                'jadwal_deskripsi' => $trx->jadwal_deskripsi,
                'attendees' => $attendees->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'phone_number' => $a->phone_number,
                    // 📧 Email masing-masing pemegang tiket (tujuan pengiriman e-tiket individual)
                    'email' => $a->email,
                    // 🔳 QR code unik milik pemegang tiket ini (bukan QR bersama satu transaksi lagi)
                    'qr_code' => $toFullQrUrl($a->qr_code),
                    'kode_unik' => $a->kode_unik,
                    'is_registered' => (bool) $a->is_registered,
                    'ticket_id' => $a->ticket_id,
                    'ticket_name' => $a->ticket_name,
                    'price' => (int) $a->price,
                ])->values()->toArray(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /// ===================================================
    /// HELPER: UPDATE WALLET EVENT
    /// ===================================================
    private function updateEventWallet($eventId, $amount)
    {
        $event = DB::table('events')->where('id', $eventId)->first();
        if (!$event) return;

        $wallet = DB::table('event_wallets')->where('event_id', $eventId)->first();

        if (!$wallet) {
            DB::table('event_wallets')->insert([
                'eo_id' => $event->eo_id,
                'event_id' => $eventId,
                'held_balance' => $amount,
                'available_balance' => 0,
                'negative_balance' => 0,
                'withdraw_locked' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('event_wallets')->where('event_id', $eventId)->update([
                'held_balance' => $wallet->held_balance + $amount,
                'updated_at' => now(),
            ]);
        }
    }
}
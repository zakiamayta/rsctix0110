<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
// 🌟 PASTIKAN MODEL BERIKUT SUDAH DI-IMPORT AGAR EMAIL MERCHANDISE JALAN
use App\Models\TransactionMerch; 

class WebhookController extends Controller
{
    /// ===================================================
    /// UTAMA: MENANGKAP WEBHOOK CALLBACK DARI XENDIT
    /// ===================================================
    public function handleCallback(Request $request)
    {
        // ✅ SOLUSI TIMEOUT: Berikan kelonggaran waktu eksekusi agar pengiriman SMTP & PDF tidak putus di tengah jalan
        set_time_limit(180);

        // 🔐 VERIFIKASI TOKEN CALLBACK XENDIT (anti pemalsuan webhook)
        // Xendit menyertakan header 'x-callback-token' di setiap notifikasi. Token ini
        // dibandingkan dengan token rahasia di config (XENDIT_CALLBACK_TOKEN). Jika tidak
        // cocok, request ditolak agar pihak luar tidak bisa memalsukan status "PAID".
        // Perbandingan memakai hash_equals() agar aman dari serangan timing.
        $expectedToken = config('services.xendit.callback_token');
        if (!empty($expectedToken)) {
            $incomingToken = (string) $request->header('x-callback-token');
            if (!hash_equals((string) $expectedToken, $incomingToken)) {
                Log::warning('Xendit webhook DITOLAK: x-callback-token tidak valid.', ['ip' => $request->ip()]);
                return response()->json(['message' => 'Invalid callback token'], 403);
            }
        } else {
            Log::warning('XENDIT_CALLBACK_TOKEN belum diset — verifikasi token webhook DINONAKTIFKAN.');
        }

        $data = $request->all();
        Log::info('Xendit Webhook Received:', $data);

        if (!isset($data['id']) || !isset($data['status'])) {
            return response()->json(['message' => 'Invalid webhook data'], 400);
        }

        if (strtoupper($data['status']) === 'PAID') {
            $invoiceId = trim($data['id']);
            $paymentChannel = $data['payment_channel'] ?? ($data['payment_method'] ?? 'Xendit Gateway');

            // 🎟️ 1. CEK TRANSAKSI TIKET
            $transaction = \App\Models\Transaction::where('xendit_invoice_id', $invoiceId)->first();
            if ($transaction) {
                // 🔒 IDEMPOTEN: Xendit dapat mengirim callback PAID berkali-kali (retry).
                // Transisi 'unpaid' → 'paid' dikunci di level query: bila tidak ada baris yang
                // berubah, berarti sudah pernah diproses → JANGAN kirim email & QR lagi.
                $affected = \App\Models\Transaction::where('id', $transaction->id)
                    ->where(function ($q) {
                        $q->where('payment_status', 'unpaid')->orWhereNull('payment_status');
                    })
                    ->update([
                        'payment_status' => 'paid',
                        'paid_time' => now(),
                        'payment_method' => $paymentChannel,
                    ]);

                if ($affected === 0) {
                    Log::info('Webhook PAID tiket duplikat diabaikan (sudah diproses).', ['invoice_id' => $invoiceId]);
                    return response()->json(['message' => 'Ticket already processed'], 200);
                }

                $transaction->refresh();
                DB::table('platform_wallets')->where('id', 1)->update([
                'total_service_tax_earned' => DB::raw("total_service_tax_earned + {$transaction->service_tax}"),
                'current_balance'          => DB::raw("current_balance + {$transaction->service_tax}"),
            ]);
                $this->generateAttendeeQRCodes($transaction);
                $this->sendAttendeeEmails($transaction);
                return response()->json(['message' => 'Ticket transaction updated'], 200);
            }

            // 👕 2. CEK TRANSAKSI MERCHANDISE (DIUBAH KE ELOQUENT MODEL BIAR TIDAK TYPE ERROR)
            $merch = TransactionMerch::where('xendit_invoice_id', $invoiceId)->first();
            if ($merch) {
                // 🔒 IDEMPOTEN: sama seperti tiket, cegah pemrosesan ganda saat Xendit retry.
                // Catatan: tabel transaction_merch TIDAK punya kolom payment_method, jadi tidak diset.
                $affected = TransactionMerch::where('id', $merch->id)
                    ->where(function ($q) {
                        $q->where('payment_status', 'unpaid')->orWhereNull('payment_status');
                    })
                    ->update([
                        'payment_status' => 'paid',
                        'paid_time' => now(),
                    ]);

                if ($affected === 0) {
                    Log::info('Webhook PAID merch duplikat diabaikan (sudah diproses).', ['invoice_id' => $invoiceId]);
                    return response()->json(['message' => 'Merch already processed'], 200);
                }

                // Ambil data terbaru berbasis Eloquent Model, bukan stdClass mentah lagi
                $updatedMerch = TransactionMerch::with([
                    'details.product',
                    'details.varian',
                    'details.ukuran',
                ])->find($merch->id);
                
                $this->generateMerchQRCode($updatedMerch);
                $this->sendMerchEmail($updatedMerch);
                return response()->json(['message' => 'Merch transaction updated'], 200);
            }

            Log::warning('Transaction not found for invoice ID: ' . $invoiceId);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // ❌ INVOICE KEDALUWARSA / GAGAL → LEPASKAN KEMBALI STOK TIKET YANG TERTAHAN
        // Saat checkout, stok tiket langsung dipotong walau status masih 'unpaid'. Jika
        // pembeli tidak jadi membayar dan invoice Xendit kedaluwarsa, stok tersebut harus
        // dikembalikan. Hanya transaksi TIKET yang memotong stok (merch tidak), sehingga
        // hanya tabel transactions yang perlu diproses di sini.
        if (in_array(strtoupper($data['status']), ['EXPIRED', 'FAILED'], true)) {
            $invoiceId = trim($data['id']);

            $transaction = \App\Models\Transaction::where('xendit_invoice_id', $invoiceId)->first();

            if ($transaction) {
                $released = $transaction->releaseExpiredStock();
                Log::info('Xendit invoice expired/failed, pelepasan stok tiket', [
                    'invoice_id'     => $invoiceId,
                    'stock_released' => $released,
                ]);
                return response()->json(['message' => 'Ticket stock released'], 200);
            }

            return response()->json(['message' => 'No unpaid ticket to release'], 200);
        }

        return response()->json(['message' => 'Ignored webhook'], 200);
    }

    /// ===================================================
    /// HELPER: GENERATE QR CODE TIKET
    /// ===================================================
    // public function generateTicketQRCode($transaction)
    // {
    //     try {
    //         $qrPath = public_path('images/qrcodes');
    //         if (!File::exists($qrPath)) File::makeDirectory($qrPath, 0755, true);

    //         $qrData = route('absen.form', ['kode' => $transaction->kode_unik]);
    //         $qrFileName = 'ticket_' . $transaction->kode_unik . '.png';
    //         $qrFullPath = $qrPath . '/' . $qrFileName;

    //         QrCode::format('png')->size(300)->generate($qrData, $qrFullPath);

    //         $transaction->qr_code = 'images/qrcodes/' . $qrFileName;
    //         $transaction->save();
    //     } catch (\Exception $e) {
    //         Log::error('Failed to generate QR Code Tiket: ' . $e->getMessage());
    //     }
    // }

    public function generateAttendeeQRCodes($transaction)
{
    try {
        $qrPath = public_path('images/qrcodes');
        if (!File::exists($qrPath)) File::makeDirectory($qrPath, 0755, true);

        $attendees = DB::table('ticket_attendees')->where('transaction_id', $transaction->id)->get();

        foreach ($attendees as $attendee) {
            $kode = $attendee->kode_unik;
            if (!$kode) {
                $kode = strtoupper(\Illuminate\Support\Str::random(12));
                DB::table('ticket_attendees')->where('id', $attendee->id)->update(['kode_unik' => $kode]);
            }

            $qrData = route('absen.form', ['kode' => $kode]);
            $qrFileName = 'ticket_' . $kode . '.png';
            $qrFullPath = $qrPath . '/' . $qrFileName;

            QrCode::format('png')->size(300)->generate($qrData, $qrFullPath);

            DB::table('ticket_attendees')->where('id', $attendee->id)->update([
                'qr_code' => 'images/qrcodes/' . $qrFileName,
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Gagal generate QR per peserta: ' . $e->getMessage());
    }
}

public function sendAttendeeEmails($transaction)
{
    ini_set('memory_limit', '-1');

    $transaction = \App\Models\Transaction::with(['event', 'attendees.ticket.jadwal'])->find($transaction->id);

    foreach ($transaction->attendees as $attendee) {
        if (!$attendee->email) {
            Log::warning('Peserta tanpa email, lewati pengiriman', ['attendee_id' => $attendee->id]);
            continue;
        }

        try {
            Mail::to($attendee->email)->send(new \App\Mail\TicketWithPDF($transaction, $attendee));
            Log::info('Email tiket terkirim ke peserta', ['attendee_id' => $attendee->id, 'email' => $attendee->email]);
        } catch (\Exception $e) {
            Log::error('Gagal kirim email peserta: ' . $e->getMessage(), ['attendee_id' => $attendee->id]);
        }
    }
}

    /// ===================================================
    /// HELPER: GENERATE QR CODE MERCHANDISE
    /// ===================================================
    public function generateMerchQRCode($transaction)
    {
        try {
            $qrPath = public_path('images/qrcodes_merch');
            if (!File::exists($qrPath)) File::makeDirectory($qrPath, 0755, true);

            $qrData = route('guests.merch.qr', ['kode_unik' => $transaction->kode_unik]); 
            $qrFileName = 'merch_' . $transaction->kode_unik . '.png';
            $qrFullPath = $qrPath . '/' . $qrFileName;

            QrCode::format('png')->size(300)->generate($qrData, $qrFullPath);

            DB::table('transaction_merch')
                ->where('id', $transaction->id)
                ->update(['qr_code' => 'images/qrcodes_merch/' . $qrFileName]);
        } catch (\Exception $e) {
            Log::error('Failed to generate QR Code Merch: ' . $e->getMessage());
        }
    }

    /// ===================================================
    /// HELPER: KIRIM EMAIL TIKET & MERCHANDISE
    /// ===================================================
    // public function sendTicketEmail($transaction)
    // {
    //     ini_set('memory_limit', '-1');

    //     try {

    //         Log::info('START SEND EMAIL');

    //         $transaction =
    //         \App\Models\Transaction::with([
    //             'event',
    //             'attendees.ticket.jadwal'
    //         ])->find($transaction->id);

    //         Log::info('EMAIL TARGET: '.$transaction->email);

    //         Mail::to($transaction->email)
    //             ->send(
    //                 new \App\Mail\TicketWithPDF($transaction)
    //             );

    //         Log::info('EMAIL SUCCESS');

    //     } catch (\Exception $e) {

    //         Log::error(
    //             'Failed to send ticket email: '
    //             .$e->getMessage()
    //         );
    //     }
    // }

    public function sendMerchEmail($merch)
    {
        try {
            // $merch di sini otomatis aman diparsing karena tipenya sudah sesuai dengan target Mailable
            Mail::to($merch->email)->send(new \App\Mail\MerchInvoiceWithPDF($merch));
        } catch (\Exception $e) {
            Log::error('Failed to send merch email: ' . $e->getMessage());
        }
    }
}   
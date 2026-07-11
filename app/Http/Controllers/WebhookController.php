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
                $transaction->update([
                    'payment_status' => 'paid',
                    'paid_time' => now(),
                    'payment_method' => $paymentChannel,
                ]);

                $this->generateTicketQRCode($transaction);
                $this->sendTicketEmail($transaction);
                return response()->json(['message' => 'Ticket transaction updated'], 200);
            }

            // 👕 2. CEK TRANSAKSI MERCHANDISE (DIUBAH KE ELOQUENT MODEL BIAR TIDAK TYPE ERROR)
            $merch = TransactionMerch::where('xendit_invoice_id', $invoiceId)->first();
            if ($merch) {
                $merch->update([
                    'payment_status' => 'paid',
                    'paid_time' => now(),
                    'payment_method' => $paymentChannel,
                ]);

                // Ambil data terbaru berbasis Eloquent Model, bukan stdClass mentah lagi
                $updatedMerch = TransactionMerch::find($merch->id);
                
                $this->generateMerchQRCode($updatedMerch);
                $this->sendMerchEmail($updatedMerch);
                return response()->json(['message' => 'Merch transaction updated'], 200);
            }

            Log::warning('Transaction not found for invoice ID: ' . $invoiceId);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        return response()->json(['message' => 'Ignored webhook'], 200);
    }

    /// ===================================================
    /// HELPER: GENERATE QR CODE TIKET
    /// ===================================================
    public function generateTicketQRCode($transaction)
    {
        try {
            $qrPath = public_path('images/qrcodes');
            if (!File::exists($qrPath)) File::makeDirectory($qrPath, 0755, true);

            $qrData = route('absen.form', ['kode' => $transaction->kode_unik]);
            $qrFileName = 'ticket_' . $transaction->kode_unik . '.png';
            $qrFullPath = $qrPath . '/' . $qrFileName;

            QrCode::format('png')->size(300)->generate($qrData, $qrFullPath);

            $transaction->qr_code = 'images/qrcodes/' . $qrFileName;
            $transaction->save();
        } catch (\Exception $e) {
            Log::error('Failed to generate QR Code Tiket: ' . $e->getMessage());
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
    public function sendTicketEmail($transaction)
    {
        ini_set('memory_limit', '-1');
        try {
            $transaction = \App\Models\Transaction::with(['event', 'attendees.ticket.jadwal'])->find($transaction->id);
            Mail::to($transaction->email)->send(new \App\Mail\TicketWithPDF($transaction));
        } catch (\Exception $e) {
            Log::error('Failed to send ticket email: ' . $e->getMessage());
        }
    }

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
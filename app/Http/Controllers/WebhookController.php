<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketEmail;

class WebhookController extends Controller
{
    public function generateTicketQRCode($transaction)
    {
        try {
            $qrPath = base_path('public_html/qrcodes');
            if (!File::exists($qrPath)) {
                File::makeDirectory($qrPath, 0755, true);
            }

            $qrData = route('absen.form', ['kode' => $transaction->kode_unik]);
            $qrFileName = 'ticket_' . $transaction->kode_unik . '.png';
            $qrFullPath = $qrPath . '/' . $qrFileName;

            QrCode::format('png')
                ->size(300)
                ->generate($qrData, $qrFullPath);

            $transaction->qr_code = 'qrcodes/' . $qrFileName;
            $transaction->save();

            Log::info('QR Code generated and saved', [
                'transaction_id' => $transaction->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate QR Code', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id
            ]);
        }
    }

    public function handleCallback(Request $request)
{
    $data = $request->all();
    Log::info('Xendit Webhook Received:', $data);

    if (!isset($data['id']) || !isset($data['status'])) {
        return response()->json(['message' => 'Invalid webhook data'], 400);
    }

    if (strtoupper($data['status']) === 'PAID') {
        // 🔹 cek transaksi tiket 
        $transaction = \App\Models\Transaction::where('xendit_invoice_id', trim($data['id']))->first();
        if ($transaction) {
            $transaction->update([
                'payment_status' => 'paid',
                'paid_time' => now(),
            ]);

            $this->generateTicketQRCode($transaction);
            $this->sendTicketEmail($transaction);

            return response()->json(['message' => 'Ticket transaction updated'], 200);
        }

        // 🔹 cek transaksi merch
        $merch = \App\Models\TransactionMerch::where('xendit_invoice_id', trim($data['id']))->first();
        if ($merch) {
            $merch->update([
                'payment_status' => 'paid',
                'paid_time' => now(),
            ]);

            $this->generateMerchQRCode($merch);
            $this->sendMerchEmail($merch);


            return response()->json(['message' => 'Merch transaction updated'], 200);
        }

        // 🔹 kalau tidak ketemu di dua tabel
        Log::warning('Transaction not found for invoice ID: ' . $data['id']);
        return response()->json(['message' => 'Transaction not found'], 404);
    }

    return response()->json(['message' => 'Ignored webhook'], 200);
}

    public function generateMerchQRCode($transaction)
    {
        try {
            $qrPath = base_path('public_html/qrcodes_merch');
            if (!File::exists($qrPath)) {
                File::makeDirectory($qrPath, 0755, true);
            }

            // QR menuju halaman detail merch (misalnya ke guest view merch)
$qrData = route('guests.merch.qr', ['kode_unik' => $transaction->kode_unik]); 
$qrFileName = 'merch_' . $transaction->kode_unik . '.png';
            $qrFullPath = $qrPath . '/' . $qrFileName;

            QrCode::format('png')
                ->size(300)
                ->generate($qrData, $qrFullPath);

            $transaction->qr_code = 'qrcodes_merch/' . $qrFileName;
            $transaction->save();

            Log::info('Merch QR Code generated', [
                'transaction_merch_id' => $transaction->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate Merch QR Code', [
                'error' => $e->getMessage(),
                'transaction_merch_id' => $transaction->id
            ]);
        }
    }

    public function sendTicketEmail($transaction)
{
    try {
        Mail::to($transaction->email)->send(new \App\Mail\TicketWithPDF($transaction));
        Log::info('Ticket email sent successfully', [
            'transaction_id' => $transaction->id
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to send ticket email', [
            'error' => $e->getMessage(),
            'transaction_id' => $transaction->id
        ]);
    }
}

public function sendMerchEmail($merch)
{
    try {
        Mail::to($merch->email)->send(new \App\Mail\MerchInvoiceWithPDF($merch));
        Log::info('Merch email sent successfully', [
            'transaction_merch_id' => $merch->id
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to send merch email', [
            'error' => $e->getMessage(),
            'transaction_merch_id' => $merch->id
        ]);
    }
}



}

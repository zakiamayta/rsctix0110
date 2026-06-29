<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionMerch;

class BuyerMerchRefundController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
            }
            return $next($request);
        });
    }

    /**
     * 📄 Form Refund Khusus Merchandise
     */
    public function create($id)
    {
        $user = auth()->user();

        // Ambil transaksi merch beserta relasi event melalui product detailnya
        $transaction = TransactionMerch::with(['details.product.event'])
            ->where('id', $id)
            ->where('email', $user->email)
            ->where('payment_status', 'paid')
            ->first();

        if (!$transaction) {
            abort(404, 'Transaksi merchandise tidak valid atau belum dibayar.');
        }

        // Ambil data event terkait dari item pertama pesanan merch
        $firstDetail = $transaction->details->first();
        $event = $firstDetail && $firstDetail->product ? $firstDetail->product->event : null;

        if (!$event) {
            abort(404, 'Data Event terkait merchandise tidak ditemukan.');
        }

        // SYARAT REFUND MERCHANDISE: Event Cancelled DAN EO Memilih Opsi 'refund'
        if ($event->status !== 'cancelled' || $event->merch_cancel_decision !== 'refund') {
            return redirect()->route('user.merch')->with('error', 'Komoditas merchandise tidak memenuhi syarat refund atau diselesaikan mandiri oleh EO.');
        }

        // Cek double input pengajuan refund khusus tabel merch_refunds (atau sesuaikan nama tabel Anda)
        $existing = DB::table('merch_refunds')->where('transaction_merch_id', $id)->first();
        if ($existing) {
            return redirect()->route('user.merch')->with('error', 'Anda sudah mengajukan refund untuk merchandise ini.');
        }

        // Tambahkan atribut judul event untuk dipakai di view form nanti
        $transaction->event_title = $event->title;

        return view('user.refund-merch-form', compact('transaction'));
    }

    /**
     * 💾 Simpan Data Pengajuan Refund Merchandise
     */
    public function store(Request $request, $id)
    {
        $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:150',
            'refund_reason'  => 'nullable|string',
        ]);

        $user = auth()->user();

        $transaction = TransactionMerch::with(['details.product.event'])
            ->where('id', $id)
            ->where('email', $user->email)
            ->where('payment_status', 'paid')
            ->first();

        if (!$transaction) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $firstDetail = $transaction->details->first();
        $event = $firstDetail && $firstDetail->product ? $firstDetail->product->event : null;

        if (!$event || $event->status !== 'cancelled' || $event->merch_cancel_decision !== 'refund') {
            return redirect()->route('user.merch')->with('error', 'Gagal memproses. Aturan penyelesaian komoditas tidak valid.');
        }

        // Proteksi double submit
        $existing = DB::table('merch_refunds')->where('transaction_merch_id', $id)->first();
        if ($existing) {
            return redirect()->route('user.merch')->with('error', 'Anda sudah mengajukan refund untuk merchandise ini.');
        }

        // Samakan sistem Gerbang Batch seperti pada Tiket Anda
        $openBatch = DB::table('refund_batches')
            ->where('event_id', $event->id)
            ->where('status', 'open')
            ->first();

        // Ambil nominal murni dari total belanja merchandise
        $pureAmountToBuyer = $transaction->total_amount; 
        $refundTaxFee = 2500; 

        if ($openBatch) {
            $batchId = $openBatch->id;
            $statusRefund = 'pending';
            $successMessage = 'Pengajuan refund merchandise berhasil! Masuk ke antrean ' . $openBatch->name . '.';
        } else {
            $batchId = null;
            $statusRefund = 'waiting';
            $successMessage = 'Pengajuan refund merchandise berhasil diterima ke antrean berkas (Waiting List).';
        }

        // Lakukan simpan ke tabel pengembalian dana merchandise (merch_refunds)
        DB::table('merch_refunds')->insert([
            'refund_batch_id'      => $batchId, 
            'transaction_merch_id' => $transaction->id, // Mengikat ke ID transaksi merch
            'bank_name'            => $request->bank_name,
            'account_number'       => $request->account_number,
            'account_name'         => $request->account_name,
            'refund_reason'        => $request->refund_reason,
            'grand_total_refunded' => $pureAmountToBuyer,
            'refunds_tax'          => $refundTaxFee,
            'status'               => $statusRefund,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return redirect()->route('user.merch')->with('success', $successMessage);
    }
}
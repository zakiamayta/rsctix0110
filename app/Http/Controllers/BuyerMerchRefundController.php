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

        $transaction = TransactionMerch::with(['details.product.event'])
            ->where('id', $id)
            ->where('email', $user->email)
            ->where('payment_status', 'paid')
            ->first();

        if (!$transaction) {
            abort(404, 'Transaksi merchandise tidak valid atau belum dibayar.');
        }

        $firstDetail = $transaction->details->first();
        $event = $firstDetail && $firstDetail->product ? $firstDetail->product->event : null;

        if (!$event) {
            abort(404, 'Data Event terkait merchandise tidak ditemukan.');
        }

        if ($event->status !== 'cancelled' || $event->merch_cancel_decision !== 'refund') {
            return redirect()->route('user.merch')->with('error', 'Komoditas merchandise tidak memenuhi syarat refund atau diselesaikan mandiri oleh EO.');
        }

        $existing = DB::table('refunds')->where('transaction_merch_id', $id)->orderByDesc('created_at')->first();
        if ($existing && $existing->status !== 'rejected') {
            return redirect()->route('user.merch')->with('error', 'Anda sudah mengajukan refund untuk merchandise ini.');
        }

        $transaction->event_title = $event->title;

        return view('user.refund-merch-form', compact('transaction'));
    }

    /**
     * 💾 Simpan Data Pengajuan Refund Merchandise ke Tabel Terpusat (refunds)
     */
    public function store(Request $request, $id)
    {
        $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:150',
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

        $existing = DB::table('refunds')->where('transaction_merch_id', $id)->orderByDesc('created_at')->first();
        if ($existing && $existing->status !== 'rejected') {
            return redirect()->route('user.merch')->with('error', 'Anda sudah mengajukan refund untuk merchandise ini.');
        }

        // ✅ PERBAIKAN: Wajib filter ->where('type', 'merch') agar tidak salah masuk ke batch tiket
        $openBatch = DB::table('refund_batches')
            ->where('event_id', $event->id)
            ->where('type', 'merch') 
            ->where('status', 'open')
            ->first();

        // ✅ BENAR: total_amount murni harga barang berdasarkan skema DB Anda
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

        DB::table('refunds')->insert([
            'refund_batch_id'      => $batchId, 
            'transaction_id'       => null, 
            'transaction_merch_id' => $transaction->id, 
            'bank_name'            => $request->bank_name,
            'account_number'       => $request->account_number,
            'account_name'         => $request->account_name,
            'grand_total_refunded' => $pureAmountToBuyer,
            'refunds_tax'          => $refundTaxFee,
            'status'               => $statusRefund,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return redirect()->route('user.merch')->with('success', $successMessage);
    }
}
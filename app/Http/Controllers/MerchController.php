<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductVarian;
use App\Models\TransactionMerch;
use App\Models\TransactionMerchDetail;
use Xendit\Xendit;
use Illuminate\Support\Str;


class MerchController extends Controller
{
    public function index()
    {
        $varians = ProductVarian::with(['product', 'ukurans'])->get();
        return view('merch.index', compact('varians'));
    }
    public function preview(Request $request)
    {
        $orderData = $request->all();
        $orderData['buyer_name'] = $orderData['buyer_name'] ?? '';
        $orderData['email']      = $orderData['email'] ?? '';
        $orderData['buyer_phone']= $orderData['buyer_phone'] ?? '';
        $orderData['items']      = $orderData['items'] ?? [];

        session(['orderData' => $orderData]);

        return view('merch.checkout', compact('orderData'));
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'email'       => 'required|email',
            'buyer_name'  => 'required|string|max:255',
            'buyer_phone' => 'required|string|max:20',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.varian_id'  => 'required|integer|exists:products_varian,id',
            'items.*.ukuran_id'  => 'nullable|integer|exists:products_ukuran,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.price'      => 'required|integer|min:0',
            'items.*.subtotal'   => 'required|integer|min:0',
        ]);

        $total = collect($validated['items'])->sum('subtotal');

        // 🔹 Simpan transaksi utama (tanpa product_id karena tabelnya sudah tidak pakai)
        $transaction = TransactionMerch::create([
            'email'          => $validated['email'],
            'total_amount'   => $total,
            'payment_status' => 'unpaid',
            'kode_unik'      => strtoupper(Str::random(10))
        ]);

        // 🔹 Simpan detail item
        foreach ($validated['items'] as $item) {
            TransactionMerchDetail::create([
                'transaction_merch_id' => $transaction->id,
                'buyer_name'           => $validated['buyer_name'],
                'buyer_phone'          => $validated['buyer_phone'],
                'product_id'           => $item['product_id'],
                'varian_id'            => $item['varian_id'],
                'ukuran_id'            => $item['ukuran_id'] ?? null,
                'quantity'             => $item['quantity'],
                'price'                => $item['price'],
                'subtotal'             => $item['subtotal'],
            ]);
        }

        // 🔹 Integrasi Xendit
        Xendit::setApiKey(env('XENDIT_API_KEY'));

        $params = [
            'external_id' => 'merch-' . $transaction->id,
            'payer_email' => $validated['email'],
            'description' => 'Pembelian Merchandise',
            'amount'      => $total,
            'success_redirect_url' => route('merch.success', $transaction->id),
            'failure_redirect_url' => route('merch.failed', $transaction->id),
        ];

        $invoice = \Xendit\Invoice::create($params);

        // simpan invoice_id & url ke DB
        $transaction->update([
            'xendit_invoice_id' => $invoice['id'],
            'xendit_invoice_url'=> $invoice['invoice_url'],
        ]);

        return redirect($invoice['invoice_url']);
    }

public function success($id)
{
    $transaction = TransactionMerch::findOrFail($id);

    if ($transaction->payment_status !== 'paid') {
        // Webhook belum masuk, jadi jangan dianggap berhasil
        return view('merch.failed', compact('transaction'))
            ->with('message', 'Menunggu verifikasi pembayaran dari Xendit...');
    }

    return view('merch.success', compact('transaction'));
}



    public function failed($id)
    {
        $transaction = TransactionMerch::findOrFail($id);
        $transaction->update(['payment_status' => 'failed']);
        return view('merch.failed', compact('transaction'));
    }


public function showQr($kode_unik)
{
    $transaction = \App\Models\TransactionMerch::where('kode_unik', $kode_unik)
        ->with('details.product')
        ->firstOrFail();

    \Log::info('Menampilkan QR untuk transaksi merch', [
        'transaction_kode_unik' => $transaction->kode_unik,
        'email'                 => $transaction->email,
        'status'                => $transaction->payment_status,
    ]);

    return view('admin.merch-qr', compact('transaction'));
}

}

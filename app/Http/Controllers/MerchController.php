<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductVarian;
use App\Models\TransactionMerch;
use App\Models\TransactionMerchDetail;
use Xendit\Xendit;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MerchController extends Controller
{
public function index($eventId)
{
    $event = \App\Models\Event::findOrFail($eventId);

    $varians = ProductVarian::with([
            'product',
            'ukurans'
        ])
        ->whereHas('product', function ($q) use ($eventId) {
            $q->where('event_id', $eventId);
        })
        ->get();

    $user = auth()->user();

    return view('merch.index', [
        'varians' => $varians,
        'event' => $event,
        'user' => $user
    ]);
}

    // Hitung biaya layanan berdasarkan total harga barang
    // (logikanya sama persis dengan TicketController::calcServiceTax)
    private function calcServiceTax($total)
    {
        if ($total == 0) return 0;
        if ($total <= 500000) return max(2500, ($total * 5) / 100); // 5%, min Rp2.500
        if ($total <= 1500000) return ($total * 3) / 100; // 3%
        if ($total <= 2500000) return ($total * 2) / 100; // 2%
        return 50000; // flat
    }

    // Label biaya layanan untuk ditampilkan di view
    // (logikanya sama persis dengan TicketController::serviceLabel)
    private function serviceLabel($totalAmount, $serviceFee)
    {
        if ($totalAmount == 0) return 'Gratis';
        if ($totalAmount <= 500000) {
            $calculated = round(($totalAmount * 5) / 100);
            return ($serviceFee == 2500 && $calculated < 2500) ? 'Minimal Rp2.500' : '5%';
        }
        if ($totalAmount <= 1500000) return '3%';
        if ($totalAmount <= 2500000) return '2%';
        return 'Flat Rp50.000';
    }

public function preview(Request $request)
{
    $orderData = $request->all();
    $orderData['buyer_name']  = $orderData['buyer_name'] ?? '';
    $orderData['email']       = $orderData['email'] ?? '';
    $orderData['buyer_phone'] = $orderData['buyer_phone'] ?? '';
    $orderData['items']       = $orderData['items'] ?? [];

    session(['orderData' => $orderData]);

    return redirect()->route('merch.checkout.show'); // redirect ke GET route baru
}

public function showCheckout()
{
    $orderData = session('orderData');

    if (!$orderData) {
        return redirect()->route('home')->with('error', 'Sesi pesanan tidak ditemukan, silakan ulangi pemesanan.');
    }

    $event = null;
    if (!empty($orderData['event_id'])) {
        $event = \App\Models\Event::find($orderData['event_id']);
    }

    return view('merch.checkout', compact('orderData', 'event'));
}

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'email'       => 'required|email',
            'buyer_name'  => 'required|string|max:255',
            'buyer_phone' => 'required|string|max:20',

            'items' => 'required|array|min:1',

            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.varian_id'  => 'required|integer|exists:products_varian,id',
            'items.*.ukuran_id'  => 'nullable|integer|exists:products_ukuran,id',

            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.price'      => 'required|integer|min:0',
            'items.*.subtotal'   => 'required|integer|min:0',
        ]);

        /*
        |--------------------------------------------------------------------------
        | HITUNG TOTAL
        |--------------------------------------------------------------------------
        */

        // subtotal seluruh barang
        $totalAmount = collect($validated['items'])->sum(function ($item) {
            return (int) $item['subtotal'];
        });

        /*
        |--------------------------------------------------------------------------
        | BIAYA LAYANAN
        |--------------------------------------------------------------------------
        */

        // sama seperti tiket: tier berdasarkan total nominal
        $serviceTax = $this->calcServiceTax($totalAmount);

        /*
        |--------------------------------------------------------------------------
        | GRAND TOTAL
        |--------------------------------------------------------------------------
        */

        $grandTotal = $totalAmount + $serviceTax;

        /*
        |--------------------------------------------------------------------------
        | SIMPAN TRANSAKSI
        |--------------------------------------------------------------------------
        */

        $transaction = TransactionMerch::create([
            'event_id' => $validated['event_id'],

            'email' => $validated['email'],

            // subtotal barang
            'total_amount' => $totalAmount,

            // biaya layanan
            'service_tax' => $serviceTax,

            // total akhir dibayar
            'grand_total' => $grandTotal,

            'payment_status' => 'unpaid',

            'kode_unik' => strtoupper(Str::random(10)),
        ]);

        /*
        |--------------------------------------------------------------------------
        | SIMPAN DETAIL ITEM
        |--------------------------------------------------------------------------
        */

        foreach ($validated['items'] as $item) {

            TransactionMerchDetail::create([
                'transaction_merch_id' => $transaction->id,

                'buyer_name'  => $validated['buyer_name'],
                'buyer_phone' => $validated['buyer_phone'],

                'product_id' => $item['product_id'],
                'varian_id'  => $item['varian_id'],
                'ukuran_id'  => $item['ukuran_id'] ?? null,

                'quantity' => $item['quantity'],
                'price'    => $item['price'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | XENDIT
        |--------------------------------------------------------------------------
        */

        Xendit::setApiKey(env('XENDIT_API_KEY'));

        $params = [
            'external_id' => 'merch-' . $transaction->id,

            'payer_email' => $validated['email'],

            'description' => 'Pembelian Merchandise',

            // WAJIB pakai grand total
            'amount' => $grandTotal,

            'success_redirect_url' => route('merch.success', $transaction->id),

            'failure_redirect_url' => route('merch.failed', $transaction->id),

            'currency' => 'IDR',

            'invoice_duration' => 15 * 60,

            'payment_methods' => ['QRIS'],
        ];

        $invoice = \Xendit\Invoice::create($params);

        /*
        |--------------------------------------------------------------------------
        | UPDATE XENDIT DATA
        |--------------------------------------------------------------------------
        */

        $transaction->update([
            'xendit_invoice_id'  => $invoice['id'],
            'xendit_invoice_url' => $invoice['invoice_url'],
        ]);

        return redirect($invoice['invoice_url']);
    }

    public function success($id)
    {
        $transaction = TransactionMerch::findOrFail($id);

        if ($transaction->payment_status !== 'paid') {

            return view('merch.failed', compact('transaction'))
                ->with(
                    'message',
                    'Menunggu verifikasi pembayaran dari Xendit...'
                );
        }

        return view('merch.success', compact('transaction'));
    }

    public function failed($id)
    {
        $transaction = TransactionMerch::findOrFail($id);

        // ⚠️ Sebelumnya di sini di-set payment_status = 'failed', padahal kolom ini berupa
        // enum('unpaid','paid','refunded') — nilai 'failed' tidak valid (akan ditolak di mode
        // strict, atau tersimpan sebagai string kosong). Status TIDAK diubah di halaman redirect
        // ini; sumber kebenaran status adalah webhook Xendit. Biarkan tetap 'unpaid' sampai
        // benar-benar dibayar atau invoice kedaluwarsa.
        return view('merch.failed', compact('transaction'));
    }

    public function showQr($kode_unik)
    {
        $transaction = TransactionMerch::where(
            'kode_unik',
            $kode_unik
        )
        ->with('details.product')
        ->firstOrFail();

        Log::info('Menampilkan QR untuk transaksi merch', [
            'transaction_kode_unik' => $transaction->kode_unik,
            'email'                 => $transaction->email,
            'status'                => $transaction->payment_status,
        ]);

        return view('admin.merch-qr', compact('transaction'));
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Xendit\Xendit;

class MerchController extends Controller
{
    /// =========================
    /// LIST MERCH
    /// =========================
    public function index($eventId)
    {
        $products = DB::table('products')
            ->where('event_id', $eventId)
            ->where('type', 'merch')
            ->get();

        $result = [];

        foreach ($products as $product) {

            $varians = DB::table('products_varian')
                ->where('product_id', $product->id)
                ->get();

            $varianData = [];
            $minPrice = null;
            $firstImage = null;

            foreach ($varians as $varian) {

                $ukurans = DB::table('products_ukuran')
                    ->where('varian_id', $varian->id)
                    ->get();

                $images = DB::table('images')
                    ->where('product_varian_id', $varian->id)
                    ->pluck('url')
                    ->map(fn($img) => $this->formatImage($img))
                    ->filter()
                    ->values();

                if ($images->isEmpty()) {
                    $images = collect([
                        asset('images/no-image.png')
                    ]);
                }

                if (
                    $firstImage === null &&
                    $images->isNotEmpty()
                ) {
                    $firstImage = $images->first();
                }

                foreach ($ukurans as $u) {

                    if (
                        $minPrice === null ||
                        $u->harga < $minPrice
                    ) {
                        $minPrice = $u->harga;
                    }
                }

                $varianData[] = [

                    'id' => $varian->id,

                    'name' => $varian->varian,

                    'images' => $images,

                    'sizes' => $ukurans
                        ->map(fn($u) => [

                            'id' => $u->id,

                            'size' =>
                                $u->ukuran ?? '-',

                            'price' =>
                                (int) $u->harga,

                            'stock' =>
                                $u->stok ?? 0,
                        ])
                        ->values(),
                ];
            }

            if (!$firstImage) {
                $firstImage =
                    asset('images/no-image.png');
            }

            $result[] = [

                'id' => $product->id,

                'name' => $product->name,

                'description' =>
                    $product->description ?? '-',

                'price_start' =>
                    (int) ($minPrice ?? 0),

                'image' => $firstImage,

                'varians' => $varianData
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /// =========================
    /// CHECKOUT MERCH
    /// =========================
    public function checkout(Request $request)
    {
        DB::beginTransaction();

        try {

            $items = $request->items;
            $email = $request->email;
            $name  = $request->name;

            if (!$items || count($items) == 0) {

                return response()->json([
                    'success' => false,
                    'message' => 'Cart kosong'
                ]);
            }

            $apiKey = env('XENDIT_API_KEY');

            if (!$apiKey) {
                throw new \Exception(
                    'XENDIT_API_KEY belum diset'
                );
            }

            Xendit::setApiKey($apiKey);

            $kode =
                'MERCH-' .
                strtoupper(Str::random(6));

            /// =========================
            /// HITUNG TOTAL
            /// =========================
            $totalAmount = 0;

            foreach ($items as $item) {

                $subtotal =
                    $item['price'] * $item['qty'];

                $totalAmount += $subtotal;
            }

            /// SERVICE TAX 10%
            $serviceTax = round(
                $totalAmount * 0.10
            );

            /// GRAND TOTAL
            $grandTotal =
                $totalAmount + $serviceTax;

            /// =========================
            /// INSERT TRANSACTION
            /// =========================
            $trxId = DB::table('transaction_merch')
                ->insertGetId([

                    'kode_unik' => $kode,

                    /// FIELD BARU
                    'total_amount' =>
                        $totalAmount,

                    'service_tax' =>
                        $serviceTax,

                    'grand_total' =>
                        $grandTotal,

                    'email' => $email,

                    'payment_status' =>
                        $grandTotal == 0
                            ? 'paid'
                            : 'unpaid',

                    'checkout_time' => now(),

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            /// =========================
            /// INSERT DETAIL
            /// =========================
            foreach ($items as $item) {

                DB::table(
                    'transaction_merch_details'
                )->insert([

                    'transaction_merch_id' =>
                        $trxId,

                    'buyer_name' =>
                        $name,

                    'product_id' =>
                        $item['product_id'],

                    'varian_id' =>
                        $item['varian_id'],

                    'ukuran_id' =>
                        $item['ukuran_id'],

                    'quantity' =>
                        $item['qty'],

                    'price' =>
                        $item['price'],

                    'subtotal' =>
                        $item['price'] *
                        $item['qty'],

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /// =========================
            /// FREE MERCH
            /// =========================
            if ($grandTotal == 0) {

                DB::table('transaction_merch')
                    ->where('id', $trxId)
                    ->update([

                        'payment_status' =>
                            'paid',

                        'paid_time' => now(),
                    ]);

                DB::commit();

                return response()->json([

                    'success' => true,

                    'is_free' => true,

                    'transaction_id' =>
                        $trxId,

                    'data' => [

                        'kode_unik' =>
                            $kode,

                        'email' =>
                            $email,

                        'payment_status' =>
                            'paid',

                        'payment_method' => 
                            'Free', // 🔥 Ditambahkan info pembayaran gratis

                        'total_amount' =>
                            $totalAmount,

                        'service_tax' =>
                            $serviceTax,

                        'grand_total' =>
                            $grandTotal,
                    ]
                ]);
            }

            /// =========================
            /// XENDIT
            /// =========================
            $params = [

                'external_id' =>
                    'merch-' . $trxId,

                'payer_email' =>
                    $email,

                'description' =>
                    "Pembelian Merchandise ($kode)",

                /// PAKAI GRAND TOTAL
                'amount' =>
                    $grandTotal,

                'success_redirect_url' =>
                    'myapp://merch-success?trx_id=' .
                    $trxId,

                'failure_redirect_url' =>
                    'myapp://merch-failed',
            ];

            $invoice =
                \Xendit\Invoice::create($params);

            /// =========================
            /// SIMPAN INVOICE
            /// =========================
            DB::table('transaction_merch')

                ->where('id', $trxId)

                ->update([

                    'xendit_invoice_id' =>
                        $invoice['id'],

                    'xendit_invoice_url' =>
                        $invoice['invoice_url'],
                ]);

            DB::commit();

            return response()->json([

                'success' => true,

                'payment_url' =>
                    $invoice['invoice_url'],

                'transaction_id' =>
                    $trxId,

                'data' => [

                    'kode_unik' =>
                        $kode,

                    'email' =>
                        $email,

                    'payment_status' =>
                        'unpaid', // 🔥 Diperbaiki dari $unpaid (sebelumnya undefined variable) menjadi string

                    'payment_method' => 
                        'Xendit Gateway', // 🔥 Ditambahkan info gerbang pembayaran awal

                    'total_amount' =>
                        $totalAmount,

                    'service_tax' =>
                        $serviceTax,

                    'grand_total' =>
                        $grandTotal,
                ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' =>
                    $e->getMessage()
            ]);
        }
    }

    /// =========================
    /// DETAIL MERCH
    /// =========================
    public function detail($id)
    {
        $trx = DB::table('transaction_merch')
            ->where('id', $id)
            ->first();

        if (!$trx) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Transaksi tidak ditemukan'
            ], 404);
        }

        $detail = DB::table(
            'transaction_merch_details'
        )
            ->where(
                'transaction_merch_id',
                $trx->id
            )
            ->first();

        return response()->json([

            'success' => true,

            'data' => [

                'kode_unik' =>
                    $trx->kode_unik,

                'email' =>
                    $trx->email,

                'name' =>
                    $detail->buyer_name ?? '-',

                'payment_status' =>
                    $trx->payment_status,

                'payment_method' => 
                    $trx->payment_method ?? ($trx->grand_total == 0 ? 'Free' : 'Xendit Gateway'), // 🔥 Mengambil dari DB jika ada, jika tidak otomatis fallback sesuai harga

                /// FIELD BARU
                'total_amount' =>
                    $trx->total_amount,

                'service_tax' =>
                    $trx->service_tax,

                'grand_total' =>
                    $trx->grand_total,
            ]
        ]);
    }

    /// =========================
    /// MY MERCH
    /// =========================
    public function myMerch(Request $request)
    {
        $email =
            trim($request->query('email'));

        $transactions = DB::table(
            'transaction_merch'
        )

            ->whereRaw(
                'LOWER(email) = ?',
                [strtolower($email)]
            )

            ->whereIn(
                'payment_status',
                ['paid', 'unpaid']
            )

            ->orderBy(
                'created_at',
                'desc'
            )

            ->get();

        $result = [];

        foreach ($transactions as $trx) {

            $items = DB::table(
                'transaction_merch_details as tmd'
            )

                ->leftJoin(
                    'products as p',
                    'tmd.product_id',
                    '=',
                    'p.id'
                )

                ->leftJoin(
                    'products_varian as pv',
                    'tmd.varian_id',
                    '=',
                    'pv.id'
                )

                ->leftJoin(
                    'products_ukuran as pu',
                    'tmd.ukuran_id',
                    '=',
                    'pu.id'
                )

                ->where(
                    'tmd.transaction_merch_id',
                    $trx->id
                )

                ->select(

                    'tmd.id',

                    'p.name as product_name',

                    'pv.varian',

                    'pu.ukuran',

                    'tmd.quantity',

                    'tmd.price',

                    'tmd.subtotal',

                    'tmd.buyer_name',

                    'tmd.buyer_phone'
                )

                ->get();

            $totalItem =
                $items->sum('quantity');

            $result[] = [

                'id' => $trx->id,

                'kode_unik' =>
                    $trx->kode_unik,

                'qr_code' =>
                    $trx->qr_code ??
                    $trx->kode_unik,

                'payment_status' =>
                    $trx->payment_status,

                'payment_method' => 
                    $trx->payment_method ?? ($trx->grand_total == 0 ? 'Free' : 'Xendit Gateway'), // 🔥 Mengambil dari DB jika ada, jika tidak otomatis fallback sesuai harga

                'email' =>
                    $trx->email,

                /// FIELD BARU
                'total_amount' =>
                    $trx->total_amount,

                'service_tax' =>
                    $trx->service_tax,

                'grand_total' =>
                    $trx->grand_total,

                'checkout_time' =>
                    $trx->checkout_time,

                'paid_time' =>
                    $trx->paid_time,

                'xendit_invoice_id' =>
                    $trx->xendit_invoice_id,

                'total_item' =>
                    $totalItem,

                'items' => $items,
            ];
        }

        return response()->json([

            'success' => true,

            'email' => $email,

            'total_transaction' =>
                count($transactions),

            'data' => $result,
        ]);
    }

    /// =========================
    /// HELPER IMAGE
    /// =========================
    private function formatImage($path)
    {
        if (!$path) {
            return asset(
                'images/no-image.png'
            );
        }

        if (
            str_starts_with($path, 'http')
        ) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (
            !str_contains($path, 'images/')
        ) {
            $path = 'images/' . $path;
        }

        return asset($path);
    }
}
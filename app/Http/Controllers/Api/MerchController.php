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
            $varians = DB::table('products_varian')->where('product_id', $product->id)->get();
            $varianData = [];
            $minPrice = null;
            $firstImage = null;

            foreach ($varians as $varian) {
                $ukurans = DB::table('products_ukuran')->where('varian_id', $varian->id)->get();
                $images = DB::table('images')
                    ->where('product_varian_id', $varian->id)
                    ->pluck('url')
                    ->map(fn($img) => $this->formatImage($img))
                    ->filter()
                    ->values();

                if ($images->isEmpty()) {
                    $images = collect([asset('images/no-image.png')]);
                }

                if ($firstImage === null && $images->isNotEmpty()) {
                    $firstImage = $images->first();
                }

                foreach ($ukurans as $u) {
                    if ($minPrice === null || $u->harga < $minPrice) {
                        $minPrice = $u->harga;
                    }
                }

                $varianData[] = [
                    'id' => $varian->id,
                    'name' => $varian->varian,
                    'images' => $images,
                    'sizes' => $ukurans->map(fn($u) => [
                        'id' => $u->id,
                        'size' => $u->ukuran ?? '-',
                        'price' => (int) $u->harga,
                        'stock' => $u->stok ?? 0,
                    ])->values(),
                ];
            }

            if (!$firstImage) {
                $firstImage = asset('images/no-image.png');
            }

            $result[] = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description ?? '-',
                'price_start' => (int) ($minPrice ?? 0),
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
            $phone = $request->phone; // 🔥 Tangkap input phone dari Flutter

            if (!$items || count($items) == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart kosong'
                ]);
            }

            $apiKey = env('XENDIT_API_KEY');
            if (!$apiKey) {
                throw new \Exception('XENDIT_API_KEY belum diset');
            }

            Xendit::setApiKey($apiKey);
            $kode = 'MERCH-' . strtoupper(Str::random(6));

            /// =========================
            /// HITUNG TOTAL
            /// =========================
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += ($item['price'] * $item['qty']);
            }

            $serviceTax = round($totalAmount * 0.10); /// SERVICE TAX 10%
            $grandTotal = $totalAmount + $serviceTax; /// GRAND TOTAL

            /// =========================
            /// AMBIL EVENT_ID DARI PRODUK (asumsi 1 transaksi = 1 event yang sama)
            /// Disimpan langsung di transaction_merch supaya lookup status/keputusan
            /// pembatalan event tidak bergantung pada join items->product yang rapuh.
            /// =========================
            $firstProductId = $items[0]['product_id'] ?? null;
            $productEventId = $firstProductId
                ? DB::table('products')->where('id', $firstProductId)->value('event_id')
                : null;

            /// =========================
            /// INSERT TRANSACTION (Sesuai Skema Asli)
            /// =========================
            $trxId = DB::table('transaction_merch')->insertGetId([
                'kode_unik' => $kode,
                'total_amount' => $totalAmount,
                'service_tax' => $serviceTax,
                'grand_total' => $grandTotal,
                'email' => $email,
                'payment_status' => $grandTotal == 0 ? 'paid' : 'unpaid',
                'payment_method' => $grandTotal == 0 ? 'Free' : 'Xendit Gateway',
                'checkout_time' => now(),
                'event_id' => $productEventId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /// =========================
            /// INSERT DETAIL (Gunakan buyer_phone)
            /// =========================
            foreach ($items as $item) {
                DB::table('transaction_merch_details')->insert([
                    'transaction_merch_id' => $trxId,
                    'buyer_name' => $name,
                    'buyer_phone' => $phone, // 🔥 Simpan phone ke kolom database asli
                    'product_id' => $item['product_id'],
                    'varian_id' => $item['varian_id'],
                    'ukuran_id' => $item['ukuran_id'],
                    'quantity' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['qty'],
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
                        'payment_status' => 'paid',
                        'paid_time' => now(),
                        'payment_method' => 'Free',
                    ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'is_free' => true,
                    'transaction_id' => $trxId,
                    'data' => [
                        'kode_unik' => $kode,
                        'email' => $email,
                        'phone' => $phone,
                        'payment_status' => 'paid',
                        'payment_method' => 'Free',
                        'total_amount' => $totalAmount,
                        'service_tax' => $serviceTax,
                        'grand_total' => $grandTotal,
                    ]
                ]);
            }

            /// =========================
            /// XENDIT INVOICE CREATION
            /// =========================
            $params = [
                'external_id' => 'merch-' . $trxId,
                'payer_email' => $email,
                'description' => "Pembelian Merchandise ($kode)",
                'amount' => $grandTotal,
                'success_redirect_url' => 'myapp://merch-success?trx_id=' . $trxId,
                'failure_redirect_url' => 'myapp://merch-failed',
            ];

            // Masukkan data pelanggan ke tagihan Xendit jika nomor HP tersedia
            if ($phone) {
                $params['customer'] = [
                    'given_names' => $name,
                    'mobile_number' => $phone
                ];
            }

            $invoice = \Xendit\Invoice::create($params);

            /// =========================
            /// SIMPAN INVOICE
            /// =========================
            DB::table('transaction_merch')
                ->where('id', $trxId)
                ->update([
                    'xendit_invoice_id' => $invoice['id'],
                    'xendit_invoice_url' => $invoice['invoice_url'],
                    'payment_method' => 'Xendit Gateway',
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'payment_url' => $invoice['invoice_url'],
                'transaction_id' => $trxId,
                'data' => [
                    'kode_unik' => $kode,
                    'email' => $email,
                    'phone' => $phone,
                    'payment_status' => 'unpaid',
                    'payment_method' => 'Xendit Gateway',
                    'total_amount' => $totalAmount,
                    'service_tax' => $serviceTax,
                    'grand_total' => $grandTotal,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /// =========================
    /// DETAIL MERCH
    /// =========================
    public function detail($id)
    {
        $trx = DB::table('transaction_merch')->where('id', $id)->first();
        if (!$trx) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        $detail = DB::table('transaction_merch_details')
            ->where('transaction_merch_id', $trx->id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'kode_unik' => $trx->kode_unik,
                'email' => $trx->email,
                'name' => $detail->buyer_name ?? '-',
                'phone' => $detail->buyer_phone ?? '-', // 🔥 Ambil dari tabel details
                'payment_status' => $trx->payment_status,
                'payment_method' => $trx->payment_method ?? ($trx->grand_total == 0 ? 'Free' : 'Xendit Gateway'),
                'total_amount' => $trx->total_amount,
                'service_tax' => $trx->service_tax,
                'grand_total' => $trx->grand_total,
            ]
        ]);
    }

    /// =========================
    /// MY MERCH
    /// =========================
    public function myMerch(Request $request)
    {
        $email = trim($request->query('email'));

        $transactions = DB::table('transaction_merch')
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->whereIn('payment_status', ['paid', 'unpaid'])
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];

        foreach ($transactions as $trx) {
            $items = DB::table('transaction_merch_details as tmd')
                ->leftJoin('products as p', 'tmd.product_id', '=', 'p.id')
                ->leftJoin('products_varian as pv', 'tmd.varian_id', '=', 'pv.id')
                ->leftJoin('products_ukuran as pu', 'tmd.ukuran_id', '=', 'pu.id')
                ->where('tmd.transaction_merch_id', $trx->id)
                ->select(
                    'tmd.id',
                    'p.name as product_name',
                    'p.event_id as event_id',
                    'pv.varian',
                    'pu.ukuran',
                    'tmd.quantity',
                    'tmd.price',
                    'tmd.subtotal',
                    'tmd.buyer_name',
                    'tmd.buyer_phone'
                )
                ->get();

            $totalItem = $items->sum('quantity');

            // AMBIL EVENT TERKAIT.
            // Prioritaskan kolom transaction_merch.event_id (diisi saat checkout, stabil
            // walau produk/varian/ukuran-nya belakangan diubah atau dihapus).
            // Fallback ke event_id dari item pertama untuk transaksi lama sebelum
            // kolom ini ada (asumsi 1 transaksi merch hanya berisi produk dari 1 event).
            $eventId = $trx->event_id ?? ($items->first()?->event_id ?? null);
            $event = $eventId ? DB::table('events')->where('id', $eventId)->first() : null;

            // Null-safe: kalau event tidak ditemukan (mis. produk sudah dihapus dan
            // transaksi lama tidak punya event_id sendiri), jangan sampai crash -
            // cukup anggap event dalam kondisi normal (tidak dibatalkan).
            $eventTitle = $event?->title ?? 'Event';
            $eventStatus = $event?->status ?? 'approved';
            // 'refund' | 'ship_independently' | null (EO belum memutuskan)
            $merchCancelDecision = $event?->merch_cancel_decision ?? null;

            // Merch hanya perlu keputusan/tindakan kalau EVENT-nya dibatalkan.
            // Reschedule TIDAK memengaruhi merch (barang tetap bisa diambil/dikirim seperti biasa).
            $isCancelled = in_array($eventStatus, ['cancelled', 'pending_cancel']);

            // AMBIL DATA REFUND TERBARU UNTUK TRANSAKSI MERCH INI (JIKA ADA)
            $refund = DB::table('refunds')
                ->where('transaction_merch_id', $trx->id)
                ->orderBy('id', 'desc')
                ->first();
            $refundStatus = $refund->status ?? null; // waiting | pending | refunded | rejected | null

            // JIKA REFUND SUDAH SELESAI (REFUNDED), PESANAN TIDAK PERLU DITAMPILKAN LAGI
            if ($refundStatus === 'refunded') {
                continue;
            }

            // Tombol "Ajukan Refund" HANYA aktif jika event dibatalkan DAN EO memutuskan 'refund'.
            $canRefund = $isCancelled && $merchCancelDecision === 'refund';
            // EO memutuskan merch tetap dikirim sendiri walau event batal -> tidak ada opsi refund.
            $isShipIndependently = $isCancelled && $merchCancelDecision === 'ship_independently';
            // Event batal tapi EO belum menentukan keputusan merch-nya.
            $isWaitingDecision = $isCancelled && $merchCancelDecision === null;

            // "PERLU TINDAKAN" hanya jika refund memang bisa diajukan, dan belum ada pengajuan
            // atau pengajuan sebelumnya ditolak.
            $needsAction = $canRefund && ($refundStatus === null || $refundStatus === 'rejected');

            $qrFile = $trx->qr_code ?? ($trx->kode_unik . '.png');
            $qrFileName = basename($qrFile); 
            $qrCodeUrl = asset('images/qrcodes_merch/' . $qrFileName);

            $result[] = [
                'id' => $trx->id,
                'kode_unik' => $trx->kode_unik,
                'qr_code' => $qrCodeUrl,
                'payment_status' => $trx->payment_status,
                'payment_method' => $trx->payment_method ?? ($trx->grand_total == 0 ? 'Free' : 'Xendit Gateway'),
                'email' => $trx->email,
                'phone' => $items->first()->buyer_phone ?? '-', // 🔥 Tampilkan phone detail pertama di list
                'total_amount' => $trx->total_amount,
                'service_tax' => $trx->service_tax,
                'grand_total' => $trx->grand_total,
                'checkout_time' => $trx->checkout_time,
                'paid_time' => $trx->paid_time,
                'xendit_invoice_id' => $trx->xendit_invoice_id,
                'total_item' => $totalItem,
                'items' => $items,

                // 📌 Data status event & keputusan pembatalan merch (dipakai Flutter untuk banner tindakan)
                'event_id' => $eventId,
                'event_title' => $eventTitle,
                'event_status' => $eventStatus,
                'merch_cancel_decision' => $merchCancelDecision,
                'refund_status' => $refundStatus,
                'can_refund' => $canRefund,
                'is_ship_independently' => $isShipIndependently,
                'is_waiting_decision' => $isWaitingDecision,
                'needs_action' => $needsAction,
            ];
        }

        return response()->json([
            'success' => true,
            'email' => $email,
            'total_transaction' => count($result),
            'data' => $result,
        ]);
    }

    private function formatImage($path)
    {
        if (!$path) return asset('images/no-image.png');
        if (str_starts_with($path, 'http')) return $path;
        
        $path = ltrim($path, '/');
        if (!str_contains($path, 'images/')) {
            $path = 'images/' . $path;
        }
        return asset($path);
    }
}
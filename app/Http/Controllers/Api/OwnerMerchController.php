<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerMerchController extends Controller
{
    /**
     * Get Dashboard & Analytics Data for Merchandise Sales
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');

            // 1. Hitung ringkasan statistik global dari transaksi PAID
            $stats = DB::table('transaction_merch')
                ->where('payment_status', 'paid')
                ->select([
                    DB::raw('IFNULL(SUM(grand_total), 0) as total_revenue'),
                    DB::raw('COUNT(id) as total_transactions')
                ])
                ->first();

            // 2. Hitung jumlah total kuantitas barang terbayar di seluruh platform
            $totalItemsSold = DB::table('transaction_merch_details')
                ->join('transaction_merch', 'transaction_merch_details.transaction_merch_id', '=', 'transaction_merch.id')
                ->where('transaction_merch.payment_status', 'paid')
                ->sum('transaction_merch_details.quantity');

            // 3. STATISTIK TERBARU: Hitung penjualan produk & sisa stok tersedia secara agregat
            $productSales = DB::table('products')
                ->leftJoin('events', 'products.event_id', '=', 'events.id')
                ->select([
                    'products.id',
                    'products.name as product_name',
                    DB::raw("IFNULL(events.title, 'Tanpa Event') as event_title"),
                    // Hitung total stok dari semua varian & ukuran produk ini
                    DB::raw('(SELECT IFNULL(SUM(stok), 0) FROM products_ukuran WHERE varian_id IN (SELECT id FROM products_varian WHERE product_id = products.id)) as total_stock'),
                    // Hitung kuantitas terjual hanya dari transaksi paid
                    DB::raw('(SELECT IFNULL(SUM(tmd.quantity), 0) FROM transaction_merch_details tmd 
                              JOIN transaction_merch tm ON tmd.transaction_merch_id = tm.id 
                              WHERE tmd.product_id = products.id AND tm.payment_status = "paid") as total_sold')
                ])
                ->where('products.type', 'merch')
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'event_title' => $product->event_title,
                        'total_stock' => (int) $product->total_stock,
                        'total_sold' => (int) $product->total_sold,
                    ];
                });

            // 4. Ambil daftar histori transaksi utama merch
            $queryTx = DB::table('transaction_merch');

            if (!empty($search)) {
                $queryTx->where(function($q) use ($search) {
                    $q->where('kode_unik', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            $transactions = $queryTx->orderBy('checkout_time', 'desc')->get();

            // Transform data histori transaksi beserta detail item di dalamnya
            $transformedItems = collect($transactions)->map(function ($tx) {
                $details = DB::table('transaction_merch_details')
                    ->join('products', 'transaction_merch_details.product_id', '=', 'products.id')
                    ->leftJoin('products_varian', 'transaction_merch_details.varian_id', '=', 'products_varian.id')
                    ->leftJoin('products_ukuran', 'transaction_merch_details.ukuran_id', '=', 'products_ukuran.id')
                    ->leftJoin('events', 'products.event_id', '=', 'events.id')
                    ->where('transaction_merch_details.transaction_merch_id', $tx->id)
                    ->select([
                        'products.name as product_name',
                        'products_varian.varian as variant_name',
                        'products_ukuran.ukuran as size_name',
                        'transaction_merch_details.quantity',
                        DB::raw('CAST(transaction_merch_details.price AS UNSIGNED) as price'),
                        DB::raw('CAST(transaction_merch_details.subtotal AS UNSIGNED) as subtotal'),
                        DB::raw("IFNULL(events.title, 'Tanpa Event') as event_title")
                    ])
                    ->get();

                return [
                    'id' => $tx->id,
                    'kode_unik' => $tx->kode_unik,
                    'email' => $tx->email,
                    'payment_status' => $tx->payment_status,
                    'payment_method' => $tx->payment_method,
                    'total_amount' => (int) $tx->total_amount,
                    'service_tax' => (int) $tx->service_tax,
                    'grand_total' => (int) $tx->grand_total,
                    'checkout_time' => $tx->checkout_time,
                    'paid_time' => $tx->paid_time,
                    'qr_code' => $tx->qr_code, 
                    'items' => $details
                ];
            });

            return response()->json([
                'status' => 'success',
                'summary' => [
                    'total_revenue' => (int) ($stats->total_revenue ?? 0),
                    'total_transactions' => (int) ($stats->total_transactions ?? 0),
                    'total_items_sold' => (int) $totalItemsSold,
                ],
                'product_performance' => $productSales, // Array data stok & kelayakan produk baru
                'transactions' => [
                    'data' => $transformedItems
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal server.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Mengambil rincian breakdown penjualan per produk merchandise (Halaman Detail).
     * GET /api/owner/merch-sales-summary?event_title=...
     */
    public function getMerchSalesSummary(Request $request)
    {
        $user = Auth::user();

        // Proteksi Hak Akses khusus Owner
        if (!$user || $user->role !== 'owner') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Khusus Owner.'
            ], 403);
        }

        try {
            $eventTitleFilter = $request->query('event_title');

            // Inisialisasi query breakdown per produk, varian, dan ukuran sesuai skema database
            $query = DB::table('products_ukuran as pu')
                ->join('products_varian as pv', 'pu.varian_id', '=', 'pv.id')
                ->join('products', 'pv.product_id', '=', 'products.id')
                ->leftJoin('events', 'products.event_id', '=', 'events.id')
                ->select([
                    DB::raw("IFNULL(events.title, 'Tanpa Event') as event_title"),
                    'products.name as product_name',
                    'pv.varian as variant_name',
                    'pu.ukuran as size_name',
                    'pu.harga as product_price',
                    'pu.stok as current_stock',
                    // Hitung total kuantitas terjual dari transaksi yang sudah lunas (paid)
                    DB::raw('(
                        SELECT IFNULL(SUM(tmd.quantity), 0) 
                        FROM transaction_merch_details tmd
                        JOIN transaction_merch tm ON tmd.transaction_merch_id = tm.id
                        WHERE tmd.ukuran_id = pu.id AND tm.payment_status = \'paid\'
                    ) as total_sold'),
                    // Hitung total omset/revenue berdasarkan subtotal detail transaksi
                    DB::raw('(
                        SELECT IFNULL(SUM(tmd.subtotal), 0) 
                        FROM transaction_merch_details tmd
                        JOIN transaction_merch tm ON tmd.transaction_merch_id = tm.id
                        WHERE tmd.ukuran_id = pu.id AND tm.payment_status = \'paid\'
                    ) as total_revenue')
                ])
                ->where('products.type', 'merch');

            // Terapkan filter berdasarkan judul event jika dipilih spesifik
            if ($eventTitleFilter && strtolower($eventTitleFilter) !== 'semua event') {
                if (strtolower($eventTitleFilter) === 'tanpa event') {
                    $query->whereNull('products.event_id');
                } else {
                    $query->where('events.title', $eventTitleFilter);
                }
            }

            $salesData = $query->orderBy('events.title', 'asc')
                ->orderBy('products.name', 'asc')
                ->orderBy('pv.varian', 'asc')
                ->get();

            // Hitung akumulasi grand total untuk statistik widget screen atas detail merch
            $grandTotalSold = $salesData->sum('total_sold');
            $grandTotalRevenue = $salesData->sum('total_revenue');

            return response()->json([
                'status' => 'success',
                'message' => 'Data rincian jenis produk merchandise berhasil dimuat.',
                'grand_total_sold' => (int) $grandTotalSold,
                'grand_total_revenue' => (int) $grandTotalRevenue,
                'data' => $salesData->map(function($item) {
                    return [
                        'event_title'   => $item->event_title,
                        'product_name'  => $item->product_name,
                        'variant_name'  => $item->variant_name,
                        'size_name'     => $item->size_name,
                        'price'         => (int) $item->product_price,
                        'current_stock' => (int) $item->current_stock,
                        'total_sold'    => (int) $item->total_sold,
                        'total_revenue' => (int) $item->total_revenue,
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat rincian penjualan produk merchandise: ' . $e->getMessage()
            ], 500);
        }
    }
}
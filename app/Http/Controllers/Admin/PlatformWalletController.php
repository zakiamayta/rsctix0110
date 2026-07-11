<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformWalletController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'owner'])) {
                abort(403, 'Aksi ini hanya diizinkan untuk Admin Utama dan Owner.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        // 1. Ambil data saldo utama platform dari tabel platform_wallets
        $wallet = DB::table('platform_wallets')->where('id', 1)->first();

        // 2. QUERY REAL-TIME MUTASI (Gabungan Pendapatan Pajak Tiket & Biaya Refund)
        // Menggunakan SQL UNION ALL untuk membaca log transaksi finansial secara live
        $ticketIncomeQuery = DB::table('transactions')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->where('transactions.payment_status', '=', 'paid')
            ->select(
                'transactions.paid_time as trx_date',
                DB::raw("'income' as type"),
                'transactions.service_tax as amount',
                DB::raw("CONCAT('Pajak Layanan (Service Tax) Tiket - ', events.title) as description"),
                'transactions.kode_unik as reference_code'
            );

        $refundExpenseQuery = DB::table('refunds')
            ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->where('refunds.status', '=', 'refunded')
            ->select(
                'refunds.processed_at as trx_date',
                DB::raw("'expense' as type"),
                'refunds.refunds_tax as amount',
                DB::raw("CONCAT('Biaya Administrasi Terpotong (Refund Fee) - ', events.title) as description"),
                'transactions.kode_unik as reference_code'
            )
            ->unionAll($ticketIncomeQuery);

        // Bungkus dalam subquery untuk melakukan sorting global real-time dan pagination
        $mutations = DB::table(DB::raw("({$refundExpenseQuery->toSql()}) as combined_mutations"))
            ->mergeBindings($refundExpenseQuery)
            ->orderByDesc('trx_date')
            ->paginate(15);

        // 3. Top 5 Kasus Refund Terbanyak (Berdasarkan skema database Anda)
        $refundStats = DB::table('refunds')
            ->join('refund_batches', 'refunds.refund_batch_id', '=', 'refund_batches.id')
            ->join('events', 'refund_batches.event_id', '=', 'events.id')
            ->select(
                'events.title as event_name',
                DB::raw('COUNT(refunds.id) as total_kasus_refund'),
                DB::raw('SUM(refunds.refunds_tax) as total_biaya_hangus')
            )
            ->groupBy('events.id', 'events.title')
            ->orderByDesc('total_biaya_hangus')
            ->take(5)
            ->get();

        return view('admin.wallet.index', compact('wallet', 'mutations', 'refundStats'));
    }
}
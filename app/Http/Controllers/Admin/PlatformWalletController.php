<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformWalletController extends Controller
{
    public function __construct()
    {
        // 🔄 REVISI MIDDLEWARE: Izinkan masuk jika user adalah Admin ATAU Owner
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'owner'])) {
                abort(403, 'Aksi ini hanya diizinkan untuk Admin Utama dan Owner.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        // Ambil data record tunggal dompet platform
        $wallet = DB::table('platform_wallets')->where('id', 1)->first();

        // Ambil 5 besar event yang memicu pengeluaran biaya refund terbanyak
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

        return view('admin.wallet.index', compact('wallet', 'refundStats'));
    }
}
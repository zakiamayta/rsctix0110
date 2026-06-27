<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\RefundBatch;
use App\Models\EODebt;
use App\Models\Eo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerRefundMonitoringController extends Controller
{
    public function __construct()
    {
        // Berikan proteksi middleware agar hanya Owner (role: owner) yang bisa masuk halaman monitoring ini
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || auth()->user()->role !== 'owner') {
                abort(403, 'Aksi ini tidak diizinkan. Halaman ini khusus untuk Owner Utama.');
            }
            return $next($request);
        });
    }

    /**
     * 👁️ Halaman Utama Monitoring Audit Refund & Utang EO
     */
    public function index()
    {
        // 1. Ambil data ringkasan batch refund untuk dipantau (Urut dari yang paling baru)
        $batches = RefundBatch::with(['event', 'eo'])
            ->withCount([
                'refunds as total_pengajuan',
                'refunds as total_sukses_transfer' => function($query) {
                    $query->where('status', 'transferred');
                }
            ])
            ->latest()
            ->get();

        // 2. Ambil rekapitulasi total nominal refund yang sudah keluar di sistem (Audit internal Owner)
        $rekapFinansial = DB::table('refunds')
            ->select(
                DB::raw('SUM(grand_total_refunded) as total_dana_kembali'),
                DB::raw('SUM(platform_service_tax_share) as total_pajak_ditanggung'),
                DB::raw('COUNT(id) as total_seluruh_tiket')
            )
            ->first();

        // 3. Ambil daftar EO yang saat ini memiliki catatan utang aktif di sistem (perlu ditagih)
        $eoDebts = EODebt::with(['eo', 'event'])
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('remaining_debt', 'desc')
            ->get();

        // 4. Ambil ringkasan total utang global seluruh EO yang belum lunas (Summary papan atas)
        $totalUtangGlobal = Eo::sum('total_debt');

        return view('owner.refunds.monitor', compact(
            'batches', 
            'rekapFinansial', 
            'eoDebts', 
            'totalUtangGlobal'
        ));
    }

    /**
     * 👁️ Halaman Detail Monitoring Isi Batch Tertentu
     */
    public function showBatchDetails($id)
    {
        // Owner dapat melihat rincian isi dari batch refund tanpa tombol eksekusi (Aksi dikunci)
        $batch = RefundBatch::with(['event', 'eo'])->findOrFail($id);
        
        $refunds = DB::table('refunds')
            ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->where('refunds.refund_batch_id', $batch->id)
            ->select('refunds.*', 'transactions.kode_unik', 'transactions.email as buyer_email')
            ->latest()
            ->get();

        $totalNominalBatch = $refunds->sum('grand_total_refunded');

        return view('owner.refunds.monitor-show', compact('batch', 'refunds', 'totalNominalBatch'));
    }
}
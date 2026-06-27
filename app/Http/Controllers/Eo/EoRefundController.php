<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use App\Models\RefundBatch;
use App\Models\EODebt;
use App\Models\Eo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EORefundController extends Controller
{
    public function __construct()
    {
        // Proteksi middleware agar hanya user dengan role 'eo' yang bisa mengakses
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || auth()->user()->role !== 'eo') {
                abort(403, 'Aksi ini tidak diizinkan. Halaman ini khusus untuk Pasangan Mitra EO.');
            }
            return $next($request);
        });
    }

    /**
     * 📊 Halaman Utama Dashboard Refund & Utang Sisi EO
     */
    public function index()
    {
        $user = auth()->user();

        // 1. Ambil data profil EO berdasarkan user_id yang sedang login
        $eo = Eo::where('user_id', $user->id)->first();

        // Antisipasi jika user memiliki role EO tapi belum mengisi data profil EO
        if (!$eo) {
            return redirect()->route('dashboard')->with('error', 'Profil Event Organizer Anda belum terdaftar lengkap.');
        }

        // 2. Ambil semua batch refund yang sedang/pernah berjalan KHUSUS untuk event milik EO ini
        $batches = RefundBatch::where('eo_id', $eo->id)
            ->with(['event'])
            ->withCount([
                'refunds as total_pengajuan',
                'refunds as total_selesai_transfer' => function($query) {
                    $query->where('status', 'transferred');
                }
            ])
            ->latest()
            ->get();

        // 3. Ambil riwayat rincian utang per event milik EO ini akibat saldo minus saat refund
        $myDebts = EODebt::where('eo_id', $eo->id)
            ->with(['event'])
            ->latest()
            ->get();

        return view('eo.refunds.index', compact('eo', 'batches', 'myDebts'));
    }

    /**
     * 🔍 Detail Pengajuan Refund Event Milik EO (Transparansi Data)
     */
    public function showBatchDetails($id)
    {
        $user = auth()->user();
        $eo = Eo::where('user_id', $user->id)->first();

        // Ambil data batch dan pastikan batch tersebut memang milik EO yang sedang login (Security Check)
        $batch = RefundBatch::where('id', $id)
            ->where('eo_id', $eo->id)
            ->with(['event'])
            ->firstOrFail();

        // Ambil data pengajuan refund penonton di dalam batch ini (Tanpa menampilkan data rekening sensitif secara penuh demi keamanan)
        $refunds = DB::table('refunds')
            ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->where('refunds.refund_batch_id', $batch->id)
            ->select(
                'refunds.id',
                'refunds.grand_total_refunded',
                'refunds.status',
                'refunds.created_at',
                'transactions.kode_unik',
                // Opsional: Masking nama bank & akun demi privasi pembeli jika diperlukan di view
                'refunds.bank_name',
                'refunds.account_name'
            )
            ->latest()
            ->get();

        $totalRefundBatch = $refunds->sum('grand_total_refunded');

        return view('eo.refunds.show', compact('batch', 'refunds', 'totalRefundBatch'));
    }
}
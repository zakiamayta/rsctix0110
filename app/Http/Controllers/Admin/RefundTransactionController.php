<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\Event;
use Illuminate\Http\Request;

class RefundTransactionController extends Controller
{
    public function __construct()
    {
        // Proteksi middleware agar hanya Admin Utama (role: admin) yang bisa masuk
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || auth()->user()->role !== 'admin') {
                abort(403, 'Aksi ini hanya diizinkan untuk Admin Utama.');
            }
            return $next($request);
        });
    }

    /**
     * 💸 Halaman Riwayat Transaksi Refund (Laporan Pengeluaran)
     *
     * Menampilkan seluruh pengajuan refund (Tiket / Merchandise) lengkap dengan
     * rincian pembeli. Untuk tipe "ticket", setiap baris bisa dibuka untuk melihat
     * masing-masing attendee beserta nama & harga tiket yang mereka beli
     * (relasi: refund -> transaction -> attendees -> ticket).
     */
    public function index(Request $request)
    {
        $type = $request->input('type', 'ticket'); // ticket | merch

        $refunds = Refund::query()
            ->when($type === 'ticket', function ($q) {
                $q->whereNotNull('transaction_id')
                    ->with([
                        'transaction.event',
                        // Detail pembeli per tiket: nama, no hp, dan tiket (nama + harga) yang dibeli
                        'transaction.attendees.ticket',
                    ]);
            })
            ->when($type === 'merch', function ($q) {
                $q->whereNotNull('transaction_merch_id')
                    ->with([
                        'transactionMerch.event',
                        'transactionMerch.details.productVarian.product',
                    ]);
            })
            ->when($request->event_id, function ($q) use ($request, $type) {
                if ($type === 'ticket') {
                    $q->whereHas('transaction', fn($t) => $t->where('event_id', $request->event_id));
                } else {
                    $q->whereHas('transactionMerch', fn($t) => $t->where('event_id', $request->event_id));
                }
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->q, function ($q) use ($request, $type) {
                $search = $request->q;
                if ($type === 'ticket') {
                    $q->whereHas('transaction', fn($t) => $t->where('email', 'like', "%{$search}%"));
                } else {
                    $q->whereHas('transactionMerch', fn($t) => $t->where('email', 'like', "%{$search}%"));
                }
            })
            ->when($request->start_date && $request->end_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->latest()
            ->get();

        // ===== Statistik ringkas (khusus data yang sedang ditampilkan / hasil filter) =====
        $totalRefunded = (float) $refunds->where('status', 'refunded')->sum('grand_total_refunded');
        $totalFee      = (float) $refunds->where('status', 'refunded')->sum('refunds_tax');
        $pendingCount  = $refunds->whereIn('status', ['waiting', 'pending'])->count();
        $refundedCount = $refunds->where('status', 'refunded')->count();

        $events = Event::orderBy('title', 'asc')->get();

        return view('admin.admin-refund-transactions', compact(
            'refunds',
            'events',
            'type',
            'totalRefunded',
            'totalFee',
            'pendingCount',
            'refundedCount'
        ));
    }
}
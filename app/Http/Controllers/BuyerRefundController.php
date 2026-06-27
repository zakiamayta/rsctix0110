<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BuyerRefundController extends Controller
{
    public function __construct()
    {
        // Amankan menggunakan sistem autentikasi bawaan Laravel
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
            }
            return $next($request);
        });
    }

    /**
     * 📄 Tampilkan Halaman Form Refund
     */
    public function create($id)
    {
        $user = auth()->user();

        // Ambil detail transaksi dan pastikan statusnya PAID
        $transaction = DB::table('transactions')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->where('transactions.id', $id)
            ->where('transactions.email', $user->email)
            ->where('transactions.payment_status', 'paid')
            ->select('transactions.*', 'events.title as event_title', 'events.status as event_status', 'events.is_rescheduled')
            ->first();

        if (!$transaction) {
            abort(404, 'Transaksi tidak valid atau belum dibayar.');
        }

        // Syarat refund: status event cancelled ATAU is_rescheduled > 0
        $eligible = ($transaction->event_status === 'cancelled' || $transaction->is_rescheduled > 0);
        if (!$eligible) {
            return redirect()->back()->with('error', 'Event tidak memenuhi syarat pengajuan refund.');
        }

        // Cek double input pengajuan
        $existing = DB::table('refunds')->where('transaction_id', $id)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Anda sudah mengajukan refund untuk transaksi ini.');
        }

        return view('user.refund-form', compact('transaction'));
    }

    /**
     * 💾 Simpan Data Pengajuan Refund ke Database
     */
    public function store(Request $request, $id)
    {
        $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:150',
            'refund_reason'  => 'nullable|string',
        ]);

        $user = auth()->user();

        $transaction = DB::table('transactions')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->where('transactions.id', $id)
            ->where('transactions.email', $user->email)
            ->where('transactions.payment_status', 'paid')
            ->select('transactions.*', 'events.status as event_status', 'events.is_rescheduled')
            ->first();

        if (!$transaction) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        // Cek double input pengajuan saat submit
        $existing = DB::table('refunds')->where('transaction_id', $id)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Anda sudah mengajukan refund untuk transaksi ini.');
        }

        // 🌟 LOGIKA GERBANG DINAMIS BATCH REFUND:
        // Cari apakah ada batch penyerapan yang berstatus 'open' (Terbuka) untuk event ini
        $openBatch = DB::table('refund_batches')
            ->where('event_id', $transaction->event_id)
            ->where('status', 'open')
            ->first();

        // Pembeli hanya menerima pengembalian harga tiket murni (total_amount)
        $pureAmountToBuyer = $transaction->total_amount; 
        $refundTaxFee = 2500; // Log beban transfer massal PG flat

        if ($openBatch) {
            // Skenario A: Gerbang Terbuka -> Langsung ikat ke ID Batch dan status 'pending' (masuk antrean dalam)
            $batchId = $openBatch->id;
            $statusRefund = 'pending';
            $successMessage = 'Pengajuan refund berhasil! Data Anda telah masuk ke antrean resmi ' . $openBatch->name . '. Silakan tunggu pencairan dari Admin.';
        } else {
            // Skenario B: Gerbang Terkunci/Belum Dibuat -> batch_id kosong dan status 'waiting' (masuk antrean luar)
            $batchId = null;
            $statusRefund = 'waiting';
            $successMessage = 'Pengajuan refund berhasil diterima! Saat ini gerbang transfer sedang dikunci/ditutup untuk pembukuan. Data Anda aman dan masuk antrean berkas (Waiting List) untuk diserap pada pembukaan gerbang berikutnya.';
        }

        DB::table('refunds')->insert([
            'refund_batch_id'      => $batchId, 
            'transaction_id'       => $transaction->id,
            'bank_name'            => $request->bank_name,
            'account_number'       => $request->account_number,
            'account_name'         => $request->account_name,
            'refund_reason'        => $request->refund_reason,
            'grand_total_refunded' => $pureAmountToBuyer,
            'refunds_tax'          => $refundTaxFee,
            'status'               => $statusRefund,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return redirect()->route('user.tickets')->with('success', $successMessage);
    }
}
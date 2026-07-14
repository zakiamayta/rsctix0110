<?php

namespace App\Http\Controllers\Admin;   

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TicketWalletService;
use App\Services\MerchWalletService;

class AdminFinanceController extends Controller
{
    public function __construct()
    {
        // Berikan proteksi middleware agar hanya Admin Utama (role: admin) yang bisa masuk
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || auth()->user()->role !== 'admin') {
                abort(403, 'Aksi ini hanya diizinkan untuk Admin Utama.');
            }
            return $next($request);
        });
    }

    /**
     * 📊 Halaman 1: Dashboard Ringkasan Finansial Seluruh EO & Event
     */
    public function index(Request $request)
    {
        // Ambil daftar master EO untuk pilihan dropdown filter
        $allEo = DB::table('eo')->orderBy('nama_badan_usaha', 'asc')->get();

        $selectedEoId = $request->input('eo_id');
        $eoDetails = null;
        $events = [];

        if ($selectedEoId) {
            // 1. Ambil detail EO terpilih
            $eoDetails = DB::table('eo')->where('id', $selectedEoId)->first();

            if ($eoDetails) {
                // 2. Hitung Total Saldo Bersih EO (Akumulasi available_balance + held_balance dari seluruh event_wallets miliknya)
                $totalBalance = DB::table('events')
                    ->join('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                    ->where('events.eo_id', $selectedEoId)
                    ->sum(DB::raw('event_wallets.available_balance + event_wallets.held_balance'));

                // 3. Hitung Total Utang Berjalan dari tabel eo_debts yang belum lunas (unpaid & partially_paid)
                $totalDebt = DB::table('eo_debts')
                    ->where('eo_id', $selectedEoId)
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->sum('remaining_debt');

                // 3b. Hitung Total Tanggungan Refund yang BELUM DIPROSES (status pending) — bukan bagian dari utang
                $pendingRefundLiability = DB::table('refunds')
                    ->leftJoin('transactions', 'refunds.transaction_id', '=', 'transactions.id')
                    ->leftJoin('transaction_merch', 'refunds.transaction_merch_id', '=', 'transaction_merch.id')
                    ->leftJoin('events', DB::raw('COALESCE(transactions.event_id, transaction_merch.event_id)'), '=', 'events.id')
                    ->where('events.eo_id', $selectedEoId)
                    ->whereIn('refunds.status', ['waiting', 'pending']) // tanggungan = refund yang belum dieksekusi (waiting + pending)
                    ->sum(DB::raw('COALESCE(transactions.total_amount, transaction_merch.total_amount)'));

                // 4. Ambil Status Kunci Dompet Terkini (Cek apakah ada salah satu yang terkunci)
                $isLocked = DB::table('events')
                    ->join('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                    ->where('events.eo_id', $selectedEoId)
                    ->where('event_wallets.withdraw_locked', 1)
                    ->exists();

                $eoDetails->total_balance = $totalBalance;
                $eoDetails->total_debt = $totalDebt;
                $eoDetails->pending_refund_liability = $pendingRefundLiability;
                $eoDetails->is_locked = $isLocked;

                // 5. Ambil daftar event milik EO tersebut beserta saldo masing-masing dompetnya
                $events = DB::table('events')
                    ->leftJoin('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                    ->where('events.eo_id', $selectedEoId)
                    ->select(
                        'events.id',
                        'events.title',
                        'events.status as event_status',
                        'events.is_rescheduled',
                        'event_wallets.available_balance', 
                        'event_wallets.held_balance', 
                        'event_wallets.withdraw_locked'
                    )
                    ->orderBy('events.created_at', 'desc')
                    ->get();
            }
        }

        return view('admin.finance.index', compact('allEo', 'selectedEoId', 'eoDetails', 'events'));
    }

    /**
     * Halaman 2: Kelola Finansial Spesifik Per Event
     */
public function manageEvent($eventId)
{
    // Ambil data detail event beserta dompet dan profil EO-nya
    $event = DB::table('events')
        ->join('eo', 'events.eo_id', '=', 'eo.id')
        ->leftJoin('event_wallets', 'events.id', '=', 'event_wallets.event_id')
        ->where('events.id', $eventId)
        ->select(
            'events.id',
            'events.title',
            'events.status as event_status',
            'events.is_rescheduled',
            'events.eo_id',
            'eo.nama_badan_usaha',
            'event_wallets.available_balance',
            'event_wallets.held_balance',
            'event_wallets.withdraw_locked'
        )
        ->first();

    if (!$event) {
        return redirect()->route('admin.finance.index')->with('error', 'Data Event tidak ditemukan.');
    }

    // Dompet MERCH (terpisah dari dompet tiket yang sudah ikut di $event)
    $merchWallet = DB::table('merch_wallets')->where('event_id', $eventId)->first();

    // 🔴 Sisa utang aktif per TIPE komoditas (tiket vs merch) untuk event ini
    $ticketDebt = (float) DB::table('eo_debts')
        ->where('event_id', $eventId)->where('type', 'ticket')
        ->whereIn('status', ['unpaid', 'partially_paid'])->sum('remaining_debt');
    $merchDebt = (float) DB::table('eo_debts')
        ->where('event_id', $eventId)->where('type', 'merch')
        ->whereIn('status', ['unpaid', 'partially_paid'])->sum('remaining_debt');
    $outstandingDebt = $ticketDebt + $merchDebt; // total gabungan (kompatibilitas tampilan lama)

    // 🟡 Tanggungan refund (waiting + pending) per TIPE komoditas — terpisah dari utang
    $ticketRefundLiability = (float) DB::table('refunds')
        ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
        ->where('transactions.event_id', $eventId)
        ->whereIn('refunds.status', ['waiting', 'pending'])
        ->sum('transactions.total_amount');
    $merchRefundLiability = (float) DB::table('refunds')
        ->join('transaction_merch', 'refunds.transaction_merch_id', '=', 'transaction_merch.id')
        ->where('transaction_merch.event_id', $eventId)
        ->whereIn('refunds.status', ['waiting', 'pending'])
        ->sum('transaction_merch.total_amount');
    $pendingRefundLiability = $ticketRefundLiability + $merchRefundLiability;

    // Ambil riwayat pengajuan/permintaan Top Up khusus untuk event ini
    $topups = DB::table('eo_topups')
        ->where('event_id', $event->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return view('admin.finance.manage_event', compact(
        'event', 'merchWallet', 'topups',
        'outstandingDebt', 'ticketDebt', 'merchDebt',
        'pendingRefundLiability', 'ticketRefundLiability', 'merchRefundLiability'
    ));
}

    /**
     * Kirim Instruksi Tagihan Top-Up Baru ke EO
     */
    public function requestTopup(Request $request, $eventId)
    {
        $request->validate([
            'amount_requested' => 'required|numeric|min:1',
            'admin_note' => 'required|string',
            'type' => 'required|in:ticket,merch' // dompet tujuan: tiket atau merch
        ]);

        $event = DB::table('events')->where('id', $eventId)->first();

        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak valid.');
        }

        DB::table('eo_topups')->insert([
            'eo_id' => $event->eo_id,
            'event_id' => $event->id, // SINKRON: Memasukkan event_id saat pembuatan instruksi tagihan
            'type' => $request->type, // menentukan dompet (event_wallets / merch_wallets) yang disuntik
            'refund_id' => null,
            'amount_requested' => $request->amount_requested,
            'status' => 'requested',
            'admin_note' => $request->admin_note,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Instruksi permintaan Top Up berhasil dikirimkan ke pihak EO.');
    }

    /**
     * 👑 Verifikasi Pembayaran Top Up dari EO (Sistem Otomatis)
     */
    public function verifyTopup(Request $request, $id)
    {
        // Validasi data input form dari Blade
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string|max:500'
        ]);

        // 1. Cari data topup
        $topup = DB::table('eo_topups')->where('id', $id)->first();
        if (!$topup) {
            return redirect()->back()->with('error', 'Data top up tidak ditemukan.');
        }

        if ($topup->status !== 'pending_verification') {
            return redirect()->back()->with('error', 'Transaksi ini sudah pernah diproses sebelumnya.');
        }

        // Jalankan Database Transaction agar aman jika salah satu query gagal
        DB::beginTransaction();
        try {
            if ($request->status === 'approved') {
                // A. UPDATE STATUS TOPUP MENJADI APPROVED
                DB::table('eo_topups')->where('id', $id)->update([
                    'status' => 'approved',
                    'admin_note' => $request->admin_note,
                    'updated_at' => now()
                ]);

                // B. Top up = suntikan dana ke DOMPET sesuai tipe (tiket/merch). Dana masuk sebagai
                //    cadangan (held) via recalculate(); jika ada hutang bertipe sama, hutang itu dilunasi.
                //    available_balance TIDAK ditambah langsung (murni dari penjualan via recalculate()).
                $walletTable = $topup->type === 'merch' ? 'merch_wallets' : 'event_wallets';

                $debt = DB::table('eo_debts')
                    ->where('event_id', $topup->event_id)
                    ->where('type', $topup->type)
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->first();

                $debtReduced = 0;
                if ($debt) {
                    $newRemainingDebt = max(0, $debt->remaining_debt - $topup->amount_requested);
                    $newDebtStatus = $newRemainingDebt <= 0 ? 'paid' : 'partially_paid';
                    $debtReduced = $debt->remaining_debt - $newRemainingDebt;

                    DB::table('eo_debts')->where('id', $debt->id)->update([
                        'remaining_debt' => $newRemainingDebt,
                        'status' => $newDebtStatus,
                        'updated_at' => now()
                    ]);

                    // SINKRONISASI: eo.total_debt (cache) ikut turun; eo_debts tetap sumber utama.
                    if ($debtReduced > 0) {
                        DB::table('eo')->where('id', $topup->eo_id)->decrement('total_debt', $debtReduced);
                    }

                    // Mirror ke dompet BERTIPE SAMA: turunkan negative_balance; buka kunci bila lunas.
                    if ($newRemainingDebt <= 0) {
                        DB::table($walletTable)->where('event_id', $topup->event_id)->update([
                            'negative_balance' => 0,
                            'withdraw_locked'  => 0,
                            'updated_at'       => now(),
                        ]);
                    } elseif ($debtReduced > 0) {
                        DB::table($walletTable)->where('event_id', $topup->event_id)->update([
                            'negative_balance' => DB::raw('GREATEST(0, negative_balance - ' . (float) $debtReduced . ')'),
                            'updated_at'       => now(),
                        ]);
                    }
                }

                DB::commit();

                // Sinkronkan dompet bertipe sama agar suntikan (held) & pembukaan kunci langsung terlihat.
                if ($topup->type === 'merch') {
                    MerchWalletService::recalculate($topup->event_id);
                } else {
                    TicketWalletService::recalculate($topup->event_id);
                }

                $labelDompet = $topup->type === 'merch' ? 'merchandise' : 'tiket';
                $msg = $debtReduced > 0
                    ? 'Pembayaran disetujui! Nota utang ' . $labelDompet . ' EO berhasil dipotong sebesar Rp ' . number_format($debtReduced, 0, ',', '.') . '.'
                    : 'Pembayaran disetujui & dana masuk sebagai cadangan refund (held) dompet ' . $labelDompet . ' event ini.';
                return redirect()->back()->with('success', $msg);

            } else {
                // JIKA DITOLAK
                DB::table('eo_topups')->where('id', $id)->update([
                    'status' => 'rejected',
                    'admin_note' => $request->admin_note,
                    'updated_at' => now()
                ]);

                DB::commit();
                return redirect()->back()->with('success', 'Bukti pembayaran ditolak. Pemberitahuan telah dikirim ke dashboard EO.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses verifikasi: ' . $e->getMessage());
        }
    }
}
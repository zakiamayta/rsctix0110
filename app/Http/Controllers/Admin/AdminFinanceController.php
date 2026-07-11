<?php

namespace App\Http\Controllers\Admin;   

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

                // 4. Ambil Status Kunci Dompet Terkini (Cek apakah ada salah satu yang terkunci)
                $isLocked = DB::table('events')
                    ->join('event_wallets', 'events.id', '=', 'event_wallets.event_id')
                    ->where('events.eo_id', $selectedEoId)
                    ->where('event_wallets.withdraw_locked', 1)
                    ->exists();

                $eoDetails->total_balance = $totalBalance;
                $eoDetails->total_debt = $totalDebt;
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

        // Ambil riwayat pengajuan/permintaan Top Up khusus untuk event ini
        $topups = DB::table('eo_topups')
            ->where('event_id', $event->id) // SINKRON: Sekarang memfilter berdasarkan event_id baru
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.finance.manage_event', compact('event', 'topups'));
    }

    /**
     * Kirim Instruksi Tagihan Top-Up Baru ke EO
     */
    public function requestTopup(Request $request, $eventId)
    {
        $request->validate([
            'amount_requested' => 'required|numeric|min:1',
            'admin_note' => 'required|string'
        ]);

        $event = DB::table('events')->where('id', $eventId)->first();

        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak valid.');
        }

        DB::table('eo_topups')->insert([
            'eo_id' => $event->eo_id,
            'event_id' => $event->id, // SINKRON: Memasukkan event_id saat pembuatan instruksi tagihan
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

                // B. OTOMATIS TAMBAHKAN SALDO KE DOMPET EVENT (Menggunakan topup->event_id hasil Alter Table)
                $wallet = DB::table('event_wallets')->where('event_id', $topup->event_id)->first();
                if ($wallet) {
                    DB::table('event_wallets')->where('event_id', $topup->event_id)->increment('available_balance', $topup->amount_requested);
                } else {
                    // Jika dompet belum ada di database, buat baru otomatis
                    DB::table('event_wallets')->insert([
                        'event_id' => $topup->event_id,
                        'available_balance' => $topup->amount_requested,
                        'held_balance' => 0,
                        'withdraw_locked' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // C. OTOMATIS POTONG SISA UTANG EO (DEBTS) YANG BELUM LUNAS UNTUK EVENT INI
                $debt = DB::table('eo_debts')
                    ->where('event_id', $topup->event_id)
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->first();

                if ($debt) {
                    $newRemainingDebt = max(0, $debt->remaining_debt - $topup->amount_requested);
                    $newDebtStatus = $newRemainingDebt <= 0 ? 'paid' : 'partially_paid';

                    DB::table('eo_debts')->where('id', $debt->id)->update([
                        'remaining_debt' => $newRemainingDebt,
                        'status' => $newDebtStatus,
                        'updated_at' => now()
                    ]);

                    // Jika utang untuk event ini sudah lunas sepenuhnya (remaining = 0), buka kunci withdraw dompet!
                    if ($newRemainingDebt <= 0) {
                        DB::table('event_wallets')->where('event_id', $topup->event_id)->update([
                            'withdraw_locked' => 0,
                            'updated_at' => now()
                        ]);
                    }
                }

                DB::commit();
                return redirect()->back()->with('success', 'Pembayaran disetujui! Saldo event otomatis ditambahkan sebesar Rp ' . number_format($topup->amount_requested, 0, ',', '.') . ' dan pemotongan nota utang berhasil dilakukan.');

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
<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EoFinanceController extends Controller
{
    public function __construct()
    {
        // Memastikan hanya user dengan role 'eo' yang bisa mengakses kontroler finansial ini
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || auth()->user()->role !== 'eo') {
                abort(403, 'Akses ditolak. Halaman ini hanya untuk akun Event Organizer.');
            }
            return $next($request);
        });
    }

    /**
     * 🆔 Helper untuk mendapatkan ID EO dari user yang sedang login
     */
    private function getEoId()
    {
        return DB::table('eo')
            ->where('user_id', Auth::id())
            ->value('id');
    }

    /**
     * 📊 Menampilkan Dashboard Tagihan Utang & Riwayat Top Up Sisi EO
     */
    public function index()
    {
        $eoId = $this->getEoId();
        
        if (!$eoId) {
            return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
        }

        // 1. Ambil semua daftar utang (debts) yang belum lunas milik EO ini beserta judul event-nya
        $debts = DB::table('eo_debts')
            ->join('events', 'eo_debts.event_id', '=', 'events.id')
            ->where('eo_debts.eo_id', $eoId)
            ->select(
                'eo_debts.id',
                'eo_debts.total_debt',
                'eo_debts.remaining_debt',
                'eo_debts.status as debt_status',
                'events.title as event_title'
            )
            ->orderBy('eo_debts.created_at', 'desc')
            ->get();

        // Hitung total sisa utang berjalan (Menjumlahkan status unpaid & partially_paid)
        $totalRemainingDebt = $debts->whereIn('debt_status', ['unpaid', 'partially_paid'])->sum('remaining_debt');

        // 2. Ambil semua log transaksi Top Up (Tagihan instruksi dari admin maupun yang diupload EO)
        $topups = DB::table('eo_topups')
            ->where('eo_id', $eoId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('eo.finance.index', compact('debts', 'totalRemainingDebt', 'topups'));
    }

    /**
     * 📤 Proses Upload Bukti Transfer Top Up oleh Pihak EO
     */
    public function uploadProof(Request $request, $topupId)
    {
        $request->validate([
            'proof_of_transfer' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $topup = DB::table('eo_topups')->where('id', $topupId)->first();
        if (!$topup) {
            return redirect()->back()->with('error', 'Data top up tidak ditemukan.');
        }

        if ($request->hasFile('proof_of_transfer')) {
            $file = $request->file('proof_of_transfer');
            
            // 1. Membuat nama file unik berdasarkan ID topup dan timestamp
            $filename = 'proof_' . $topupId . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // 2. Menentukan folder tujuan langsung ke public/images/transfer_proof
            $destinationPath = public_path('images/transfer_proof');

            // 3. Pastikan folder tersebut sudah otomatis dibuat jika belum ada di server
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            // 4. Pindahkan file fisik gambar dari temporary ke folder tujuan
            $file->move($destinationPath, $filename);

            // 5. Menyimpan path relatif yang akan dibaca oleh helper url() ke database
            $dbPath = 'images/transfer_proof/' . $filename;

            // 6. Update status dan path di database
            DB::table('eo_topups')->where('id', $topupId)->update([
                'proof_of_transfer' => $dbPath,
                'status' => 'pending_verification',
                'updated_at' => now()
            ]);

            return redirect()->back()->with('success', 'Bukti transfer berhasil diupload. Menunggu verifikasi admin.');
        }

        return redirect()->back()->with('error', 'Gagal mengupload file.');
    }
}
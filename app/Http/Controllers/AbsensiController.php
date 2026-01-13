<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class AbsensiController extends Controller
{
    /**
     * Tampilkan form absensi berdasarkan kode unik tiket.
     */
    public function showPasswordForm($kode)
    {
        $transaction = Transaction::where('kode_unik', $kode)->first();

        if (!$transaction) {
            abort(404);
        }

        // Kirim data transaksi ke view (misal untuk menampilkan kode unik)
        return view('absen.form', compact('transaction'));
    }

    /**
     * Tangani proses absensi berdasarkan input password petugas.
     */
    public function handleScan(Request $request, $kode)
    {
        // 1️⃣ Validasi input
        $request->validate([
            'password' => 'required',
        ]);

        // 2️⃣ Cek password petugas
        if ($request->password !== config('app.gate_password', 'gate123')) {
            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('error', 'Password petugas salah.');
        }

        // 3️⃣ Cek transaksi
        $transaction = Transaction::where('kode_unik', $kode)->first();

        if (!$transaction) {
            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        // 4️⃣ Jika sudah absen, kirim notifikasi status
        if ($transaction->is_registered) {
            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('status', 'already_scanned')
                ->with('transaction_id', $transaction->id);
        }

        // 5️⃣ Proses absensi baru
        DB::beginTransaction();
        try {
            $transaction->is_registered = true;
            $transaction->registered_at = now();
            $transaction->save();

            // Ambil salah satu attendee untuk ditampilkan di popup
            $attendee = DB::table('ticket_attendees')
                ->where('transaction_id', $transaction->id)
                ->first();

            // Hitung jumlah total tiket yang dimiliki transaksi ini
            $ticket_count = DB::table('ticket_attendees')
                ->where('transaction_id', $transaction->id)
                ->count();

            DB::commit();

            // 6️⃣ Redirect ke halaman yang sama dengan data hasil absensi
            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('status', 'success')
                ->with('attendee_name', $attendee->name ?? 'Tidak diketahui')
                ->with('attendee_phone', $attendee->phone_number ?? '-')
                ->with('ticket_count', $ticket_count)
                ->with('transaction_kode_unik', $transaction->kode_unik);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('error', 'Terjadi kesalahan saat menyimpan absensi.');
        }
    }
}

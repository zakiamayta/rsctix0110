<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TicketAttendee;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AbsensiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SISI MONITORING / PANTAUAN (Multi-role: Admin & EO)
    |--------------------------------------------------------------------------
    |
    | Bagian ini menangani halaman daftar absensi pengunjung. File view
    | otomatis diarahkan berdasarkan role user yang sedang login.
    |
    */
    
    /**
     * Tampilkan halaman pantauan absensi pengunjung untuk Admin dan EO.
     */
    public function indexPantauan(Request $request)
    {
        $user = Auth::user();
        
        // Base Query untuk mengambil data peserta beserta relasi transaksi dan jenis tiketnya
        $query = TicketAttendee::with(['transaction.event', 'ticket']);

        // 🛡️ SEKAT DATA BERDASARKAN ROLE
        if ($user->role === 'eo') {
            // Jika yang login adalah EO, cari ID EO-nya terlebih dahulu
            $eo = DB::table('eo')->where('user_id', $user->id)->first();
            
            if (!$eo) {
                return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
            }

            // Ambil daftar ID event milik EO tersebut
            $eventIds = Event::where('eo_id', $eo->id)->pluck('id');

            // Filter agar EO hanya bisa melihat transaksi dari tiket event miliknya sendiri
            $query->whereHas('transaction', function ($q) use ($eventIds) {
                $q->whereIn('event_id', $eventIds);
            });

            // Ambil daftar pilihan filter khusus event milik EO ini saja
            $events = Event::where('eo_id', $eo->id)->get();

        } else {
            // Jika Admin (atau role selain EO), tampilkan semua pilihan event untuk difilter
            $events = Event::all();
        }

        // 🔍 FILTER PENCARIAN (Nama, Email, No HP, atau Kode Unik)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhereHas('transaction', function ($subQ) use ($search) {
                      $subQ->where('email', 'like', "%{$search}%")
                           ->orWhere('kode_unik', 'like', "%{$search}%");
                  });
            });
        }

        // 🔍 FILTER EVENT ID
        if ($request->filled('event_id')) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->where('event_id', $request->event_id);
            });
        }

        // 🔍 FILTER STATUS ABSENSI
        if ($request->filled('status')) {
            $status = $request->status;
            $query->whereHas('transaction', function ($q) use ($status) {
                $q->where('is_registered', $status === 'sudah' ? 1 : 0);
            });
        }

        // Ambil data dengan pagination
        $attendees = $query->latest('id')->paginate(10)->withQueryString();

        // 🔄 PENENTUAN VIEW BERDASARKAN ROLE (Memperbaiki error View not found)
        if ($user->role === 'eo') {
            // Mengarah ke resources/views/eo/absensi/tiket.blade.php
            return view('eo.absensi.tiket', compact('events', 'attendees'));
        }

        // Mengarah ke resources/views/admin/absensi.blade.php (untuk Admin)
        return view('admin.absensi', compact('events', 'attendees'));
    }

    /**
     * Tombol Aksi Manual Langsung dari Tabel Pantauan (Guna mengatasi kendala scan kamera)
     */
    public function absenManual($id)
    {
        $attendee = TicketAttendee::findOrFail($id);
        $transaction = Transaction::find($attendee->transaction_id);

        if (!$transaction) {
            return redirect()->back()->with('error', 'Data transaksi tidak ditemukan.');
        }

        $transaction->update([
            'is_registered' => true,
            'registered_at' => now(),
        ]);

        return redirect()->back()->with('success', "Berhasil melakukan absensi manual untuk: {$attendee->name}");
    }

    public function batalAbsen($id)
    {
        $attendee = TicketAttendee::findOrFail($id);
        $transaction = Transaction::find($attendee->transaction_id);

        if (!$transaction) {
            return redirect()->back()->with('error', 'Data transaksi tidak ditemukan.');
        }

        $transaction->update([
            'is_registered' => false,
            'registered_at' => null,
        ]);

        return redirect()->back()->with('success', "Absensi untuk {$attendee->name} berhasil dibatalkan.");
    }


    /*
    |--------------------------------------------------------------------------
    | SISI PUBLIC (PETUGAS GATES HP: PROSES SCAN QR CODE)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Tampilkan form absensi berdasarkan kode unik tiket.
     */
    public function showPasswordForm($kode)
    {
        $transaction = Transaction::where('kode_unik', $kode)->with('event')->first();

        if (!$transaction) {
            abort(404);
        }

        return view('absen.form', compact('transaction'));
    }

    /**
     * Tangani proses absensi berdasarkan input password petugas.
     */
    public function handleScan(Request $request, $kode)
    {
        $request->validate([
            'password' => 'required',
        ]);

        if ($request->password !== config('app.gate_password', 'gate123')) {
            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('error', 'Password petugas salah.');
        }

        $transaction = Transaction::where('kode_unik', $kode)->first();

        if (!$transaction) {
            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        if ($transaction->is_registered) {
            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('status', 'already_scanned')
                ->with('message', 'Kode tiket ini sudah pernah digunakan untuk absensi sebelumnya.')
                ->with('transaction_id', $transaction->id);
        }

        DB::beginTransaction();
        try {
            $transaction->is_registered = true;
            $transaction->registered_at = now();
            $transaction->save();

            $attendee = DB::table('ticket_attendees')
                ->where('transaction_id', $transaction->id)
                ->first();

            $ticket_count = DB::table('ticket_attendees')
                ->where('transaction_id', $transaction->id)
                ->count();

            DB::commit();

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
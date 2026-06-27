<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\TicketAttendee;
use App\Models\Transaction;
use App\Models\TransactionMerch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EoAbsensiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SISI EO: HALAMAN PANTAUAN ABSENSI (DASHBOARD)
    |--------------------------------------------------------------------------
    */

    /**
     * Pantauan Absensi Tiket Masuk Event (Gate) bagi EO
     */
    public function indexTiket(Request $request)
    {
        $eo = DB::table('eo')->where('user_id', Auth::id())->first();

        if (!$eo) {
            return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
        }

        $events = Event::where('eo_id', $eo->id)->get();
        $eventIds = $events->pluck('id');

        // Query mengambil data TicketAttendee yang memiliki transaksi sukses milik EO ini
        $query = TicketAttendee::whereHas('transaction', function ($q) use ($eventIds) {
            $q->whereIn('event_id', $eventIds);
        })->with(['transaction.event', 'ticket']);

        // Filter Event
        if ($request->filled('event_id')) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->where('event_id', $request->event_id);
            });
        }

        // Filter Pencarian (Nama, Email, No HP, atau Kode Unik)
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

        // Filter Status Absen
        if ($request->filled('status')) {
            $status = $request->status;
            $query->whereHas('transaction', function ($q) use ($status) {
                $q->where('is_registered', $status === 'sudah' ? 1 : 0);
            });
        }

        $attendees = $query->latest('id')->paginate(10)->withQueryString();

        return view('eo.absensi.tiket', compact('events', 'attendees'));
    }

    /**
     * Tombol Aksi Manual Langsung dari Tabel Pantauan EO (Jika ada kendala QR)
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
    | SISI PETUGAS HP: PROSES SCAN QR CODE TIKET
    |--------------------------------------------------------------------------
    */

    /**
     * Tampilkan form input password gate saat QR Tiket di-scan lewat HP
     */
    public function showAbsenForm($kode)
    {
        $transaction = Transaction::where('kode_unik', $kode)->with('event')->firstOrFail();
        return view('absen.form', compact('transaction'));
    }

    /**
     * Handle pengiriman password dari HP dan mengubah status transaksi menjadi 'Sudah Absen'
     */
    public function handleScan(Request $request, $kode)
    {
        $request->validate([
            'password' => 'required',
        ]);

        // Cek password gate (bisa disesuaikan passwordnya)
        if ($request->password !== config('app.gate_password', 'gate123')) {
            return redirect()->route('absen.form', $kode)->with('error', 'Password petugas salah.');
        }

        $transaction = Transaction::where('kode_unik', $kode)->firstOrFail();

        // Jika sudah pernah absen sebelumnya, tampilkan info
        if ($transaction->is_registered) {
            return redirect()->route('absen.form', $kode)
                ->with('status', 'warning')
                ->with('message', 'Kode tiket ini sudah pernah digunakan untuk absensi sebelumnya.');
        }

        DB::beginTransaction();
        try {
            // Update status absensi utama pada transaksi
            $transaction->is_registered = true;
            $transaction->registered_at = now();
            $transaction->save();

            // Ambil data peserta di dalam transaksi untuk ditampilkan di view sukses HP
            $attendees = TicketAttendee::where('transaction_id', $transaction->id)->get();
            $attendeeUtama = $attendees->first();

            DB::commit();

            return redirect()->route('absen.form', $kode)
                ->with('status', 'success')
                ->with('attendee_name', $attendeeUtama->name ?? 'Pengunjung')
                ->with('attendee_phone', $attendeeUtama->phone_number ?? '-')
                ->with('ticket_count', $attendees->count())
                ->with('transaction_kode_unik', $transaction->kode_unik);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('absen.form', $kode)->with('error', 'Terjadi kesalahan sistem saat memproses absensi.');
        }
    }
}
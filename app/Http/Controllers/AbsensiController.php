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
    */

    public function indexPantauan(Request $request)
    {
        $user = Auth::user();

        $query = TicketAttendee::with(['transaction.event', 'ticket']);

        // 🛡️ SEKAT DATA BERDASARKAN ROLE
        if ($user->role === 'eo') {
            $eo = DB::table('eo')->where('user_id', $user->id)->first();

            if (!$eo) {
                return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
            }

            $eventIds = Event::where('eo_id', $eo->id)->pluck('id');

            $query->whereHas('transaction', function ($q) use ($eventIds) {
                $q->whereIn('event_id', $eventIds);
            });

            $events = Event::where('eo_id', $eo->id)->get();

        } else {
            $events = Event::all();
        }

        // 🔍 FILTER PENCARIAN (Nama, No HP, Email peserta, atau Kode Unik peserta)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")       // ✅ email milik attendee
                  ->orWhere('kode_unik', 'like', "%{$search}%")   // ✅ kode unik milik attendee
                  ->orWhereHas('transaction', function ($subQ) use ($search) {
                      $subQ->where('email', 'like', "%{$search}%"); // email pembeli, tetap dicari juga
                  });
            });
        }

        // 🔍 FILTER EVENT ID
        if ($request->filled('event_id')) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->where('event_id', $request->event_id);
            });
        }

        // 🔍 FILTER STATUS ABSENSI — sekarang langsung dari ticket_attendees, bukan transaksi
        if ($request->filled('status')) {
            $status = $request->status;
            $query->where('is_registered', $status === 'sudah' ? 1 : 0);
        }

        $attendees = $query->latest('id')->paginate(10)->withQueryString();

        if ($user->role === 'eo') {
            return view('eo.absensi.tiket', compact('events', 'attendees'));
        }

        return view('admin.absensi', compact('events', 'attendees'));
    }

    /**
     * Tombol Aksi Manual Langsung dari Tabel Pantauan
     * ✅ Sekarang update langsung ke attendee, bukan ke transaksi
     */
    public function absenManual($id)
    {
        $attendee = TicketAttendee::findOrFail($id);

        $attendee->update([
            'is_registered' => true,
            'registered_at' => now(),
        ]);

        return redirect()->back()->with('success', "Berhasil melakukan absensi manual untuk: {$attendee->name}");
    }

    public function batalAbsen($id)
    {
        $attendee = TicketAttendee::findOrFail($id);

        $attendee->update([
            'is_registered' => false,
            'registered_at' => null,
        ]);

        return redirect()->back()->with('success', "Absensi untuk {$attendee->name} berhasil dibatalkan.");
    }


    /*
    |--------------------------------------------------------------------------
    | SISI PUBLIC (PETUGAS GATES HP: PROSES SCAN QR CODE)
    |--------------------------------------------------------------------------
    | ✅ QR sekarang membawa kode_unik milik ATTENDEE, bukan transaksi.
    */

    /**
     * Tampilkan form absensi berdasarkan kode unik TIKET PESERTA.
     */
    public function showPasswordForm($kode)
    {
        $attendee = TicketAttendee::where('kode_unik', $kode)
            ->with(['transaction.event', 'ticket.jadwal'])
            ->first();

        if (!$attendee) {
            abort(404);
        }

        // Nama variabel view tetap $transaction agar kompatibel dengan blade lama,
        // tapi sekarang berisi relasi transaksi milik attendee ini + data attendee itu sendiri.
        $transaction = $attendee->transaction;

        return view('absen.form', compact('transaction', 'attendee'));
    }

    /**
     * Tangani proses absensi berdasarkan input password petugas.
     * ✅ Yang diperiksa & diupdate sekarang adalah attendee, bukan transaksi.
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

        $attendee = TicketAttendee::where('kode_unik', $kode)->first();

        if (!$attendee) {
            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('error', 'Data tiket tidak ditemukan.');
        }

        if ($attendee->is_registered) {
            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('status', 'already_scanned')
                ->with('message', 'Kode tiket ini sudah pernah digunakan untuk absensi sebelumnya.')
                ->with('transaction_id', $attendee->transaction_id);
        }

        DB::beginTransaction();
        try {
            $attendee->is_registered = true;
            $attendee->registered_at = now();
            $attendee->save();

            DB::commit();

            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('status', 'success')
                ->with('attendee_name', $attendee->name ?? 'Tidak diketahui')
                ->with('attendee_phone', $attendee->phone_number ?? '-')
                ->with('ticket_count', 1) // ✅ 1 QR = 1 orang sekarang, bukan lagi jumlah peserta transaksi
                ->with('transaction_kode_unik', $attendee->kode_unik);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('absen.form', ['kode' => $kode])
                ->with('error', 'Terjadi kesalahan saat menyimpan absensi.');
        }
    }
}
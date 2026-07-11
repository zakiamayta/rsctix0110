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

    public function indexTiket(Request $request)
    {
        $eo = DB::table('eo')->where('user_id', Auth::id())->first();

        if (!$eo) {
            return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
        }

        $events = Event::where('eo_id', $eo->id)->get();
        $eventIds = $events->pluck('id');

        $query = TicketAttendee::whereHas('transaction', function ($q) use ($eventIds) {
            $q->whereIn('event_id', $eventIds);
        })->with(['transaction.event', 'ticket']);

        // Filter Event
        if ($request->filled('event_id')) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->where('event_id', $request->event_id);
            });
        }

        // Filter Pencarian (Nama, No HP, Email peserta, atau Kode Unik peserta)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('kode_unik', 'like', "%{$search}%")
                  ->orWhereHas('transaction', function ($subQ) use ($search) {
                      $subQ->where('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Status Absen — sekarang dari ticket_attendees langsung
        if ($request->filled('status')) {
            $status = $request->status;
            $query->where('is_registered', $status === 'sudah' ? 1 : 0);
        }

        $attendees = $query->latest('id')->paginate(10)->withQueryString();

        return view('eo.absensi.tiket', compact('events', 'attendees'));
    }

    /**
     * Tombol Aksi Manual — update langsung ke attendee.
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
    | SISI PETUGAS HP: PROSES SCAN QR CODE TIKET
    |--------------------------------------------------------------------------
    */

    public function showAbsenForm($kode)
    {
        $attendee = TicketAttendee::where('kode_unik', $kode)
            ->with(['transaction.event', 'ticket.jadwal'])
            ->firstOrFail();

        $transaction = $attendee->transaction;

        return view('absen.form', compact('transaction', 'attendee'));
    }

    public function handleScan(Request $request, $kode)
    {
        $request->validate([
            'password' => 'required',
        ]);

        if ($request->password !== config('app.gate_password', 'gate123')) {
            return redirect()->route('absen.form', $kode)->with('error', 'Password petugas salah.');
        }

        $attendee = TicketAttendee::where('kode_unik', $kode)->firstOrFail();

        if ($attendee->is_registered) {
            return redirect()->route('absen.form', $kode)
                ->with('status', 'warning')
                ->with('message', 'Kode tiket ini sudah pernah digunakan untuk absensi sebelumnya.');
        }

        DB::beginTransaction();
        try {
            $attendee->is_registered = true;
            $attendee->registered_at = now();
            $attendee->save();

            DB::commit();

            return redirect()->route('absen.form', $kode)
                ->with('status', 'success')
                ->with('attendee_name', $attendee->name ?? 'Pengunjung')
                ->with('attendee_phone', $attendee->phone_number ?? '-')
                ->with('ticket_count', 1) // ✅ 1 QR = 1 orang
                ->with('transaction_kode_unik', $attendee->kode_unik);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('absen.form', $kode)->with('error', 'Terjadi kesalahan sistem saat memproses absensi.');
        }
    }
}
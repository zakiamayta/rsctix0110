<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Ditambahkan untuk handle update tabel jadwal otomatis

class EventApprovalController extends Controller
{
    /**
     * 📌 List semua pengajuan event (Baru, Cancel, & Reschedule)
     */
    public function index()
    {
        // Diperbarui menggunakan whereIn agar semua jenis request dari EO masuk ke dashboard Owner
        $events = Event::with('eo')
                    ->whereIn('status', ['pending', 'pending_cancel', 'pending_reschedule'])
                    ->latest()
                    ->get();

        return view('owner.event-list', compact('events'));
    }

    /**
     * 📌 Detail event + jadwal + tiket
     */
    public function show(Event $event)
    {
        $event->load(['eo', 'tickets.jadwal']);

        return view('owner.event-show', compact('event'));
    }

    /**
     * ✅ APPROVE EVENT BARU / RESUBMIT
     */
    public function approve(Event $event)
    {
        $event->update([
            'status' => 'approved'
        ]);

        return redirect()
            ->route('owner.events.index')
            ->with('success', 'Event berhasil di-approve dan sekarang aktif');
    }

    /**
     * ❌ REJECT EVENT BARU / RESUBMIT
     */
    public function reject(Request $request, Event $event)
    {
        $event->update([
            'status'     => 'rejected',
            'owner_note' => $request->note // menyimpan alasan penolakan jika ada input note
        ]);

        return redirect()
            ->route('owner.events.index')
            ->with('success', 'Event telah ditolak');
    }

    /*
    |--------------------------------------------------------------------------
    | 🆕 PROSES REQUEST CANCEL (PEMBATALAN EVENT) BY OWNER
    |--------------------------------------------------------------------------
    */

    /**
     * ✅ Owner menyetujui pembatalan event
     */
    public function confirmCancel(Event $event)
    {
        if ($event->status !== 'pending_cancel') {
            abort(400, 'Bukan pengajuan pembatalan yang sah.');
        }

        $event->update([
            'status' => 'cancelled'
        ]);

        return redirect()
            ->route('owner.events.index')
            ->with('success', 'Event resmi DIBATALKAN. Sistem siap memproses refund.');
    }

    /**
     * ❌ Owner menolak pembatalan (Event dipaksa tetap jalan)
     */
    public function rejectCancel(Request $request, Event $event)
    {
        if ($event->status !== 'pending_cancel') {
            abort(400, 'Bukan pengajuan pembatalan yang sah.');
        }

        $event->update([
            'status'     => 'approved', // Dikembalikan ke status aktif semula
            'owner_note' => $request->note
        ]);

        return redirect()
            ->route('owner.events.index')
            ->with('success', 'Pengajuan pembatalan ditolak. Event tetap berjalan aktif.');
    }

    /*
    |--------------------------------------------------------------------------
    | 🆕 PROSES REQUEST RESCHEDULE (PERUBAHAN JADWAL) BY OWNER
    |--------------------------------------------------------------------------
    */

    /**
     * ✅ Owner menyetujui perubahan jadwal (Reschedule)
     */
    public function approveReschedule(Event $event)
    {
        if ($event->status !== 'pending_reschedule' || !$event->proposed_date) {
            abort(400, 'Bukan pengajuan reschedule yang sah.');
        }

        // Memakai Database Transaction agar jika salah satu tabel gagal, data aman tidak korup
        DB::transaction(function () use ($event) {
            
            // 1. Pindahkan proposed_date ke date utama, set status balik ke approved
            $event->update([
                'date'          => $event->proposed_date,
                'status'        => 'approved',
                'proposed_date' => null // dikosongkan kembali setelah disetujui
            ]);

            // 2. Samakan tanggal di tabel `jadwal` yang terikat dengan event ini
            DB::table('jadwal')
                ->where('event_id', $event->id)
                ->update([
                    'tanggal'    => $event->date,
                    'updated_at' => now()
                ]);
        });

        return redirect()
            ->route('owner.events.index')
            ->with('success', 'Perubahan jadwal event berhasil disetujui!');
    }

    /**
     * ❌ Owner menolak perubahan jadwal
     */
    public function rejectReschedule(Request $request, Event $event)
    {
        if ($event->status !== 'pending_reschedule') {
            abort(400, 'Bukan pengajuan reschedule yang sah.');
        }

        $event->update([
            'status'        => 'approved', // Balik ke aktif semula tanpa merubah tanggal lama
            'proposed_date' => null,       // Buang usulan tanggal baru
            'owner_note'    => $request->note
        ]);

        return redirect()
            ->route('owner.events.index')
            ->with('success', 'Pengajuan reschedule ditolak. Jadwal tetap menggunakan tanggal lama.');
    }
}
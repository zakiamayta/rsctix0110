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
            'status' => 'cancelled',
            'merch_cancel_decision' => null // Memastikan null agar alert penentu keputusan langsung mengunci dashboard EO
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
    if ($event->status !== 'pending_reschedule') {
        abort(400);
    }

    $event->update([
        'date' => $event->proposed_date,
        'status' => 'approved',
        'proposed_date' => null,
        'is_rescheduled' => $event->is_rescheduled + 1,

        // kasih hak edit sekali
        'can_adjust_schedule' => true,
    ]);

    return redirect()
        ->route('owner.events.index')
        ->with(
            'success',
            'Reschedule disetujui. EO dapat melakukan penyesuaian jadwal satu kali.'
        );
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
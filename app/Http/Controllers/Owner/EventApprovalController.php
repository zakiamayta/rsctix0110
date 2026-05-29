<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventApprovalController extends Controller
{
    /**
     * 📌 List semua event pending
     */
    public function index()
    {
        $events = Event::with('eo')
                    ->where('status', 'pending')
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
     * ✅ APPROVE
     */
    public function approve(Event $event)
    {
        $event->update([
            'status' => 'approved'
        ]);

        return redirect()
            ->route('owner.events.index')
            ->with('success', 'Event berhasil di-approve');
    }

    /**
     * ❌ REJECT
     */
    public function reject(Request $request, Event $event)
    {
        $event->update([
            'status' => 'rejected'
        ]);

        return redirect()
            ->route('owner.events.index')
            ->with('success', 'Event ditolak');
    }
}
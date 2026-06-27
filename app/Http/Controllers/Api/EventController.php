<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::latest()->get();
        return view('eo.index', compact('events'));
    }

    public function create()
    {
        return view('eo.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'event_url' => 'nullable|string|max:255',
            'organizer' => 'nullable|string|max:100',
            'instagram' => 'nullable|string|max:100',
            'location' => 'required|string|max:255',
            'date' => 'required|date',
            'ticket_sale_start' => 'nullable|date',
            'ticket_redeem_start' => 'nullable|date',
            'min_age' => 'nullable|integer|min:0',
            'max_tickets_per_email' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'poster' => 'nullable|image|max:2048',
        ]);

        DB::transaction(function () use ($request) {

            $posterPath = null;
            if ($request->hasFile('poster')) {
                $posterPath = $request->file('poster')->store('events', 'public');
            }

            Event::create([
                'title' => $request->title,
                'event_url' => $request->event_url,
                'organizer' => $request->organizer,
                'instagram' => $request->instagram,
                'location' => $request->location,
                'date' => $request->date,
                'ticket_sale_start' => $request->ticket_sale_start,
                'ticket_redeem_start' => $request->ticket_redeem_start,
                'min_age' => $request->min_age,
                'max_tickets_per_email' => $request->max_tickets_per_email,
                'description' => $request->description,
                'poster' => $posterPath,
                'status' => 'pending',
            ]);
        });

        return redirect()
            ->route('eo.event.index')
            ->with('success', 'Event berhasil ditambahkan');
    }

    public function edit(Event $event)
    {
        return view('eo.edit', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'date' => 'required|date',
            'max_tickets_per_email' => 'required|integer|min:1',
            'poster' => 'nullable|image|max:2048',
        ]);

        DB::transaction(function () use ($request, $event) {

            if ($request->hasFile('poster')) {
                if ($event->poster) {
                    Storage::disk('public')->delete($event->poster);
                }
                $event->poster = $request->file('poster')->store('events', 'public');
            }

            $event->update($request->only([
                'title',
                'event_url',
                'organizer',
                'instagram',
                'location',
                'date',
                'ticket_sale_start',
                'ticket_redeem_start',
                'min_age',
                'max_tickets_per_email',
                'description',
            ]));
        });

        return redirect()
            ->route('eo.event.index')
            ->with('success', 'Event berhasil diperbarui');
    }

    public function destroy(Event $event)
    {
        if ($event->poster) {
            Storage::disk('public')->delete($event->poster);
        }

        $event->delete();

        return redirect()
            ->route('eo.event.index')
            ->with('success', 'Event berhasil dihapus');
    }
}

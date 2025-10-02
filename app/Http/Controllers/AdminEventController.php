<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class AdminEventController extends Controller
{
    public function index()
    {
        $events = Event::with('tickets')->latest()->get();
        return view('admin.admin-event', compact('events'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'lineup'      => 'nullable|string|max:100',
            'organizer'   => 'nullable|string|max:100',
            'instagram'   => 'nullable|string|max:100',
            'date'        => 'required|date',
            'location'    => 'required|string|max:255',
            'poster'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'max_tickets_per_email' => 'nullable|integer',
            'tickets'     => 'required|array|min:1',
            'tickets.*.name'  => 'required|string|max:100',
            'tickets.*.price' => 'required|numeric',
            'tickets.*.stock' => 'required|integer',
        ]);

        DB::transaction(function () use ($request) {
            $eventData = $request->only([
                'title',
                'description',
                'lineup',
                'organizer',
                'instagram',
                'date',
                'location',
                'max_tickets_per_email'
            ]);

            if ($request->hasFile('poster')) {
                $filename = time() . '.' . $request->poster->getClientOriginalExtension();
                $request->poster->move(public_path('images/events'), $filename);
                $eventData['poster'] = 'images/events/' . $filename;
            }

            $event = Event::create($eventData);

            foreach ($request->tickets as $ticketInput) {
                Ticket::create([
                    'event_id' => $event->id,
                    'name'     => $ticketInput['name'],
                    'price'    => $ticketInput['price'],
                    'stock'    => $ticketInput['stock'],
                ]);
            }
        });

        return redirect()->route('admin.event.index')->with('success','Event berhasil ditambahkan');
    }

    public function edit($id)
    {
        $event = Event::with('tickets')->findOrFail($id);
        return response()->json($event);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'lineup'      => 'nullable|string|max:100',
            'organizer'   => 'nullable|string|max:100',
            'instagram'   => 'nullable|string|max:100',
            'date'        => 'required|date',
            'location'    => 'required|string|max:255',
            'poster'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'max_tickets_per_email' => 'nullable|integer',
            'tickets'     => 'required|array|min:1',
            'tickets.*.name'  => 'required|string|max:100',
            'tickets.*.price' => 'required|numeric',
            'tickets.*.stock' => 'required|integer',
        ]);

        DB::transaction(function () use ($request,$id) {
            $event = Event::findOrFail($id);

            $eventData = $request->only([
                'title',
                'description',
                'lineup',
                'organizer',
                'instagram',
                'date',
                'location',
                'max_tickets_per_email'
            ]);

            if ($request->hasFile('poster')) {
                $filename = time() . '.' . $request->poster->getClientOriginalExtension();
                $request->poster->move(public_path('images/events'), $filename);
                $eventData['poster'] = 'images/events/' . $filename;
            }

            $event->update($eventData);

            // hapus tiket lama lalu buat ulang
            $event->tickets()->delete();
            foreach ($request->tickets as $ticketInput) {
                Ticket::create([
                    'event_id' => $event->id,
                    'name'     => $ticketInput['name'],
                    'price'    => $ticketInput['price'],
                    'stock'    => $ticketInput['stock'],
                ]);
            }
        });

        return redirect()->route('admin.event.index')->with('success','Event berhasil diperbarui');
    }

    public function destroy($id)
    {
        $event = Event::with('tickets')->findOrFail($id);
        $event->tickets()->delete();
        $event->delete();

        return redirect()->route('admin.event.index')->with('success','Event berhasil dihapus');
    }

    public function show($id)
    {
        $event = Event::with('tickets')->findOrFail($id);
        return response()->json($event);
    }
}

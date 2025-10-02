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
        // tampilkan semua event dengan tiketnya
        $events = Event::with('tickets')->latest()->get();
        return view('admin.admin-event', compact('events'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
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
            // simpan event dulu
            $eventData = $request->only(['title','description','date','location','max_tickets_per_email']);
            
            if ($request->hasFile('poster')) {
                $filename = time() . '.' . $request->poster->getClientOriginalExtension();
                $request->poster->move(public_path('images/events'), $filename);
                $eventData['poster'] = 'images/events/' . $filename; // path relatif
            }

            $event = Event::create($eventData);

            // simpan tiket
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

            $eventData = $request->only(['title','description','date','location','max_tickets_per_email']);
            
            if ($request->hasFile('poster')) {
                // hapus poster lama jika ada
                if ($event->poster && file_exists(public_path($event->poster))) {
                    @unlink(public_path($event->poster));
                }

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

        // hapus poster fisik jika ada
        if ($event->poster && file_exists(public_path($event->poster))) {
            @unlink(public_path($event->poster));
        }

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

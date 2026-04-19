<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminEventController extends Controller
{
    public function index()
    {
        $events = DB::table('events')->latest()->get();
        return view('admin.event', compact('events'));
    }

    public function create()
    {
        return view('admin.event-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'date'        => 'required|date',
            'location'    => 'required|string|max:255',
            'poster'      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'jadwal' => 'required|array|min:1',
            'jadwal.*.info' => 'required|string|max:255',
            'jadwal.*.tanggal' => 'required|date',

            'jadwal.*.tickets' => 'required|array|min:1',
            'jadwal.*.tickets.*.name'  => 'required|string|max:100',
            'jadwal.*.tickets.*.price' => 'required|integer',
            'jadwal.*.tickets.*.stock' => 'required|integer',
            'jadwal.*.tickets.*.start_sale' => 'nullable|date',
            'jadwal.*.tickets.*.end_sale'   => 'nullable|date',
        ]);

        // upload poster
        $posterPath = null;
        if ($request->hasFile('poster')) {
            $filename = time() . '.' . $request->poster->getClientOriginalExtension();
            $request->poster->move(public_path('images/events'), $filename);
            $posterPath = 'images/events/' . $filename;
        }

        DB::transaction(function () use ($request, $posterPath) {

            $eventId = DB::table('events')->insertGetId([
                'title'       => $request->title,
                'description' => $request->description,
                'date'        => $request->date,
                'location'    => $request->location,
                'poster'      => $posterPath,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            foreach ($request->jadwal as $jadwalInput) {

                $jadwalId = DB::table('jadwal')->insertGetId([
                    'event_id'  => $eventId,
                    'info'      => $jadwalInput['info'],
                    'tanggal'   => $jadwalInput['tanggal'],
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);

                foreach ($jadwalInput['tickets'] as $ticket) {

                    // VALIDASI MANUAL SALE TIME
                    if (!empty($ticket['start_sale']) && !empty($ticket['end_sale'])) {
                        if ($ticket['end_sale'] < $ticket['start_sale']) {
                            throw new \Exception('End sale tidak boleh sebelum start sale');
                        }
                    }

                    DB::table('tickets')->insert([
                        'event_id'   => $eventId,
                        'jadwal_id'  => $jadwalId,
                        'name'       => $ticket['name'],
                        'price'      => $ticket['price'],
                        'stock'      => $ticket['stock'],
                        'start_sale' => $ticket['start_sale'] ?? null,
                        'end_sale'   => $ticket['end_sale'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('admin.event.index')
            ->with('success', 'Event berhasil ditambahkan');
    }

    public function edit($id)
    {
        $event = DB::table('events')->where('id', $id)->first();

        $jadwal = DB::table('jadwal')
            ->where('event_id', $id)
            ->get()
            ->map(function ($j) {
                $j->tickets = DB::table('tickets')
                    ->where('jadwal_id', $j->id)
                    ->get();
                return $j;
            });

        return response()->json([
            'event' => $event,
            'jadwal' => $jadwal
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'date'        => 'required|date',
            'location'    => 'required|string|max:255',
            'poster'      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'jadwal' => 'required|array|min:1',
            'jadwal.*.info' => 'required|string|max:255',
            'jadwal.*.tanggal' => 'required|date',

            'jadwal.*.tickets' => 'required|array|min:1',
            'jadwal.*.tickets.*.name'  => 'required|string|max:100',
            'jadwal.*.tickets.*.price' => 'required|integer',
            'jadwal.*.tickets.*.stock' => 'required|integer',
            'jadwal.*.tickets.*.start_sale' => 'nullable|date',
            'jadwal.*.tickets.*.end_sale'   => 'nullable|date',
        ]);

        DB::transaction(function () use ($request, $id) {

            $event = DB::table('events')->where('id', $id)->first();

            $posterPath = $event->poster;

            if ($request->hasFile('poster')) {

                // hapus lama
                if ($posterPath && file_exists(public_path($posterPath))) {
                    unlink(public_path($posterPath));
                }

                $filename = time() . '.' . $request->poster->getClientOriginalExtension();
                $request->poster->move(public_path('images/events'), $filename);
                $posterPath = 'images/events/' . $filename;
            }

            // update event
            DB::table('events')->where('id', $id)->update([
                'title'       => $request->title,
                'description' => $request->description,
                'date'        => $request->date,
                'location'    => $request->location,
                'poster'      => $posterPath,
                'updated_at'  => now(),
            ]);

            // hapus lama
            $jadwalIds = DB::table('jadwal')->where('event_id', $id)->pluck('id');

            DB::table('tickets')->whereIn('jadwal_id', $jadwalIds)->delete();
            DB::table('jadwal')->where('event_id', $id)->delete();

            // insert ulang
            foreach ($request->jadwal as $jadwalInput) {

                $jadwalId = DB::table('jadwal')->insertGetId([
                    'event_id'  => $id,
                    'info'      => $jadwalInput['info'],
                    'tanggal'   => $jadwalInput['tanggal'],
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);

                foreach ($jadwalInput['tickets'] as $ticket) {

                    if (!empty($ticket['start_sale']) && !empty($ticket['end_sale'])) {
                        if ($ticket['end_sale'] < $ticket['start_sale']) {
                            throw new \Exception('End sale tidak valid');
                        }
                    }

                    DB::table('tickets')->insert([
                        'event_id'   => $id,
                        'jadwal_id'  => $jadwalId,
                        'name'       => $ticket['name'],
                        'price'      => $ticket['price'],
                        'stock'      => $ticket['stock'],
                        'start_sale' => $ticket['start_sale'] ?? null,
                        'end_sale'   => $ticket['end_sale'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('admin.event.index')
            ->with('success', 'Event berhasil diperbarui');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {

            $event = DB::table('events')->where('id', $id)->first();

            if ($event->poster && file_exists(public_path($event->poster))) {
                unlink(public_path($event->poster));
            }

            $jadwalIds = DB::table('jadwal')->where('event_id', $id)->pluck('id');

            DB::table('tickets')->whereIn('jadwal_id', $jadwalIds)->delete();
            DB::table('jadwal')->where('event_id', $id)->delete();
            DB::table('events')->where('id', $id)->delete();
        });

        return redirect()->route('admin.event.index')
            ->with('success', 'Event berhasil dihapus');
    }

    public function show($id)
    {
        $event = DB::table('events')->where('id', $id)->first();

        $jadwal = DB::table('jadwal')
            ->where('event_id', $id)
            ->get()
            ->map(function ($j) {
                $j->tickets = DB::table('tickets')
                    ->where('jadwal_id', $j->id)
                    ->get();
                return $j;
            });

        return response()->json([
            'event' => $event,
            'jadwal' => $jadwal
        ]);
    }
}
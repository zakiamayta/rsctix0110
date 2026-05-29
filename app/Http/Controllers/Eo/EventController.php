<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Jadwal;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class EventController extends Controller
{
    
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (!auth('user')->check()) {
                return redirect()->route('loginuser');
            }

            if (auth('user')->user()->role !== 'eo') {
                abort(403, 'Akses hanya untuk EO');
            }

            return $next($request);
        });
    }

    /**
     * 📌 Event milik EO
     */
    public function index()
    {
        $user = auth('user')->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();

        $events = Event::where('eo_id', $eo->id)
                ->where('status', '!=', 'pending')
                ->latest()
                ->get();

        return view('eo.event-show', compact('events'));
    }

    /**
     * 📌 Form create
     */
    public function create()
    {
        return view('eo.create');
    }

    /**
     * 📌 STORE (INI YANG PENTING 🔥)
     */
public function store(Request $request)
{
    $request->validate([
        'title' => 'required',
        'date' => 'required',
        'location' => 'required',
        'poster' => 'nullable|image|max:2048',
    ]);

    $user = auth('user')->user();
    $eo = DB::table('eo')->where('user_id', $user->id)->first();

    DB::transaction(function () use ($request, $eo, $user) {

        $posterPath = null;

        if ($request->hasFile('poster')) {
            $file = $request->file('poster');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/events'), $filename);
            $posterPath = 'images/events/' . $filename;
        }

        $event = Event::create([
            'eo_id' => $eo->id,
            'title' => $request->title,
            'description' => $request->description,
            'lineup' => $request->lineup,
            'organizer' => $user->name,
            'instagram' => $request->instagram,
            'date' => $request->date,
            'ticket_sale_start' => $request->ticket_sale_start,
            'ticket_redeem_start' => $request->ticket_redeem_start,
            'min_age' => $request->min_age,
            'location' => $request->location,
            'poster' => $posterPath,
            'max_tickets_per_email' => $request->max_tickets_per_email ?? 3,
            'status' => 'pending',
        ]);

        if ($request->jadwal) {
            foreach ($request->jadwal as $jadwalData) {

                $jadwal = Jadwal::create([
                    'event_id' => $event->id,
                    'info' => $jadwalData['info'],
                    'tanggal' => $jadwalData['tanggal'],
                    'deskripsi' => $jadwalData['deskripsi'] ?? null,
                ]);

                if (isset($jadwalData['tickets'])) {
                    foreach ($jadwalData['tickets'] as $ticketData) {

                        Ticket::create([
                            'event_id' => $event->id,
                            'jadwal_id' => $jadwal->id,
                            'name' => $ticketData['name'],
                            'price' => $ticketData['price'],
                            'stock' => $ticketData['stock'],
                            'start_sale' => $ticketData['start_sale'] ?? null,
                            'end_sale' => $ticketData['end_sale'] ?? null,
                        ]);
                    }
                }
            }
        }
    });

    return redirect()
        ->route('eo.event.index')
        ->with('success', 'Event berhasil diajukan');
}

    /**
     * 📌 Edit
     */
    public function edit(Event $event)
    {
        return view('eo.edit', compact('event'));
    }

    /**
     * 📌 Update
     */
    public function update(Request $request, Event $event)
    {
        $request->validate([
            'title'     => 'required|string|max:255',
            'location'  => 'required|string|max:255',
            'date'      => 'required|date',
        ]);

        DB::transaction(function () use ($request, $event) {

            if ($request->hasFile('poster')) {

                if ($event->poster && file_exists(public_path($event->poster))) {
                    File::delete(public_path($event->poster));
                }

                $file = $request->file('poster');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/events'), $filename);

                $event->poster = 'images/events/' . $filename;
            }

            $event->update([
                'title'    => $request->title,
                'location' => $request->location,
                'date'     => $request->date,
                'status'   => 'pending', // 🔥 edit harus re-approve
            ]);

        });

        return redirect()
            ->route('eo.event.index')
            ->with('success', 'Event diperbarui & menunggu approval ulang');
    }

    /**
     * 📌 Delete
     */
    public function destroy(Event $event)
    {
        if ($event->poster && file_exists(public_path($event->poster))) {
            File::delete(public_path($event->poster));
        }

        $event->delete();

        return redirect()
            ->route('eo.event.index')
            ->with('success', 'Event berhasil dihapus');
    }

    public function show(Event $event)
    {
        $event->load([
            'jadwals.tickets'
        ]);

        return view('eo.event-detail', compact('event'));
    }

    public function editRejected(Event $event)
{
    return view('eo.event-edit-rejected', compact('event'));
}

public function resubmit(Request $request, Event $event)
{
    $request->validate([
        'title' => 'required',
        'date' => 'required',
        'location' => 'required',
        'poster' => 'nullable|image|max:2048',
    ]);

    DB::transaction(function () use ($request, $event) {

        // =========================
        // POSTER UPDATE
        // =========================
        if ($request->hasFile('poster')) {

            if ($event->poster && file_exists(public_path($event->poster))) {
                \File::delete(public_path($event->poster));
            }

            $file = $request->file('poster');
            $filename = \Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/events'), $filename);

            $event->poster = 'images/events/' . $filename;
        }

        // =========================
        // UPDATE EVENT UTAMA
        // =========================
        $event->update([
            'title' => $request->title,
            'date' => $request->date,
            'location' => $request->location,
            'description' => $request->description,
            'instagram' => $request->instagram,
            'lineup' => $request->lineup,
            'min_age' => $request->min_age,
            'max_tickets_per_email' => $request->max_tickets_per_email,
            'ticket_sale_start' => $request->ticket_sale_start,
            'ticket_redeem_start' => $request->ticket_redeem_start,

            // penting: balik ke pending lagi
            'status' => 'pending',
        ]);

        // =========================
        // RESET JADWAL & TIKET (simple approach)
        // =========================
        $event->jadwals()->delete();

        if ($request->jadwal) {
            foreach ($request->jadwal as $jadwalData) {

                $jadwal = \App\Models\Jadwal::create([
                    'event_id' => $event->id,
                    'info' => $jadwalData['info'],
                    'tanggal' => $jadwalData['tanggal'],
                    'deskripsi' => $jadwalData['deskripsi'] ?? null,
                ]);

                if (!empty($jadwalData['tickets'])) {
                    foreach ($jadwalData['tickets'] as $ticketData) {

                        \App\Models\Ticket::create([
                            'event_id' => $event->id,
                            'jadwal_id' => $jadwal->id,
                            'name' => $ticketData['name'],
                            'price' => $ticketData['price'],
                            'stock' => $ticketData['stock'],
                            'start_sale' => $ticketData['start_sale'] ?? null,
                            'end_sale' => $ticketData['end_sale'] ?? null,
                        ]);
                    }
                }
            }
        }
    });

    return redirect()
        ->route('eo.event.index')
        ->with('success', 'Event berhasil di-resubmit');
}
}
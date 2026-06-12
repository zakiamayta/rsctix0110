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

class EoEventController extends Controller
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

    $eo = DB::table('eo')
        ->where('user_id', $user->id)
        ->first();

    $events = Event::where('eo_id', $eo->id)
        ->whereIn('status', [
            'approved',
            'cancelled'
        ])
        ->latest()
        ->get();

    return view('eo.event-show', compact('events'));
}

public function status()
{
    $events = Event::whereHas('eo', function ($q) {
            $q->where('user_id', auth('user')->id());
        })
        ->whereIn('status', [
            'pending',
            'pending_cancel',
            'pending_reschedule',
            'rejected'
        ])
        ->latest()
        ->get();

    return view('eo.status', compact('events'));
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

        $this->validateEventSchedule(
            $request
        );

        $user = auth('user')->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();

//         $eventDate = \Carbon\Carbon::parse($request->date);
        

//         if ($request->ticket_sale_start) {

//     $saleStart = \Carbon\Carbon::parse(
//         $request->ticket_sale_start
//     );

//     $latestStartSale =
//         $eventDate->copy()->subDays(2);


//     if ($saleStart->gt($latestStartSale)) {

//         return back()
//             ->withInput()
//             ->withErrors([
//                 'Mulai penjualan tiket harus minimal H-2 sebelum event.'
//             ]);
//     }
// }

//         if ($request->ticket_redeem_start) {

//     $redeemDate = \Carbon\Carbon::parse(
//         $request->ticket_redeem_start
//     );

//     if (
//         $redeemDate->gt($eventDate)
//     ) {

//         return back()
//             ->withInput()
//             ->withErrors([
//                 'Penukaran tiket tidak boleh melebihi tanggal event.'
//             ]);
//     }
// }



// foreach ($request->jadwal ?? [] as $jadwal) {

//     $jadwalDate = \Carbon\Carbon::parse(
//         $jadwal['tanggal']
//     );

//     if ($jadwalDate->lt($eventDate)) {

//         return back()
//             ->withInput()
//             ->withErrors([
//                 'Tanggal jadwal tidak boleh sebelum tanggal event.'
//             ]);
//     }

//     if (
//         $jadwalDate->gt(
//             $eventDate->copy()->addDays(14)
//         )
//     ) {

//         return back()
//             ->withInput()
//             ->withErrors([
//                 'Jadwal maksimal 14 hari setelah tanggal event.'
//             ]);
//     }

//     foreach (($jadwal['tickets'] ?? []) as $ticket) {

//         if (!empty($ticket['start_sale'])) {

//             $startSale =
//                 \Carbon\Carbon::parse(
//                     $ticket['start_sale']
//                 );

//         $latestStartSale =
//             $eventDate->copy()->subDays(2);
//         if (
//             $startSale->greaterThan($latestStartSale)
//         ) {

//             return back()
//                 ->withInput()
//                 ->withErrors([
//                     'Penjualan tiket harus dimulai minimal H-2 sebelum event.'
//                 ]);
//         }
//         }

//             if (
//         !empty($ticket['start_sale']) &&
//         !empty($ticket['end_sale'])
//     ) {

//         $startSale = \Carbon\Carbon::parse(
//             $ticket['start_sale']
//         );

//         $endSale = \Carbon\Carbon::parse(
//             $ticket['end_sale']
//         );

//         if ($endSale->lt($startSale)) {

//             return back()
//                 ->withInput()
//                 ->withErrors([
//                     'Akhir penjualan tiket tidak boleh sebelum mulai penjualan.'
//                 ]);
//         }
//     }

//         if (!empty($ticket['end_sale'])) {

//             $endSale =
//                 \Carbon\Carbon::parse(
//                     $ticket['end_sale']
//                 );

//             if (
//                 $endSale->gt($eventDate)
//             ) {

//                 return back()
//                     ->withInput()
//                     ->withErrors([
//                         'Akhir penjualan tiket tidak boleh melebihi tanggal event.'
//                     ]);
//             }
//         }
//     }
// }

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
    $user = auth('user')->user();

$eo = DB::table('eo')
    ->where('user_id', $user->id)
    ->first();

if (!$eo || $event->eo_id != $eo->id) {
    abort(403);
}
    if (!$event->can_adjust_schedule) {
        abort(403, 'Hak penyesuaian jadwal sudah digunakan.');
    }

    $event->load('jadwals.tickets');

    return view('eo.event-edit', compact('event'));
}

    /**
     * 📌 Update
     */
    public function update(Request $request, Event $event)
{

    $user = auth('user')->user();

    $eo = DB::table('eo')
        ->where('user_id', $user->id)
        ->first();

    if (!$eo || $event->eo_id != $eo->id) {
        abort(403);
    }

    $request->validate([
        'title' => 'required|string|max:255',
        'date' => 'required|date',
        'location' => 'required|string|max:255',
        'poster' => 'nullable|image|max:5120',
    ]);

    $this->validateEventSchedule(
        $request
    );

    DB::transaction(function () use ($request, $event) {

        /*
        |--------------------------------------------------------------------------
        | UPDATE POSTER
        |--------------------------------------------------------------------------
        */
        if ($request->hasFile('poster')) {

            if (
                $event->poster &&
                file_exists(public_path($event->poster))
            ) {
                File::delete(public_path($event->poster));
            }

            $file = $request->file('poster');

            $filename =
                Str::uuid() . '.' .
                $file->getClientOriginalExtension();

            $file->move(
                public_path('images/events'),
                $filename
            );

            $event->poster =
                'images/events/' . $filename;
        }

  $updateData = [
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

    'poster' => $event->poster,

    // langsung aktif tanpa approval lagi
    'status' => 'approved',

    // hak edit reschedule habis setelah dipakai
    'can_adjust_schedule' => false,

    // bersihkan data request reschedule
    'proposed_date' => null,
    'owner_note' => null,
    'reschedule_reason' => null,
];

$event->update($updateData);

        /*
        |--------------------------------------------------------------------------
        | HAPUS JADWAL LAMA
        |--------------------------------------------------------------------------
        */
        Ticket::where('event_id', $event->id)->delete();
        $event->jadwals()->delete();

        /*
        |--------------------------------------------------------------------------
        | INSERT ULANG JADWAL & TIKET
        |--------------------------------------------------------------------------
        */
        if ($request->jadwal) {

            foreach ($request->jadwal as $jadwalData) {

                $jadwal = Jadwal::create([
                    'event_id' => $event->id,
                    'info' => $jadwalData['info'],
                    'tanggal' => $jadwalData['tanggal'],
                    'deskripsi'
                        => $jadwalData['deskripsi'] ?? null,
                ]);

                if (!empty($jadwalData['tickets'])) {

                    foreach (
                        $jadwalData['tickets']
                        as $ticketData
                    ) {

                        Ticket::create([
                            'event_id' => $event->id,
                            'jadwal_id' => $jadwal->id,
                            'name' => $ticketData['name'],
                            'price' => $ticketData['price'],
                            'stock' => $ticketData['stock'],
                            'start_sale'
                                => $ticketData['start_sale'] ?? null,
                            'end_sale'
                                => $ticketData['end_sale'] ?? null,
                        ]);
                    }
                }
            }
        }
    });

return redirect()
    ->route('eo.event.index')
    ->with(
        'success',
        'Jadwal event berhasil diperbarui.'
    );
}

    /**
     * 📌 Delete
     */
    public function destroy(Event $event)
    {
        $user = auth('user')->user();

$eo = DB::table('eo')
    ->where('user_id', $user->id)
    ->first();

if (!$eo || $event->eo_id != $eo->id) {
    abort(403);
}
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
        $user = auth('user')->user();

$eo = DB::table('eo')
    ->where('user_id', $user->id)
    ->first();

if (!$eo || $event->eo_id != $eo->id) {
    abort(403);
}
        $event->load([
            'jadwals.tickets'
        ]);

        return view('eo.event-detail', compact('event'));
    }

public function editRejected(Event $event)
{
    $user = auth('user')->user();

    $event->load([
        'jadwals.tickets'
    ]);

    $eo = DB::table('eo')
        ->where('user_id', $user->id)
        ->first();

    if (!$eo || $event->eo_id != $eo->id) {
        abort(403);
    }

    return view(
        'eo.event-edit-rejected',
        compact('event')
    );
}

    public function resubmit(Request $request, Event $event)
    {
        $user = auth('user')->user();

$eo = DB::table('eo')
    ->where('user_id', $user->id)
    ->first();

if (!$eo || $event->eo_id != $eo->id) {
    abort(403);
}
        $request->validate([
            'title' => 'required',
            'date' => 'required',
            'location' => 'required',
            'poster' => 'nullable|image|max:2048',
        ]);

        $this->validateEventSchedule(
            $request
        );

        DB::transaction(function () use ($request, $event) {

            // =========================
            // POSTER UPDATE
            // =========================
            $posterPath = $event->poster;

            if ($request->hasFile('poster')) {

                if ($event->poster && file_exists(public_path($event->poster))) {
                    \File::delete(public_path($event->poster));
                }

                $file = $request->file('poster');
                $filename = \Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/events'), $filename);

                // $event->poster = 'images/events/' . $filename;
                $posterPath = 'images/events/' . $filename;
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
                'poster' => $posterPath,
                // penting: balik ke pending lagi
                'status' => 'pending',
            ]);

            // =========================
            // RESET JADWAL & TIKET (simple approach)
            // =========================
            Ticket::where('event_id', $event->id)->delete();
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

    /*
    |--------------------------------------------------------------------------
    | 🆕 FITUR BARU: REQUEST CANCEL & RESCHEDULE (PERLU APPROVAL OWNER)
    |--------------------------------------------------------------------------
    */

    /**
     * 🛑 EO mengajukan pembatalan ke Owner (Cancel Request)
     */
    public function requestCancel(Event $event)
    {
        
        $user = auth('user')->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();

        // 🛡️ SECURITY CHECK: Pastikan event ini memang milik EO yang sedang login
        if ($event->eo_id !== $eo->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah event ini.');
        }

        // Hanya event aktif (approved) yang bisa diajukan pembatalan
        if ($event->status !== 'approved') {
            return back()->with('error', 'Hanya event dengan status Approved yang bisa diajukan pembatalan.');
        }

        $event->update([
            'status' => 'pending_cancel'
        ]);

        return back()->with('success', 'Pengajuan pembatalan event telah dikirim ke Owner.');
    }

    /**
     * 📅 EO mengajukan perubahan jadwal ke Owner (Reschedule Request)
     */
   
public function showRescheduleForm(Event $event)
{
    $user = auth('user')->user();

$eo = DB::table('eo')
    ->where('user_id', $user->id)
    ->first();

if (!$eo || $event->eo_id != $eo->id) {
    abort(403);
}
    $event->load(['jadwals.tickets']);

    return response()->view('eo.event-reschedule', compact('event'));
}
 public function editReschedule(Event $event)
{
    $user = auth('user')->user();

$eo = DB::table('eo')
    ->where('user_id', $user->id)
    ->first();

if (!$eo || $event->eo_id != $eo->id) {
    abort(403);
}
    return view('eo.event-reschedule', compact('event'));
}

public function requestReschedule(Request $request, Event $event)
{

    $request->validate([
        'proposed_date' => 'required|date|after:now',
        'reschedule_reason' => 'required|string|max:1000',
    ]);

    $user = auth('user')->user();

    $eo = DB::table('eo')
        ->where('user_id', $user->id)
        ->first();

    if (!$eo || $event->eo_id !== $eo->id) {
        abort(403);
    }

    if ($event->status !== 'approved') {
        return back()->with(
            'error',
            'Hanya event approved yang bisa direschedule.'
        );
    }

    $currentDate = \Carbon\Carbon::parse($event->date);
    $newDate = \Carbon\Carbon::parse($request->proposed_date);

    if ($currentDate->equalTo($newDate)) {
        return back()
            ->withInput()
            ->with(
                'error',
                'Tanggal baru harus berbeda dengan tanggal event saat ini.'
            );
    }

    $event->update([
        'status' => 'pending_reschedule',
        'proposed_date' => $request->proposed_date,
        'reschedule_reason' => $request->reschedule_reason,
    ]);

    return back()->with(
        'success',
        'Request reschedule berhasil dikirim.'
    );
}

private function validateEventSchedule(Request $request)
{
    $eventDate = \Carbon\Carbon::parse(
        $request->date
    );

    /*
    |--------------------------------------------------------------------------
    | EVENT
    |--------------------------------------------------------------------------
    */

    if ($request->ticket_sale_start) {

        $saleStart = \Carbon\Carbon::parse(
            $request->ticket_sale_start
        );

        $latestStartSale =
            $eventDate->copy()->subDays(2);

        if ($saleStart->gt($latestStartSale)) {

            return back()
                ->withInput()
                ->withErrors([
                    'Mulai penjualan tiket harus minimal H-2 sebelum event.'
                ])->throwResponse();
        }
    }

    if ($request->ticket_redeem_start) {

        $redeemDate = \Carbon\Carbon::parse(
            $request->ticket_redeem_start
        );

        if ($redeemDate->gt($eventDate)) {

            return back()
                ->withInput()
                ->withErrors([
                    'Penukaran tiket tidak boleh melebihi tanggal event.'
                ])->throwResponse();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | JADWAL
    |--------------------------------------------------------------------------
    */

    foreach ($request->jadwal ?? [] as $jadwal) {

        $jadwalDate = \Carbon\Carbon::parse(
            $jadwal['tanggal']
        );

        if ($jadwalDate->lt($eventDate)) {

            return back()
                ->withInput()
                ->withErrors([
                    'Tanggal jadwal tidak boleh sebelum tanggal event.'
                ])->throwResponse();
        }

        if (
            $jadwalDate->gt(
                $eventDate->copy()->addDays(14)
            )
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'Jadwal maksimal 14 hari setelah tanggal event.'
                ])->throwResponse();
        }

        foreach (
            ($jadwal['tickets'] ?? [])
            as $ticket
        ) {

            /*
            |--------------------------------------------------------------------------
            | START SALE
            |--------------------------------------------------------------------------
            */

            if (!empty($ticket['start_sale'])) {

                $startSale =
                    \Carbon\Carbon::parse(
                        $ticket['start_sale']
                    );

                $latestStartSale =
                    $eventDate->copy()->subDays(2);

                if (
                    $startSale->gt(
                        $latestStartSale
                    )
                ) {

                    return back()
                        ->withInput()
                        ->withErrors([
                            'Penjualan tiket harus dimulai minimal H-2 sebelum event.'
                        ])->throwResponse();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | END SALE >= START SALE
            |--------------------------------------------------------------------------
            */

            if (
                !empty($ticket['start_sale']) &&
                !empty($ticket['end_sale'])
            ) {

                $startSale =
                    \Carbon\Carbon::parse(
                        $ticket['start_sale']
                    );

                $endSale =
                    \Carbon\Carbon::parse(
                        $ticket['end_sale']
                    );

                if ($endSale->lt($startSale)) {

                    return back()
                        ->withInput()
                        ->withErrors([
                            'Akhir penjualan tiket tidak boleh sebelum mulai penjualan.'
                        ])->throwResponse();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | END SALE <= EVENT DATE
            |--------------------------------------------------------------------------
            */

            if (!empty($ticket['end_sale'])) {

                $endSale =
                    \Carbon\Carbon::parse(
                        $ticket['end_sale']
                    );

                if (
                    $endSale->gt($eventDate)
                ) {

                    return back()
                        ->withInput()
                        ->withErrors([
                            'Akhir penjualan tiket tidak boleh melebihi tanggal event.'
                        ])->throwResponse();
                }
            }
        }
    }
}


}

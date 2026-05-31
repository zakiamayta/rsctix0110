<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;

class DetailEventController extends Controller
{
    public function show($id)
    {
        $event = Event::with(['jadwals', 'tickets'])
            ->where('id', $id)
            ->where('status', 'approved')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $event->id,
                'title' => $event->title,
                'event_url' => $event->event_url,
                'description' => $event->description,
                'lineup' => $event->lineup,
                'organizer' => $event->organizer,
                'instagram' => $event->instagram,
                'date' => $event->date,
                'ticket_sale_start' => $event->ticket_sale_start,
                'ticket_redeem_start' => $event->ticket_redeem_start,
                'min_age' => $event->min_age,
                'location' => $event->location,
                'poster' => $event->poster
                        ? asset($event->poster)
                        : null,

                'max_tickets_per_email' => $event->max_tickets_per_email,
                'status' => $event->status,

                /// ================= JADWAL =================
                'jadwal' => $event->jadwals->map(function ($j) {
                    return [
                        'id' => $j->id,
                        'info' => $j->info, // contoh: Day 1
                        'tanggal' => $j->tanggal,
                        'deskripsi' => $j->deskripsi,
                    ];
                })->values(),

                /// ================= TICKETS =================
                'tickets' => $event->tickets->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'name' => $t->name,
                        'price' => $t->price,
                        'stock' => $t->stock,
                        'jadwal_id' => $t->jadwal_id,
                        'start_sale' => $t->start_sale,
                        'end_sale' => $t->end_sale,
                    ];
                })->values(),

                /// 🔥 BONUS: GROUP TICKETS PER JADWAL (BIAR ENAK DI FLUTTER)
                'tickets_grouped' => $event->jadwals->map(function ($j) use ($event) {
                    return [
                        'jadwal_id' => $j->id,
                        'info' => $j->info,
                        'tickets' => $event->tickets
                            ->where('jadwal_id', $j->id)
                            ->map(function ($t) {
                                return [
                                    'id' => $t->id,
                                    'name' => $t->name,
                                    'price' => $t->price,
                                    'stock' => $t->stock,
                                ];
                            })
                            ->values()
                    ];
                })->values(),
            ]
        ]);
    }
}
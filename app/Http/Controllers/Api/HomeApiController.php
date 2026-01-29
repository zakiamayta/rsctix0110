<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Product;

class HomeApiController extends Controller
{
    public function index()
    {
        $events = Event::orderBy('date')->get()->map(function ($event) {
            return [
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
                'max_tickets_per_email' => $event->max_tickets_per_email,
                'status' => $event->status,

                // 🔥 INI KUNCI MASALAH GAMBAR
                'poster' => $event->poster
                    ? asset($event->poster)
                    : null,
            ];
        });

        return response()->json([
            'status' => true,
            'events' => $events,
            'tickets' => Product::where('type', 'ticket')->get(),
            'merchandise' => Product::where('type', 'merch')->get(),
        ]);
    }
}

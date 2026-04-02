<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;

class DetailEventController extends Controller
{
    public function show($id)
    {
        $event = Event::where('status', 'approved')
            ->findOrFail($id);

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
                'poster' => $event->poster,
                'max_tickets_per_email' => $event->max_tickets_per_email,
                'status' => $event->status,
            ]
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;

class EventController extends Controller
{
    /**
     * GET /api/events
     * List semua event (untuk Flutter)
     */
    public function index()
    {
        $events = Event::select(
                'id',
                'title',
                'description',
                'organizer',
                'instagram',
                'date',
                'location',
                'poster'
            )
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * GET /api/events/{id}
     * Detail event
     */
    public function show($id)
    {
        $event = Event::with([
                'products',
                'varians',
                'ukurans',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }
}

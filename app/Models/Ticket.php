<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Event;
use App\Models\Ticket;

class Ticket extends Model
{
    public function index()
    {
        $events = Event::all();

        foreach ($events as $event) {
            $event->lowest_price = Ticket::where('event_id', $event->id)->min('price');
        }

        return view('home', compact('events'));
    }
}

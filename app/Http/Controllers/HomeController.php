<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $events = Event::orderBy('date')->get();
        $tickets = Product::where('type', 'ticket')->get();
        $merchandise = Product::where('type', 'merch')->get();

        return view('home', compact('events', 'tickets', 'merchandise'));
    }
}




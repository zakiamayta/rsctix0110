<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        // ✅ HANYA tampilkan event yang sudah di-approve
        $events = Event::where('status', 'approved')
            ->orderBy('date')
            ->get();

        $tickets = Product::where('type', 'ticket')->get();
        $merchandise = Product::where('type', 'merch')->get();

        return view('home', compact('events', 'tickets', 'merchandise'));
    }
}
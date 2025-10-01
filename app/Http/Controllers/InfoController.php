<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InfoController extends Controller
{
    public function show($id)
    {
        // Ambil event
        $event = DB::table('events')->find($id);
        if (!$event) {
            abort(404, 'Event tidak ditemukan.');
        }

        // Ambil semua tiket untuk event ini
        $tickets = DB::table('tickets')->where('event_id', $id)->get();

        // Hitung harga minimum & maksimum
        $minPrice = $tickets->min('price');
        $maxPrice = $tickets->max('price');

        return view('info.info', compact('event', 'tickets', 'minPrice', 'maxPrice'));
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InfoController extends Controller
{
public function show($id)
{
    $event = DB::table('events')->find($id);
    if (!$event) {
        abort(404, 'Event tidak ditemukan.');
    }

    // Ambil tiket
    $tickets = DB::table('tickets')->where('event_id', $id)->get();

    // Ambil jadwal
    $jadwals = DB::table('jadwal')
        ->where('event_id', $id)
        ->orderBy('tanggal', 'asc')
        ->get();

    $minPrice = $tickets->min('price');
    $maxPrice = $tickets->max('price');

    return view('info.info', compact(
        'event',
        'tickets',
        'jadwals',
        'minPrice',
        'maxPrice'
    ));
}
}

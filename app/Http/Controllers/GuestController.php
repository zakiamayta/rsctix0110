<?php

namespace App\Http\Controllers;

use App\Models\Transaction; // gunakan model yang benar
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // tambahkan ini

class GuestController extends Controller
{
    // Tampilkan QR di browser
public function showQR($kode)
{
    $guest = Transaction::where('kode_unik', $kode)->firstOrFail();
    return view('admin.guest-qr', compact('guest'));
}

public function exportGuestQR($kode)
{
    $guest = Transaction::with(['event', 'attendees'])
        ->where('kode_unik', $kode)
        ->firstOrFail();

    $event = $guest->event;

    $pdf = Pdf::loadView('admin.export-qr', compact('guest', 'event'));
    return $pdf->download('guest_qr_' . $guest->kode_unik . '.pdf');
}




}

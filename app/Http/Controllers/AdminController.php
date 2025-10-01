<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketAttendee;
class AdminController extends Controller
{
    public function absenManual($id)
    {
        $attendee = TicketAttendee::find($id);

        if (! $attendee) {
            return redirect()->back()->with('error', 'Peserta tidak ditemukan.');
        }

        if (! $attendee->transaction) {
            return redirect()->back()->with('error', 'Transaksi peserta tidak ditemukan.');
        }

        // Tandai peserta sudah absen lewat transaksi
        $attendee->transaction->is_registered = true;
        $attendee->transaction->registered_at = now();
        $attendee->transaction->save();

        return redirect()->back()->with('success', 'Absensi manual berhasil dilakukan.');
    }

    public function batalAbsen($id)
    {
        $attendee = TicketAttendee::find($id);

        if (! $attendee) {
            return redirect()->back()->with('error', 'Peserta tidak ditemukan.');
        }

        if (! $attendee->transaction) {
            return redirect()->back()->with('error', 'Transaksi peserta tidak ditemukan.');
        }

        // Batalkan status absen lewat transaksi
        $attendee->transaction->is_registered = false;
        $attendee->transaction->registered_at = null;
        $attendee->transaction->save();

        return redirect()->back()->with('success', 'Status absensi berhasil dibatalkan.');
    }

}

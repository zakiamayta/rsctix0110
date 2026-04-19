<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function myTickets()
    {
        $user = Auth::guard('user')->user();

        $transactions = DB::table('transactions')
            ->where('email', $user->email)
            ->orderByDesc('checkout_time')
            ->get();

        // ambil detail tiap transaksi
        foreach ($transactions as $trx) {
            $trx->details = DB::table('ticket_attendees')
                ->join('tickets', 'ticket_attendees.ticket_id', '=', 'tickets.id')
                ->leftJoin('jadwal', 'tickets.jadwal_id', '=', 'jadwal.id')
                ->leftJoin('events', 'tickets.event_id', '=', 'events.id')
                ->where('ticket_attendees.transaction_id', $trx->id)
                ->select(
                    'ticket_attendees.name',
                    'ticket_attendees.phone_number',
                    'tickets.name as ticket_name',
                    'tickets.price',
                    'events.title as event_title',
                    'events.date as event_date',
                    'jadwal.tanggal as jadwal_tanggal',
                    'jadwal.info as jadwal_info'
                )
                ->get();
        }

        return view('user.riwayat-pembelian', compact('transactions'));
    }
}
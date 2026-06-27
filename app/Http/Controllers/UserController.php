<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionMerch;

class UserController extends Controller
{
    public function myTickets()
    {
        $user = Auth::user(); 

        if (!$user) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Mengambil semua jenis transaksi (termasuk paid, unpaid, dan refunded)
        $transactions = DB::table('transactions')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->leftJoin('refunds', 'transactions.id', '=', 'refunds.transaction_id')
            ->where('transactions.email', $user->email)
            ->select(
                'transactions.*',
                'transactions.id as id', // Memastikan ID transaksi tidak tertimpa ID refund
                'events.status as event_status',
                'events.is_rescheduled as event_is_rescheduled',
                'refunds.status as refund_status'
            )
            ->orderByDesc('transactions.checkout_time')
            ->get();

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

        return view('user.riwayat-tiket', compact('transactions'));
    }

    public function myMerch()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $transactions = TransactionMerch::with([
            'details.product',
            'details.varian',
            'details.ukuran'
        ])
        ->where('email', $user->email)
        ->orderByDesc('created_at')
        ->get();

        return view('user.riwayat-merch', compact('transactions'));
    }
}
<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use App\Models\Eo;
use App\Models\Withdrawal;

class TicketHistoryController extends Controller
{
    public function index()
    {
        $eo = Eo::where(
            'user_id',
            auth()->id()
        )->firstOrFail();

        $withdrawals = Withdrawal::with([
            'event'
        ])
        ->where(
            'eo_id',
            $eo->id
        )
        ->latest()
        ->paginate(20);

        return view(
            'eo.wallet.ticket.history',
            compact('withdrawals')
        );
    }

    public function show($id)
    {
        $eo = Eo::where(
            'user_id',
            auth()->id()
        )->firstOrFail();

        $withdrawal = Withdrawal::with([
            'event',
            'eo'
        ])
        ->where(
            'eo_id',
            $eo->id
        )
        ->findOrFail($id);

        return view(
            'eo.wallet.ticket.detail',
            compact('withdrawal')
        );
    }
}
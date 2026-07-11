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
    $eo = Eo::where('user_id', auth()->id())->firstOrFail();

    $withdrawal = Withdrawal::with(['event', 'eo'])
        ->where('eo_id', $eo->id)
        ->findOrFail($id);

    // Tempelkan data rekening dari relasi 'eo' langsung ke root object 'withdrawal'
    $withdrawal->bank_name = $withdrawal->eo->bank_name ?? null;
    $withdrawal->account_number = $withdrawal->eo->account_number ?? null;
    $withdrawal->account_name = $withdrawal->eo->account_name ?? null;

    return view('eo.wallet.ticket.detail', compact('withdrawal'));
}
}
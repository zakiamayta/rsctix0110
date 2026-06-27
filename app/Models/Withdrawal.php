<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $table = 'withdrawals';

    protected $fillable = [
        'eo_id',
        'event_id',
        'amount',
        'note',
        'owner_note',
        'invoice_file',
        'transfer_proof',
        'status',
        'approved_at',
        'paid_at',
    ];

    public function eo()
    {
        return $this->belongsTo(
            Eo::class,
            'eo_id'
        );
    }

    public function event()
    {
        return $this->belongsTo(
            Event::class,
            'event_id'
        );
    }
}
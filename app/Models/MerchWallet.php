<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchWallet extends Model
{
    protected $table = 'merch_wallets';

    protected $fillable = [
        'eo_id',
        'event_id',

        'available_balance',
        'held_balance',
        'negative_balance',

        'withdraw_locked',
    ];

    protected $casts = [
        'withdraw_locked' => 'boolean',
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

    public function withdrawals()
    {
        return $this->hasMany(
            Withdrawal::class,
            'event_id',
            'event_id'
        );
    }
}
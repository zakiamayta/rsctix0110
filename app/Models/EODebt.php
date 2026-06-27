<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EODebt extends Model
{
    protected $table = 'eo_debts';

    protected $fillable = [
        'eo_id',
        'event_id',
        'total_debt',
        'remaining_debt',
        'status',
    ];

    /**
     * Relasi Balik ke EO
     */
    public function eo(): BelongsTo
    {
        return $this->belongsTo(EO::class, 'eo_id');
    }

    /**
     * Relasi Balik ke Event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
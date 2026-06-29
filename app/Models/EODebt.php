<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EoDebt extends Model
{
    protected $table = 'eo_debts';

    protected $fillable = [
        'eo_id',
        'event_id',
        'total_debt',
        'remaining_debt',
        'status'
    ];

    /**
     * Hubungan ke data profil Event Organizer (EO)
     */
    public function eo()
    {
        return $this->belongsTo(Eo::class, 'eo_id');
    }

    /**
     * Hubungan ke data Event terkait yang minus saldonya
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
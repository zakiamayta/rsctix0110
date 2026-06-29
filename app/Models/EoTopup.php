<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EoTopup extends Model
{
    protected $table = 'eo_topups';

    protected $fillable = [
        'eo_id',
        'refund_id',
        'amount_requested',
        'amount_paid',
        'proof_of_transfer',
        'status',
        'admin_note'
    ];

    /**
     * Hubungan ke data profil Event Organizer (EO) yang melakukan topup
     */
    public function eo()
    {
        return $this->belongsTo(Eo::class, 'eo_id');
    }

    /**
     * Hubungan ke data refund spesifik (jika topup dipicu otomatis oleh system refund)
     */
    public function refund()
    {
        return $this->belongsTo(Refund::class, 'refund_id');
    }
}
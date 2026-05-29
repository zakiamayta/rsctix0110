<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $table = 'withdrawals';

    protected $fillable = [
        'eo_id',
        'amount',
        'bank_name',
        'account_name',
        'account_number',
        'note',
        'transfer_proof',
        'status',
        'approved_at',
    ];

    public function eo()
    {
        return $this->belongsTo(Eo::class);
    }
    
}
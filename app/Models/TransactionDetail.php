<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $fillable = [
        'transaction_id', 'nik', 'name', 'phone_number'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}

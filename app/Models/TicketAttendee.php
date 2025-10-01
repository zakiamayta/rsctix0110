<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAttendee extends Model
{
    protected $table = 'ticket_attendees';

    protected $fillable = [
        'transaction_id',
        'name',
        'phone_number',
        'ticket_id',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAttendee extends Model
{
    protected $table = 'ticket_attendees';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'name',
        'phone_number',
        'email',
        'kode_unik',
        'qr_code',
        'is_registered',
        'registered_at',
        'ticket_id',
        'jadwal_id',
    ];

    protected $casts = [
        'is_registered' => 'boolean',
        'registered_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id', 'id');
    }
}
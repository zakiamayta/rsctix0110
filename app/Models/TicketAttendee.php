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
        'ticket_id',
        'jadwal_id', // ✅ Ditambahkan sesuai database
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    // ✅ Ditambahkan relasi ke Jadwal sesuai database
    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id', 'id');
    }
}
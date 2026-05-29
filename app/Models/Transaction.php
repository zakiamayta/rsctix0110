<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',

        // pembayaran
        'total_amount',
        'service_tax',
        'grand_total',

        'payment_method',
        'status',
        'payment_status',

        // user
        'email',

        // waktu
        'checkout_time',
        'paid_time',

        // xendit
        'xendit_invoice_id',
        'xendit_invoice_url',

        // qr
        'qr_code',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function attendees()
    {
        return $this->hasMany(TicketAttendee::class, 'transaction_id');
    }

    public function jadwal()
    {
        return $this->belongsTo(\App\Models\Jadwal::class, 'jadwal_id');
    }

    public $timestamps = true;
}
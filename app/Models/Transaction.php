<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions'; // Definisikan secara eksplisit

    protected $fillable = [
        'kode_unik',         // ✅ Ditambahkan sesuai database
        'event_id',          // ✅ Ditambahkan sesuai database
        'jadwal_id',         // ✅ Ditambahkan sesuai database
        'payment_method',
        'payment_status',    // Di database namanya payment_status (bukan status)
        'email',
        'checkout_time',
        'paid_time',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'qr_code',
        'total_amount',
        'service_tax',
        'grand_total',
        'is_registered',     // ✅ Ditambahkan sesuai database
        'registered_at',     // ✅ Ditambahkan sesuai database
    ];

    public $timestamps = true;

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id', 'id');
    }

    public function attendees()
    {
        return $this->hasMany(TicketAttendee::class, 'transaction_id', 'id');
    }
}
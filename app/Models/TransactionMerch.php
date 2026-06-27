<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionMerch extends Model
{
    use HasFactory;

    protected $table = 'transaction_merch';

    protected $fillable = [
        'event_id', // ✅ Wajib ditambahkan karena ada FK fk_transaction_merch_event
        'kode_unik',
        'total_amount',
        'service_tax',
        'grand_total',
        'payment_status',
        'email',
        'checkout_time',
        'paid_time',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'qr_code',
    ];

    // ✅ Ditambahkan relasi ke Event
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function details()
    {
        return $this->hasMany(TransactionMerchDetail::class, 'transaction_merch_id', 'id');
    }
}
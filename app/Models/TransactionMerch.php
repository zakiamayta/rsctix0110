<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionMerch extends Model
{
    use HasFactory;

    protected $table = 'transaction_merch'; // nama tabel persis

    protected $fillable = [
        'product_id',
        'total_price',
        'kode_unik',
        'payment_status',
        'email',
        'checkout_time',
        'paid_time',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'qr_code',
        'total_amount',
    ];

        public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // 🔹 relasi ke detail transaksi
    public function details()
    {
        return $this->hasMany(TransactionMerchDetail::class, 'transaction_merch_id');
    }
}

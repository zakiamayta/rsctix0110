<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionMerch extends Model
{
    use HasFactory;

    protected $table = 'transaction_merch';

    protected $fillable = [

        // subtotal barang
        'total_amount',

        // biaya layanan
        'service_tax',

        // total akhir pembayaran
        'grand_total',

        'kode_unik',
        'payment_status',

        'email',

        'checkout_time',
        'paid_time',

        'xendit_invoice_id',
        'xendit_invoice_url',

        'qr_code',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    public function details()
    {
        return $this->hasMany(
            TransactionMerchDetail::class,
            'transaction_merch_id'
        );
    }
}
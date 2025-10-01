<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionMerchDetail extends Model
{
    use HasFactory;

    protected $table = 'transaction_merch_details';

    protected $fillable = [
        'transaction_merch_id',
        'buyer_name',
        'buyer_phone',
        'product_id',
        'varian_id',
        'ukuran_id',
        'quantity',
        'price',
        'subtotal',
    ];

    public function transaction()
    {
        return $this->belongsTo(TransactionMerch::class, 'transaction_merch_id');
    }

    public function varian()
    {
        return $this->belongsTo(ProductVarian::class, 'varian_id');
    }

    public function ukuran()
    {
        return $this->belongsTo(ProductUkuran::class, 'ukuran_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
};


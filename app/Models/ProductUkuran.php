<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUkuran extends Model
{
    protected $table = 'products_ukuran'; // sesuai tabel di DB

    protected $fillable = [
        'event_id',
        'ukuran',
        'harga',
        'stok',
        'varian_id',
    ];

    public function varian()
    {
        return $this->belongsTo(ProductVarian::class, 'varian_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'ukuran_id');
    }
}





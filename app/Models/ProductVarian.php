<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVarian extends Model
{
    protected $table = 'products_varian'; // sesuai tabel di DB

    protected $fillable = [
        'event_id',
        'varian',
        'image',
        'product_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    // relasi baru: varian punya banyak ukuran
    public function ukurans()
    {
        return $this->hasMany(ProductUkuran::class, 'varian_id');
    }
}


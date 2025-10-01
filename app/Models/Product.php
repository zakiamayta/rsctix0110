<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products'; // sesuai tabel di DB

    protected $fillable = [
        'type',
        'name',
        'description',
        'event_id',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function varians()
    {
        return $this->hasMany(ProductVarian::class, 'product_id');
    }

    // ⚠ ukurans tidak langsung ke product, tapi lewat varians
    // kalau mau akses semua ukuran dari produk
    public function ukurans()
    {
        return $this->hasManyThrough(
            ProductUkuran::class,   // model tujuan
            ProductVarian::class,   // model perantara
            'product_id',           // FK di product_varians
            'varian_id',            // FK di product_ukurans
            'id',                   // PK di products
            'id'                    // PK di product_varians
        );
    }
}
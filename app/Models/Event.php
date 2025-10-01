<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'title',
        'description',
        'date',
        'location',
        'poster',
        'max_tickets_per_email',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'event_id');
    }

    public function varians()
    {
        return $this->hasMany(ProductVarian::class, 'event_id');
    }

    public function ukurans()
    {
        return $this->hasMany(ProductUkuran::class, 'event_id');
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

}

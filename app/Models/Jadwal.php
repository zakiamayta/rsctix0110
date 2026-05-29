<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $table = 'jadwal'; // ⛗ PENTING (karena tabel kamu bukan plural)
    protected $fillable = [
    'event_id',
    'info',
    'tanggal',
];

public function event()
{
    return $this->belongsTo(Event::class, 'event_id');
}

public function tickets()
{
    return $this->hasMany(Ticket::class, 'jadwal_id');
}
}
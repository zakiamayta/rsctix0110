<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Eo extends Model
{
    protected $table = 'eo';

    protected $fillable = [
        'user_id',
        'status',

        'nama_badan_usaha',
        'alamat_badan_usaha',
        'dokumen_badan_usaha',

        'penanggung_jawab',
        'ktp_penanggung_jawab',

        'bank_name',
        'account_name',
        'account_number',

        'logo',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION USER
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | RELATION EVENTS
    |--------------------------------------------------------------------------
    */

    public function events()
    {
        return $this->hasMany(\App\Models\Event::class);
    }
}
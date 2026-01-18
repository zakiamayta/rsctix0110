<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id';
    public $timestamps = false;

    /**
     * Kolom yang boleh diisi (WAJIB)
     */
    protected $fillable = [
        'email',
        'google_id',
        'name',
        'phone',
        'birth_date',
        'gender',
        'avatar',
        'profile_complete',
        'PASSWORD',
        'created_at',
    ];

    /**
     * Kolom sensitif
     */
    protected $hidden = [
        'PASSWORD',
    ];

    /**
     * Supaya auth admin tetap pakai PASSWORD (uppercase)
     */
    public function getAuthPassword()
    {
        return $this->PASSWORD;
    }
}

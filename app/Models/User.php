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

    protected $fillable = ['email', 'PASSWORD', 'created_at'];

    protected $hidden = ['PASSWORD'];

    // Override getAuthPassword untuk pakai kolom PASSWORD (uppercase)
    public function getAuthPassword()
    {
        return $this->PASSWORD;
    }
}
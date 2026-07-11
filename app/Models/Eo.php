<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Eo extends Model
{
    protected $table = 'eo';

    protected $fillable = [
        'user_id',
        'status',
        'rejected_reason',
        'nama_badan_usaha',
        'alamat_badan_usaha',
        'dokumen_badan_usaha',
        'penanggung_jawab',
        'ktp_penanggung_jawab',
        'bank_name',
        'account_name',
        'account_number',
        'balance',    // 👈 Ditambahkan agar bisa di-update via Eloquent
        'total_debt', // 👈 Ditambahkan agar bisa di-update via Eloquent
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
        return $this->hasMany(\App\Models\Event::class, 'eo_id');
    }

    public function withdrawals()
    {
        return $this->hasMany(
            Withdrawal::class,
            'eo_id'
        );
    }

    public function merchWithdrawals()
    {
        return $this->hasMany(
            MerchWithdrawal::class,
            'eo_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 🆕 NEW REFUND & DEBT RELATIONS
    |--------------------------------------------------------------------------
    */

    public function refundBatches()
    {
        return $this->hasMany(RefundBatch::class, 'eo_id');
    }

    public function debts()
    {
        return $this->hasMany(EODebt::class, 'eo_id');
    }
}
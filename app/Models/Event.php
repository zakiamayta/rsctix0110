<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'eo_id',
        'title',
        'event_url',
        'description',
        'lineup',
        'organizer',
        'instagram',
        'date',
        'ticket_sale_start',
        'ticket_redeem_start',
        'min_age',
        'location',
        'poster',
        'max_tickets_per_email',
        'status',
        'eo_note',
        'proposed_date',
        'is_rescheduled',
        'reschedule_reason',
        'reschedule_rejected_reason',
        'rejected_reason',
        'can_adjust_schedule',
        'owner_note',
        'merch_cancel_decision'
    ];

    protected $casts = [
        'date' => 'datetime',
        'ticket_sale_start' => 'datetime',
        'ticket_redeem_start' => 'datetime',
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
        return $this->hasMany(Ticket::class, 'event_id');
    }
    
    public function jadwals()
    {
        return $this->hasMany(\App\Models\Jadwal::class, 'event_id');
    }

    public function eo()
    {
        return $this->belongsTo(\App\Models\Eo::class, 'eo_id');
    }

    public function wallet()
    {
        return $this->hasOne(
            EventWallet::class,
            'event_id'
        );
    }

    public function merchWallet()
    {
        return $this->hasOne(
            MerchWallet::class,
            'event_id'
        );
    }

    public function withdrawals()
    {
        return $this->hasMany(
            Withdrawal::class,
            'event_id'
        );
    }

    public function merchWithdrawals()
    {
        return $this->hasMany(
            MerchWithdrawal::class,
            'event_id'
        );
    }

    public function eventWallet()
    {
        return $this->hasOne(\App\Models\EventWallet::class, 'event_id');
    }

    /*
    |--------------------------------------------------------------------------
    | 🆕 NEW REFUND & DEBT RELATIONS
    |--------------------------------------------------------------------------
    */

    public function refundBatches()
    {
        return $this->hasMany(RefundBatch::class, 'event_id');
    }

    public function debts()
    {
        return $this->hasMany(EODebt::class, 'event_id');
    }
}
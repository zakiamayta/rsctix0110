<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefundBatch extends Model
{
    protected $table = 'refund_batches';

    protected $fillable = [
        'eo_id',
        'event_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'status', // Status Baru yang divalidasi: 'open', 'closed', 'completed'
    ];

    /**
     * Relasi Balik ke EO
     */
    public function eo(): BelongsTo
    {
        return $this->belongsTo(EO::class, 'eo_id');
    }

    /**
     * Relasi Balik ke Event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Relasi ke daftar Refund Pembeli yang ada di dalam Batch ini
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'refund_batch_id');
    }
}
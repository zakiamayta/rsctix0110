<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $table = 'refunds';

    protected $fillable = [
        'transaction_id',
        'refund_batch_id',
        'bank_name',
        'account_number',
        'account_name',
        'refund_reason',
        'grand_total_refunded',
        'refunds_tax', // Diperbarui dari platform_service_tax_share sesuai database baru
        'status',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * Relasi Balik ke Transaksi Tiket Pembeli
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Relasi Balik ke Batch Refund Terkait (Mendukung Nullable untuk status 'waiting')
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(RefundBatch::class, 'refund_batch_id');
    }
}
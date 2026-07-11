<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    protected $table = 'transactions'; // Definisikan secara eksplisit

    protected $fillable = [
        'kode_unik',         // ✅ Ditambahkan sesuai database
        'event_id',          // ✅ Ditambahkan sesuai database
        'jadwal_id',         // ✅ Ditambahkan sesuai database
        'payment_method',
        'payment_status',    // Di database namanya payment_status (bukan status)
        'email',
        'checkout_time',
        'paid_time',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'qr_code',
        'total_amount',
        'service_tax',
        'grand_total',
        // 'is_registered',     // ✅ Ditambahkan sesuai database
        // 'registered_at',     // ✅ Ditambahkan sesuai database
    ];

    public $timestamps = true;

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id', 'id');
    }

    public function attendees()
    {
        return $this->hasMany(TicketAttendee::class, 'transaction_id', 'id');
    }

    /**
     * Lepaskan kembali stok tiket untuk transaksi yang GAGAL/KEDALUWARSA dibayar.
     *
     * Mengembalikan stok sesuai jumlah peserta lalu menghapus transaksi & pesertanya,
     * mengikuti pola yang sama dengan TicketController::cancel(). Karena kolom
     * payment_status berupa enum('unpaid','paid','refunded'), status "expired" tidak
     * bisa disimpan, sehingga record dihapus (bukan ditandai).
     *
     * Aman dipanggil berkali-kali (idempotent) dan tahan balapan (race-safe): baris
     * dikunci lalu dipastikan masih 'unpaid' sebelum diproses, jadi webhook PAID,
     * webhook EXPIRED, dan sweep terjadwal tidak akan mengembalikan stok dua kali.
     *
     * @return bool true jika stok benar-benar dilepas pada pemanggilan ini.
     */
    public function releaseExpiredStock(): bool
    {
        return DB::transaction(function () {
            $trx = static::where('id', $this->id)
                ->where('payment_status', 'unpaid')
                ->lockForUpdate()
                ->first();

            // Sudah dibayar, atau sudah dilepas oleh proses lain → tidak melakukan apa-apa.
            if (!$trx) {
                return false;
            }

            // Kembalikan stok: 1 peserta = 1 tiket (sesuai cara store() memotong stok).
            $attendees = DB::table('ticket_attendees')
                ->where('transaction_id', $trx->id)
                ->get();

            foreach ($attendees as $attendee) {
                DB::table('tickets')
                    ->where('id', $attendee->ticket_id)
                    ->increment('stock', 1);
            }

            DB::table('ticket_attendees')->where('transaction_id', $trx->id)->delete();
            DB::table('transactions')->where('id', $trx->id)->delete();

            return true;
        });
    }
}
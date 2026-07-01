<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReleaseExpiredTransactions extends Command
{
    /**
     * Contoh:
     *   php artisan tickets:release-expired
     *   php artisan tickets:release-expired --minutes=60
     */
    protected $signature = 'tickets:release-expired
        {--minutes=30 : Usia minimal (menit) sejak checkout sebelum transaksi unpaid dianggap kedaluwarsa}';

    protected $description = 'Jaring pengaman: kembalikan stok tiket dari transaksi unpaid yang sudah kedaluwarsa (jika webhook EXPIRED Xendit terlewat).';

    public function handle(): int
    {
        $minutes   = max(1, (int) $this->option('minutes'));
        $threshold = Carbon::now()->subMinutes($minutes);

        // Durasi invoice Xendit hanya 15 menit, jadi unpaid yang lebih tua dari ambang ini
        // dipastikan tidak akan dibayar lagi. Hanya proses baris yang benar-benar lahir dari
        // alur checkout (punya invoice id) agar data hasil seeding manual tidak ikut tersentuh.
        $staleQuery = Transaction::where('payment_status', 'unpaid')
            ->whereNotNull('xendit_invoice_id')
            ->where('checkout_time', '<', $threshold);

        $total = (clone $staleQuery)->count();

        if ($total === 0) {
            $this->info("Tidak ada transaksi unpaid kedaluwarsa (> {$minutes} menit).");
            return self::SUCCESS;
        }

        $this->info("Menemukan {$total} transaksi unpaid kedaluwarsa. Memproses...");

        $released = 0;

        // Proses per baris (chunk) agar tabel besar tidak dimuat sekaligus ke memori,
        // dan tiap pelepasan berjalan di transaksinya sendiri (gagal satu tidak membatalkan semua).
        $staleQuery->orderBy('id')->chunkById(200, function ($transactions) use (&$released) {
            foreach ($transactions as $trx) {
                if ($trx->releaseExpiredStock()) {
                    $released++;
                }
            }
        });

        $this->info("Selesai. {$released} transaksi dilepas & stok tiketnya dikembalikan.");

        return self::SUCCESS;
    }
}

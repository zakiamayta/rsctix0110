<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('wallet:resync')->everyFiveMinutes();

// Jaring pengaman refund: rekonsiliasi refund yang masih "processing" langsung dari Xendit,
// kalau-kalau webhook payout tidak terkirim (tunnel/server down, retry Xendit habis).
// withoutOverlapping mencegah dua proses berjalan bersamaan bila satu jalan kelamaan.
Schedule::command('refunds:sync-pending')->everyTenMinutes()->withoutOverlapping();

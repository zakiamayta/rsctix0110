<?php

namespace App\Console\Commands;

use App\Services\MerchWalletService;
use App\Services\TicketWalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResyncWallets extends Command
{
    protected $signature = 'wallet:resync';

    protected $description = 'Jaring pengaman: sinkronkan ulang saldo event_wallets & merch_wallets untuk semua event aktif/cancelled';

    public function handle()
    {
        $eventIds = DB::table('events')
            ->whereIn('status', ['approved', 'cancelled'])
            ->pluck('id');

        $count = 0;
        foreach ($eventIds as $id) {
            TicketWalletService::recalculate($id);
            MerchWalletService::recalculate($id);
            $count++;
        }

        $this->info("Resync selesai untuk {$count} event.");
    }
}
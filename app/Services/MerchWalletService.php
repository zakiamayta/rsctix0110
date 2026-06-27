<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MerchWalletService
{
    public function getWallets(int $eoId): array
    {
        $eo = DB::table('eo')
            ->where('id', $eoId)
            ->first();

        if (!$eo) {
            return [
                'summary' => [
                    'total_sales' => 0,
                    'total_available_balance' => 0,
                    'total_held_balance' => 0,
                    'total_withdrawn' => 0,
                ],
                'events' => [],
            ];
        }

        $events = DB::table('events')
            ->where('eo_id', $eoId)
            ->orderByDesc('date')
            ->get();

        $wallets = [];

        $totalSales = 0;
        $totalAvailable = 0;
        $totalHeld = 0;
        $totalWithdrawn = 0;

        foreach ($events as $event) {

            /*
            |--------------------------------------------------------------------------
            | TOTAL PAID
            |--------------------------------------------------------------------------
            */

            $paidTotal = DB::table('merch_orders')
                ->where('event_id', $event->id)
                ->where('payment_status', 'paid')
                ->sum('total_price');

            /*
            |--------------------------------------------------------------------------
            | POTENSI REVENUE
            |--------------------------------------------------------------------------
            */

            $potentialRevenue = DB::table('products_ukuran')
                ->join(
                    'products_varian',
                    'products_varian.id',
                    '=',
                    'products_ukuran.varian_id'
                )
                ->join(
                    'products',
                    'products.id',
                    '=',
                    'products_varian.product_id'
                )
                ->where('products.event_id', $event->id)
                ->selectRaw('SUM(products_ukuran.stok * products_ukuran.harga) as total')
                ->value('total') ?? 0;

            /*
            |--------------------------------------------------------------------------
            | WITHDRAW APPROVED
            |--------------------------------------------------------------------------
            */

            $alreadyWithdrawn = DB::table('merch_withdrawals')
                ->where('event_id', $event->id)
                ->where('status', 'approved')
                ->sum('amount');

            /*
            |--------------------------------------------------------------------------
            | EVENT STATUS
            |--------------------------------------------------------------------------
            */

            $startDate = Carbon::parse($event->date);

            $endDate = !empty($event->end_date)
                ? Carbon::parse($event->end_date)
                : $startDate;

            $isEventFinished = $endDate->isPast();

            $isHMinus10 =
                now()->diffInDays($startDate, false) <= 10
                && now()->isBefore($startDate);

            /*
            |--------------------------------------------------------------------------
            | MINIMUM HELD BALANCE
            |--------------------------------------------------------------------------
            */

            $minHeldBalance =
                $potentialRevenue * 0.20;

            /*
            |--------------------------------------------------------------------------
            | WITHDRAW LIMIT
            |--------------------------------------------------------------------------
            */

            if ($isEventFinished) {

                $plafonPercent = 1.0;

            } elseif ($isHMinus10) {

                $plafonPercent = 0.7;

            } else {

                $plafonPercent = 0.5;
            }

            $maxWithdrawable =
                max(
                    0,
                    ($paidTotal * $plafonPercent)
                    - $alreadyWithdrawn
                );

            /*
            |--------------------------------------------------------------------------
            | AVAILABLE BALANCE
            |--------------------------------------------------------------------------
            */

            $availableBalance =
                min(
                    $maxWithdrawable,
                    max(
                        0,
                        ($paidTotal - $alreadyWithdrawn)
                        - $minHeldBalance
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | HELD BALANCE
            |--------------------------------------------------------------------------
            */

            $heldBalance =
                ($paidTotal - $alreadyWithdrawn)
                - $availableBalance;

            $heldBalance =
                max(0, $heldBalance);

            /*
            |--------------------------------------------------------------------------
            | SYSTEM REASON
            |--------------------------------------------------------------------------
            */

            $canWithdraw = true;
            $systemReason = null;

            if ($paidTotal <= 0) {

                $canWithdraw = false;
                $systemReason =
                    'Belum ada penjualan merchandise yang dibayar.';

            } elseif (
                ($paidTotal - $alreadyWithdrawn)
                < $minHeldBalance
                && !$isEventFinished
            ) {

                $canWithdraw = false;
                $systemReason =
                    'Saldo masih berada di bawah batas saldo mengendap 20%.';

            } elseif ($availableBalance <= 0) {

                $canWithdraw = false;
                $systemReason =
                    'Tidak ada saldo yang dapat ditarik.';
            }

            $wallets[] = [

                'event_id' => $event->id,

                'event_name' => $event->nama_event,

                'event_date' => $event->date,

                'total_sales' => $paidTotal,

                'potential_revenue' => $potentialRevenue,

                'available_balance' => $availableBalance,

                'held_balance' => $heldBalance,

                'withdrawn_balance' => $alreadyWithdrawn,

                'can_withdraw' => $canWithdraw,

                'system_reason' => $systemReason,

                'bank_name' => $eo->bank_name,

                'account_name' => $eo->account_name,

                'account_number' => $eo->account_number,
            ];

            $totalSales += $paidTotal;
            $totalAvailable += $availableBalance;
            $totalHeld += $heldBalance;
            $totalWithdrawn += $alreadyWithdrawn;
        }

        return [
            'summary' => [
                'total_sales' => $totalSales,
                'total_available_balance' => $totalAvailable,
                'total_held_balance' => $totalHeld,
                'total_withdrawn' => $totalWithdrawn,
            ],

            'events' => $wallets,
        ];
    }
}
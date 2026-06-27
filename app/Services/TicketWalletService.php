<?php

namespace App\Services;

use App\Models\EventWallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TicketWalletService
{
    public function getWallets(int $eoId): array
    {
        $events = DB::table('events')
            ->leftJoin(
                'event_wallets',
                'events.id',
                '=',
                'event_wallets.event_id'
            )
            ->join(
                'eo',
                'events.eo_id',
                '=',
                'eo.id'
            )
            ->leftJoin(
                'jadwal',
                'events.id',
                '=',
                'jadwal.event_id'
            )
            ->where('events.eo_id', $eoId)
            ->select(
                'events.id as event_id',
                'events.title',
                'events.poster',
                'events.date as start_date',
                DB::raw('MAX(jadwal.tanggal) as end_date'),
                'events.status as event_status',

                'event_wallets.id as wallet_id',
                'event_wallets.negative_balance',
                'event_wallets.withdraw_locked',

                'eo.bank_name',
                'eo.account_name',
                'eo.account_number'
            )
            ->groupBy(
                'events.id',
                'events.title',
                'events.poster',
                'events.date',
                'events.status',

                'event_wallets.id',
                'event_wallets.negative_balance',
                'event_wallets.withdraw_locked',

                'eo.bank_name',
                'eo.account_name',
                'eo.account_number'
            )
            ->orderByDesc('events.id')
            ->get();

        $result = [];

        $summaryTotalSales = 0;
        $summaryAvailable = 0;
        $summaryHeld = 0;
        $summaryWithdrawn = 0;

        foreach ($events as $event) {

            $actualEndDate =
                $event->end_date
                ?? $event->start_date;

            if (is_null($event->wallet_id)) {

                $wallet = EventWallet::create([
                    'eo_id' => $eoId,
                    'event_id' => $event->event_id,

                    'available_balance' => 0,
                    'held_balance' => 0,
                    'negative_balance' => 0,

                    'withdraw_locked' => false,
                ]);

                $event->wallet_id = $wallet->id;
                $event->withdraw_locked = 0;
                $event->negative_balance = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | Total Penjualan Paid
            |--------------------------------------------------------------------------
            */

            $paidTotal = DB::table('transactions')
                ->where(
                    'event_id',
                    $event->event_id
                )
                ->where(
                    'payment_status',
                    'paid'
                )
                ->sum('total_amount') ?? 0;

            /*
            |--------------------------------------------------------------------------
            | Approved + Pending
            |--------------------------------------------------------------------------
            */

            $alreadyWithdrawn = DB::table('withdrawals')
                ->where(
                    'event_id',
                    $event->event_id
                )
                ->whereIn(
                    'status',
                    [
                        'approved',
                        'pending'
                    ]
                )
                ->sum('amount') ?? 0;

            /*
            |--------------------------------------------------------------------------
            | Potensi Omset
            |--------------------------------------------------------------------------
            */

            try {

                $potentialRevenue = DB::table('tickets')
                    ->where(
                        'event_id',
                        $event->event_id
                    )
                    ->select(
                        DB::raw(
                            'SUM(stock * price) as total_potential_revenue'
                        )
                    )
                    ->value(
                        'total_potential_revenue'
                    ) ?? 0;

            } catch (\Exception $e) {

                $potentialRevenue = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | Skala Event
            |--------------------------------------------------------------------------
            */

            $isSkalaBesar =
                $potentialRevenue >= 50000000;

            $minBalanceRequired =
                $isSkalaBesar
                ? 3000000
                : 1000000;

            $minHeldBalance =
                $isSkalaBesar
                ? 500000
                : 100000;

            /*
            |--------------------------------------------------------------------------
            | H-10 & Event Finished
            |--------------------------------------------------------------------------
            */

            $isEventFinished = false;
            $isHMinus10 = false;

            if (!is_null($event->start_date)) {

                $today =
                    now()->startOfDay();

                $startDate =
                    Carbon::parse(
                        $event->start_date
                    )->startOfDay();

                $daysLeft =
                    $today->diffInDays(
                        $startDate
                    );

                $isHMinus10 =
                    ($daysLeft <= 10)
                    && $today->isBefore($startDate);
            }

            if (!is_null($actualEndDate)) {

                $isEventFinished =
                    Carbon::parse(
                        $actualEndDate
                    )->isPast();
            }

            /*
            |--------------------------------------------------------------------------
            | Plafon
            |--------------------------------------------------------------------------
            */

            if ($isEventFinished) {

                $plafonPercent = 1.0;

            } else {

                $plafonPercent = 0.5;
            }

            $isBypassedByHMinus10 = false;

            if (
                $isHMinus10
                && $paidTotal < $minBalanceRequired
            ) {

                $minBalanceRequired = 0;

                $isBypassedByHMinus10 = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Perhitungan Saldo
            |--------------------------------------------------------------------------
            */

            $maxEligibleBalance =
                floor(
                    $paidTotal
                    * $plafonPercent
                );

            $calculatedAvailable =
                $maxEligibleBalance
                - $alreadyWithdrawn;

            if ($calculatedAvailable < 0) {
                $calculatedAvailable = 0;
            }

            $sisaKasSistem =
                $paidTotal
                - $alreadyWithdrawn;

            $heldBalance =
                $sisaKasSistem
                - $calculatedAvailable;

            if ($heldBalance < 0) {
                $heldBalance = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | Validasi Withdrawal
            |--------------------------------------------------------------------------
            */

            $canWithdraw = true;

            $systemReason =
                'Silakan masukkan nominal pengajuan Anda.';

            if ($event->withdraw_locked == 1) {

                $canWithdraw = false;

                $calculatedAvailable = 0;

                $heldBalance =
                    $paidTotal
                    - $alreadyWithdrawn;

                $systemReason =
                    'Fitur penarikan dana dinonaktifkan sementara oleh admin.';
            }

            elseif (
                $paidTotal
                < $minBalanceRequired
            ) {

                $canWithdraw = false;

                $calculatedAvailable = 0;

                $heldBalance =
                    $paidTotal
                    - $alreadyWithdrawn;

                $systemReason =
                    'Total omset belum mencapai batas minimal pembuka gerbang '
                    . $this->formatRupiah(
                        $minBalanceRequired
                    );
            }

            elseif (
                ($paidTotal - $alreadyWithdrawn)
                < $minHeldBalance
                && !$isEventFinished
            ) {

                $canWithdraw = false;

                $calculatedAvailable = 0;

                $heldBalance =
                    $paidTotal
                    - $alreadyWithdrawn;

                $systemReason =
                    'Sisa saldo berjalan di bawah target mengendap '
                    . $this->formatRupiah(
                        $minHeldBalance
                    );
            }

            elseif (
                $calculatedAvailable <= 0
            ) {

                $canWithdraw = false;

                $calculatedAvailable = 0;

                if (
                    $isBypassedByHMinus10
                ) {

                    $systemReason =
                        'Gerbang H-10 terbuka otomatis! Namun saldo hak tarik Anda masih 0 karena limit plafon 50% sudah habis atau belum ada tiket laku baru.';
                } else {

                    $systemReason =
                        'Kuota limit penarikan termin berjalan (Plafon 50%) Anda saat ini sudah diambil.';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Sync Wallet DB
            |--------------------------------------------------------------------------
            */

            EventWallet::where(
                'id',
                $event->wallet_id
            )->update([
                'available_balance' =>
                    (int) $calculatedAvailable,

                'held_balance' =>
                    (int) $heldBalance,
            ]);

            $statusBypassMsg =
                $isBypassedByHMinus10
                ? ' (Bypass H-10 Aktif)'
                : '';

            $result[] = [

                'event_id' =>
                    (int) $event->event_id,

                'event_name' =>
                    $event->title,

                'poster' =>
                    $event->poster,

                'start_date' =>
                    $event->start_date,

                'end_date' =>
                    $actualEndDate,

                'status' =>
                    $event->event_status,

                'is_event_finished' =>
                    $isEventFinished,

                'is_h_minus_10' =>
                    $isHMinus10,

                'skala_event' =>
                    $isSkalaBesar
                    ? 'Besar (Potensi Capai '
                        . $this->formatRupiah(
                            $potentialRevenue
                        )
                        . ')'
                    : 'Kecil (Potensi '
                        . $this->formatRupiah(
                            $potentialRevenue
                        )
                        . ')',

                'total_sales' =>
                    (int) $paidTotal,

                'already_withdrawn' =>
                    (int) $alreadyWithdrawn,

                'available_balance' =>
                    (int) $calculatedAvailable,

                'held_balance_ui' =>
                    (int) $heldBalance,

                'held_balance' =>
                    (int) $heldBalance,

                'negative_balance' =>
                    (int) $event->negative_balance,

                'withdraw_locked' =>
                    (int) $event->withdraw_locked,

                'can_withdraw' =>
                    $canWithdraw,

                'system_reason' =>
                    $systemReason
                    . $statusBypassMsg,

                'min_balance_required' =>
                    (int) $minBalanceRequired,

                'max_amount_allowed' =>
                    (int) $calculatedAvailable,

                'bank_name' =>
                    $event->bank_name ?? '-',

                'account_name' =>
                    $event->account_name ?? '-',

                'account_number' =>
                    $event->account_number ?? '-',
            ];

            $summaryTotalSales += $paidTotal;
            $summaryAvailable += $calculatedAvailable;
            $summaryHeld += $heldBalance;
            $summaryWithdrawn += $alreadyWithdrawn;
        }

        return [
            'summary' => [
                'total_sales' => $summaryTotalSales,
                'total_available_balance' => $summaryAvailable,
                'total_held_balance' => $summaryHeld,
                'total_withdrawn' => $summaryWithdrawn,
            ],

            'events' => $result,
        ];
    }

    private function formatRupiah($angka): string
    {
        return 'Rp ' .
            number_format(
                $angka,
                0,
                ',',
                '.'
            );
    }
}
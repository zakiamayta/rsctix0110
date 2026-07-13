<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TicketWalletService
{
    /**
     * Hitung ulang & sinkronkan saldo wallet TIKET untuk satu event.
     * Panggil ini di SETIAP titik yang mengubah payment_status transaksi,
     * status withdrawal, atau status event — bukan hanya saat dashboard EO dibuka.
     */
    public static function recalculate(int $eventId): array
    {
        $event = DB::table('events')->where('id', $eventId)->first();

        if (!$event) {
            return self::emptyResult('Event tidak ditemukan.');
        }

        $wallet = DB::table('event_wallets')->where('event_id', $eventId)->first();

        if (!$wallet) {
            $walletId = DB::table('event_wallets')->insertGetId([
                'eo_id'             => $event->eo_id,
                'event_id'          => $eventId,
                'available_balance' => 0,
                'held_balance'      => 0,
                'negative_balance'  => 0,
                'withdraw_locked'   => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            $wallet = DB::table('event_wallets')->where('id', $walletId)->first();
        }

        $actualEndDate = DB::table('jadwal')->where('event_id', $eventId)->max('tanggal') ?? $event->date;

        $paidTotal = DB::table('transactions')
            ->where('event_id', $eventId)
            ->where('payment_status', 'paid')
            ->sum('total_amount') ?? 0;

        $alreadyWithdrawn = DB::table('withdrawals')
            ->where('event_id', $eventId)
            ->whereIn('status', ['approved', 'pending'])
            ->sum('amount') ?? 0;

        $potentialRevenue = DB::table('tickets')
            ->where('event_id', $eventId)
            ->select(DB::raw('SUM(stock * price) as total_potential_revenue'))
            ->value('total_potential_revenue') ?? 0;

        $isSkalaBesar = $potentialRevenue >= 50000000;
        $minBalanceRequired = $isSkalaBesar ? 3000000 : 1000000;
        $minHeldBalance = $isSkalaBesar ? 500000 : 100000;

        $isEventFinished = false;
        $isHMinus10 = false;

        if (!is_null($event->date)) {
            $today = now()->startOfDay();
            $startDate = Carbon::parse($event->date)->startOfDay();
            $daysLeft = $today->diffInDays($startDate);
            $isHMinus10 = ($daysLeft <= 10) && $today->isBefore($startDate);
        }

        if (!is_null($actualEndDate)) {
            $isEventFinished = Carbon::parse($actualEndDate)->isPast();
        }

        $plafonPercent = $isEventFinished ? 1.0 : 0.5;

        $isBypassedByHMinus10 = false;
        if ($isHMinus10 && $paidTotal < $minBalanceRequired) {
            $minBalanceRequired = 0;
            $minHeldBalance = 0;
            $isBypassedByHMinus10 = true;
        }

        $maxEligibleBalance = floor($paidTotal * $plafonPercent);
        $calculatedAvailable = $maxEligibleBalance - $alreadyWithdrawn;
        if ($calculatedAvailable < 0) $calculatedAvailable = 0;

        $sisaKasSistem = $paidTotal - $alreadyWithdrawn;
        $heldBalance = $sisaKasSistem - $calculatedAvailable;
        if ($heldBalance < 0) $heldBalance = 0;

        $canWithdraw = true;
        $systemReason = 'Silakan masukkan nominal pengajuan Anda.';

        if ($event->status === 'cancelled') {
            $canWithdraw = false;
            $calculatedAvailable = 0;
            $heldBalance = $sisaKasSistem;
            $systemReason = 'Event dibatalkan. Dana dibekukan untuk dialokasikan ke sirkuit refund.';
        } else {
            if ($wallet->withdraw_locked == 1) {
                $canWithdraw = false;
                $calculatedAvailable = 0;
                $heldBalance = $paidTotal - $alreadyWithdrawn;
                $systemReason = 'Fitur penarikan dana dinonaktifkan sementara oleh admin.';
            } elseif ($paidTotal < $minBalanceRequired && !$isEventFinished) {
                $canWithdraw = false;
                $calculatedAvailable = 0;
                $heldBalance = $paidTotal - $alreadyWithdrawn;
                $systemReason = 'Total omset belum mencapai batas minimal pembuka gerbang Rp '
                    . number_format($minBalanceRequired, 0, ',', '.');
            } elseif (($paidTotal - $alreadyWithdrawn) < $minHeldBalance && !$isEventFinished) {
                $canWithdraw = false;
                $calculatedAvailable = 0;
                $heldBalance = $paidTotal - $alreadyWithdrawn;
                $systemReason = 'Sisa saldo berjalan di bawah target mengendap Rp '
                    . number_format($minHeldBalance, 0, ',', '.');
            } elseif ($calculatedAvailable <= 0) {
                $canWithdraw = false;
                $calculatedAvailable = 0;
                $systemReason = $isBypassedByHMinus10
                    ? 'Gerbang H-10 terbuka otomatis! Namun saldo hak tarik Anda masih 0 karena limit plafon 50% sudah habis.'
                    : 'Kuota limit penarikan termin berjalan Anda saat ini sudah diambil.';
            }
        }

        DB::table('event_wallets')->where('event_id', $eventId)->update([
            'available_balance' => (int) $calculatedAvailable,
            'held_balance'      => (int) $heldBalance,
            'updated_at'        => now(),
        ]);

        return [
            'available_balance' => (int) $calculatedAvailable,
            'held_balance'      => (int) $heldBalance,
            'total_sales'       => (int) $paidTotal,
            'already_withdrawn' => (int) $alreadyWithdrawn,
            'can_withdraw'      => $canWithdraw,
            'system_reason'     => $systemReason . ($isBypassedByHMinus10 ? ' (Bypass H-10 Aktif)' : ''),
            'is_event_finished' => $isEventFinished,
            'is_h_minus_10'     => $isHMinus10,
            'is_bypassed_h10'   => $isBypassedByHMinus10,
            'skala_event'       => $isSkalaBesar ? 'Besar' : 'Kecil',
            'negative_balance'  => $wallet->negative_balance,
            'withdraw_locked'   => $wallet->withdraw_locked,
        ];
    }

    /** Resync semua event milik satu EO sekaligus (dipakai di dashboard index()) */
    public static function recalculateForEo(int $eoId): void
    {
        $eventIds = DB::table('events')->where('eo_id', $eoId)->pluck('id');
        foreach ($eventIds as $eventId) {
            self::recalculate($eventId);
        }
    }

    private static function emptyResult(string $reason): array
    {
        return [
            'available_balance' => 0,
            'held_balance'      => 0,
            'total_sales'       => 0,
            'already_withdrawn' => 0,
            'can_withdraw'      => false,
            'system_reason'     => $reason,
            'is_event_finished' => false,
            'is_h_minus_10'     => false,
            'is_bypassed_h10'   => false,
            'skala_event'       => 'Kecil',
            'negative_balance'  => 0,
            'withdraw_locked'   => 0,
        ];
    }
}
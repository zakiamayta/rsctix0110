<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MerchWalletService
{
    /**
     * Hitung ulang & sinkronkan saldo wallet MERCH untuk satu event.
     * Panggil ini di SETIAP titik yang mengubah payment_status transaksi_merch,
     * status merch_withdrawals, atau saldo minus refund merch.
     */
    public static function recalculate(int $eventId): array
    {
        $event = DB::table('events')->where('id', $eventId)->first();

        if (!$event) {
            return self::emptyResult('Event tidak ditemukan.');
        }

        $wallet = DB::table('merch_wallets')->where('event_id', $eventId)->first();

        if (!$wallet) {
            $walletId = DB::table('merch_wallets')->insertGetId([
                'eo_id'             => $event->eo_id,
                'event_id'          => $eventId,
                'available_balance' => 0,
                'held_balance'      => 0,
                'negative_balance'  => 0,
                'withdraw_locked'   => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            $wallet = DB::table('merch_wallets')->where('id', $walletId)->first();
        }

        $paidTotal = DB::table('transaction_merch_details as tmd')
            ->join('transaction_merch as tm', 'tmd.transaction_merch_id', '=', 'tm.id')
            ->join('products as p', 'tmd.product_id', '=', 'p.id')
            ->where('p.event_id', $eventId)
            ->where('tm.payment_status', 'paid')
            ->sum('tmd.subtotal') ?? 0;

        $alreadyWithdrawn = DB::table('merch_withdrawals')
            ->where('event_id', $eventId)
            ->whereIn('status', ['approved', 'pending'])
            ->sum('amount') ?? 0;

        $potentialRevenue = DB::table('products_ukuran')
            ->where('event_id', $eventId)
            ->select(DB::raw('SUM(stok * harga) as total_potential'))
            ->value('total_potential') ?? 0;

        $isSkalaBesar = $potentialRevenue >= 25000000;
        $minBalanceRequired = $isSkalaBesar ? 500000 : 100000;
        $minHeldBalance = $isSkalaBesar ? 250000 : 50000;

        $isHMinus10 = false;
        if (!is_null($event->date)) {
            $startDate = Carbon::parse($event->date)->startOfDay();
            $today = now()->startOfDay();
            $isHMinus10 = $today->diffInDays($startDate, false) <= 10;
        }

        $isBypassedByHMinus10 = false;
        if ($isHMinus10) {
            $minBalanceRequired = 0;
            $minHeldBalance = 0;
            $isBypassedByHMinus10 = true;
        }

        $plafonPercent = $isHMinus10 ? 0.7 : 0.5;

        $maxEligibleBalance = floor($paidTotal * $plafonPercent);
        $calculatedAvailable = $maxEligibleBalance - $alreadyWithdrawn;
        if ($calculatedAvailable < 0) $calculatedAvailable = 0;

        $sisaKasSistem = $paidTotal - $alreadyWithdrawn;
        $heldBalance = $sisaKasSistem - $calculatedAvailable;
        if ($heldBalance < 0) $heldBalance = 0;

        $canWithdraw = true;
        $systemReason = 'Silakan masukkan nominal pengajuan Anda.';

        if ($wallet->withdraw_locked == 1) {
            $canWithdraw = false;
            $calculatedAvailable = 0;
            $heldBalance = $paidTotal - $alreadyWithdrawn;
            $systemReason = 'Fitur penarikan dana dinonaktifkan sementara oleh admin.';
        } elseif ($paidTotal < $minBalanceRequired) {
            $canWithdraw = false;
            $calculatedAvailable = 0;
            $heldBalance = $paidTotal - $alreadyWithdrawn;
            $systemReason = 'Total omset belum mencapai batas minimal Rp '
                . number_format($minBalanceRequired, 0, ',', '.');
        } elseif (($paidTotal - $alreadyWithdrawn) < $minHeldBalance) {
            $canWithdraw = false;
            $calculatedAvailable = 0;
            $heldBalance = $paidTotal - $alreadyWithdrawn;
            $systemReason = 'Sisa saldo berjalan di bawah target mengendap Rp '
                . number_format($minHeldBalance, 0, ',', '.');
        } elseif ($calculatedAvailable <= 0) {
            $canWithdraw = false;
            $calculatedAvailable = 0;
            $systemReason = $isBypassedByHMinus10
                ? 'Gerbang H-10 terbuka otomatis! Namun saldo hak tarik Anda masih 0 karena limit plafon 70% sudah diambil atau belum ada merch laku baru.'
                : 'Kuota limit penarikan termin berjalan (Plafon 50%) Anda saat ini sudah diambil.';
        }

        DB::table('merch_wallets')->where('event_id', $eventId)->update([
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
            'is_h_minus_10'     => false,
            'is_bypassed_h10'   => false,
            'skala_event'       => 'Kecil',
            'negative_balance'  => 0,
            'withdraw_locked'   => 0,
        ];
    }
}
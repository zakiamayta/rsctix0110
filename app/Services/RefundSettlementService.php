<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\EODebt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Eksekusi finansial saat sebuah payout refund SUKSES.
 *
 * Sengaja dipisah dari WebhookController agar bisa dipakai oleh DUA pemicu:
 *   1) Webhook payout.succeeded dari Xendit (jalur normal), dan
 *   2) Sinkronisasi manual admin (fallback saat webhook tidak diterima).
 *
 * Logika pemotongan saldo, pencatatan utang EO, dan biaya platform HARUS identik
 * di kedua jalur, jadi tinggal satu sumber kebenaran di sini.
 */
class RefundSettlementService
{
    /**
     * @return string 'settled' | 'already' | 'not_found'
     * @throws \Throwable  bila terjadi kegagalan di tengah proses (dilempar agar
     *                     pemanggil bisa menentukan respons; transaksi sudah di-rollback).
     */
    public static function settleSuccessfulPayout(int $refundId): string
    {
        DB::beginTransaction();
        try {
            // 🔒 Idempoten: lock row & pastikan masih 'processing' sebelum potong saldo
            $refund = Refund::where('id', $refundId)->lockForUpdate()->first();

            if (!$refund) {
                DB::commit();
                Log::warning('Settle payout dibatalkan: refund tidak ditemukan.', ['refund_id' => $refundId]);
                return 'not_found';
            }

            if ($refund->status !== 'processing') {
                DB::commit();
                Log::info('Settle payout diabaikan: status bukan processing (kemungkinan duplikat/telat).', [
                    'refund_id' => $refund->id,
                    'status'    => $refund->status,
                ]);
                return 'already';
            }

            $batch = $refund->refundBatch;
            $isTicket = is_null($refund->transaction_merch_id);

            $relation = $isTicket
                ? DB::table('transactions')->where('id', $refund->transaction_id)->first()
                : DB::table('transaction_merch')->where('id', $refund->transaction_merch_id)->first();

            if (!$relation) {
                // Transaksi sumber hilang — tak bisa menentukan event/nominal. Jangan diam-diam.
                throw new \RuntimeException("Transaksi sumber refund #{$refund->id} tidak ditemukan.");
            }

            $pureAmountToBuyer = $relation->total_amount;
            $eventId = $relation->event_id;
            $eoId = $batch->eo_id;
            $walletTable = $isTicket ? 'event_wallets' : 'merch_wallets';

            // Pastikan angka fresh sebelum dipotong
            if ($isTicket) {
                TicketWalletService::recalculate($eventId);
            } else {
                MerchWalletService::recalculate($eventId);
            }

            // 🔒 Kunci baris wallet agar dua penyelesaian paralel tidak salah hitung utang.
            $wallet = DB::table($walletTable)->where('event_id', $eventId)->lockForUpdate()->first();
            $sumberSaldoUang = $wallet ? ($wallet->available_balance + $wallet->held_balance) : 0;

            if ($sumberSaldoUang < $pureAmountToBuyer) {
                $kekuranganDana = $pureAmountToBuyer - $sumberSaldoUang;

                DB::table($walletTable)->where('event_id', $eventId)->update([
                    'available_balance' => 0,
                    'held_balance'      => 0,
                ]);
                DB::table($walletTable)->where('event_id', $eventId)->increment('negative_balance', $kekuranganDana);
                DB::table($walletTable)->where('event_id', $eventId)->update(['withdraw_locked' => 1]);

                EODebt::create([
                    'eo_id'          => $eoId,
                    'event_id'       => $eventId,
                    'type'           => $isTicket ? 'ticket' : 'merch',
                    'total_debt'     => $kekuranganDana,
                    'remaining_debt' => $kekuranganDana,
                    'status'         => 'unpaid',
                ]);

                DB::table('eo')->where('id', $eoId)->increment('total_debt', $kekuranganDana);
            }

            // Potong wallet platform utk biaya transfer per-item
            DB::table('platform_wallets')->updateOrInsert(['id' => 1], []);
            DB::table('platform_wallets')->where('id', 1)->update([
                'total_refund_fees_spent' => DB::raw("total_refund_fees_spent + {$refund->refunds_tax}"),
                'current_balance'         => DB::raw("current_balance - {$refund->refunds_tax}"),
                'updated_at'              => now(),
            ]);

            $refund->update([
                'grand_total_refunded'  => $pureAmountToBuyer,
                'status'                => 'refunded',
                'xendit_payout_status'  => 'SUCCEEDED',
                'processed_at'          => now(),
            ]);

            $targetTable = $isTicket ? 'transactions' : 'transaction_merch';
            DB::table($targetTable)->where('id', $relation->id)->update([
                'payment_status' => 'refunded',
                'updated_at'     => now(),
            ]);

            DB::commit();

            if ($isTicket) {
                TicketWalletService::recalculate($eventId);
            } else {
                MerchWalletService::recalculate($eventId);
            }

            Log::info('✅ Payout sukses diproses & saldo dipotong.', [
                'refund_id' => $refund->id,
                'event_id'  => $eventId,
                'amount'    => $pureAmountToBuyer,
            ]);

            return 'settled';
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Gagal memproses penyelesaian payout sukses: ' . $e->getMessage(), [
                'refund_id' => $refundId,
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}

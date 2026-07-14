<?php

namespace App\Console\Commands;

use App\Models\Refund;
use App\Services\XenditPayoutService;
use App\Services\RefundSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SyncPendingRefunds extends Command
{
    /**
     * --grace : hanya rekonsiliasi refund yang sudah dikirim > N menit lalu, supaya
     *           tidak balapan dengan webhook untuk payout yang baru saja dikirim.
     * --limit : batas jumlah per-jalan agar tidak menghantam API Xendit sekaligus.
     */
    protected $signature = 'refunds:sync-pending {--grace=15 : Menit minimal sejak dikirim ke Xendit} {--limit=50 : Maksimal refund yang diproses per jalan}';

    protected $description = 'Jaring pengaman: tarik status payout dari Xendit untuk refund yang masih "processing" dan selesaikan bila sudah SUCCEEDED/FAILED (fallback bila webhook tidak diterima).';

    public function handle(XenditPayoutService $payoutService): int
    {
        $graceMinutes = (int) $this->option('grace');
        $limit        = (int) $this->option('limit');
        $threshold    = Carbon::now()->subMinutes($graceMinutes);

        // Hanya refund yang benar-benar sudah dikirim ke Xendit & sudah lewat masa tenggang webhook.
        $refunds = Refund::where('status', 'processing')
            ->whereNotNull('sent_to_xendit_at')
            ->where('sent_to_xendit_at', '<=', $threshold)
            ->where(function ($q) {
                $q->whereNotNull('xendit_payout_id')->orWhereNotNull('xendit_reference_id');
            })
            ->orderBy('sent_to_xendit_at')
            ->limit($limit)
            ->get();

        if ($refunds->isEmpty()) {
            $this->info('Tidak ada refund "processing" yang perlu disinkronkan (grace ' . $graceMinutes . ' menit).');
            return self::SUCCESS;
        }

        $this->info('Menyinkronkan ' . $refunds->count() . ' refund yang masih processing...');
        Log::info('🔄 [scheduler] refunds:sync-pending mulai', ['jumlah' => $refunds->count(), 'grace_menit' => $graceMinutes]);

        $settled = 0;
        $failed  = 0;
        $pending = 0;
        $errors  = 0;

        foreach ($refunds as $refund) {
            try {
                $result = $payoutService->fetchPayoutStatus($refund);

                if (!$result['success']) {
                    $errors++;
                    $this->warn("  #{$refund->id}: gagal cek status — {$result['message']}");
                    continue;
                }

                $status = $result['status'];

                if ($status === 'SUCCEEDED') {
                    $r = RefundSettlementService::settleSuccessfulPayout($refund->id);
                    if ($r === 'settled') {
                        $settled++;
                        $this->line("  #{$refund->id}: ✅ SUCCEEDED → diselesaikan & saldo dipotong.");
                    } else {
                        // 'already' atau 'not_found' — tidak dihitung sebagai settle baru.
                        $this->line("  #{$refund->id}: SUCCEEDED tetapi sudah diproses sebelumnya ({$r}).");
                    }
                } elseif ($status === 'FAILED') {
                    Refund::where('id', $refund->id)->where('status', 'processing')->update([
                        'status'               => 'failed',
                        'xendit_payout_status' => 'FAILED',
                        'failure_code'         => $result['raw']['failure_code'] ?? 'UNKNOWN',
                        'failure_message'      => $result['raw']['failure_code'] ?? 'Payout gagal (hasil sinkronisasi terjadwal).',
                        'updated_at'           => now(),
                    ]);
                    $failed++;
                    $this->line("  #{$refund->id}: ❌ FAILED → ditandai gagal (perlu retry/reject).");
                } else {
                    // ACCEPTED / REQUESTED / PENDING — belum tuntas, biarkan.
                    Refund::where('id', $refund->id)->update(['xendit_payout_status' => $status]);
                    $pending++;
                    $this->line("  #{$refund->id}: ⏳ masih {$status}, dilewati.");
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::error('❌ [scheduler] Gagal sinkron refund #' . $refund->id . ': ' . $e->getMessage(), [
                    'refund_id' => $refund->id,
                    'trace'     => $e->getTraceAsString(),
                ]);
                $this->error("  #{$refund->id}: error — {$e->getMessage()}");
            }
        }

        $summary = "Selesai. Diselesaikan: {$settled}, Gagal: {$failed}, Masih pending: {$pending}, Error: {$errors}.";
        $this->info($summary);
        Log::info('🔄 [scheduler] refunds:sync-pending selesai', [
            'settled' => $settled, 'failed' => $failed, 'pending' => $pending, 'errors' => $errors,
        ]);

        return self::SUCCESS;
    }
}

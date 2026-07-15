<?php

namespace App\Services;

use App\Models\Refund;
use App\Support\XenditBankList;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XenditPayoutService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.xendit.co/v2/payouts';

    public function __construct()
    {
        // Pakai config() (bukan env() langsung) supaya tetap terbaca setelah
        // `php artisan config:cache` di produksi.
        $this->apiKey = (string) config('services.xendit.api_key');
    }

    /**
     * 🚀 Kirim satu refund sebagai Payout ke Xendit.
     * Tidak melempar exception ke caller — supaya loop batch tidak berhenti
     * hanya karena 1 item gagal dibuat.
     *
     * return: ['success' => bool, 'refund' => Refund, 'message' => string]
     */
    public function createPayout(Refund $refund): array
    {
        // 🔒 Reference ID deterministik = kunci idempotensi ke Xendit.
        // Kalau ini kirim ulang setelah gagal sebelumnya, tambahkan suffix attempt
        // agar tidak bentrok dengan reference_id attempt lama yang sudah FAILED.
        $attempt = 1;
        $baseRef = 'refund-' . $refund->id;
        $referenceId = $baseRef;

        if ($refund->xendit_reference_id) {
            if (preg_match('/-retry-(\d+)$/', $refund->xendit_reference_id, $m)) {
                $attempt = ((int) $m[1]) + 1;
            } else {
                $attempt = 2;
            }
            $referenceId = $baseRef . '-retry-' . $attempt;
        }

        // 🏦 bank_name di tabel refunds SUDAH berisi channel_code Xendit langsung
        // (mengikuti pola yang sama seperti di RefundXenditExport). Tinggal divalidasi.
        $channelCode = trim($refund->bank_name);

        if (!XenditBankList::isValidCode($channelCode)) {
            Log::warning('Payout dibatalkan: channel_code tidak valid/tidak dikenali Xendit', [
                'refund_id'    => $refund->id,
                'channel_code' => $channelCode,
            ]);

            $refund->update([
                'status'          => 'failed',
                'failure_code'    => 'INVALID_CHANNEL_CODE',
                'failure_message' => 'Channel code "' . $channelCode . '" tidak terdaftar di daftar bank Xendit.',
            ]);

            return [
                'success' => false,
                'refund'  => $refund,
                'message' => 'Channel code "' . $channelCode . '" tidak valid.',
            ];
        }

        $relation = $refund->transaction ?? $refund->transactionMerch;

        $payload = [
            'reference_id'       => $referenceId,
            'channel_code'       => $channelCode,
            'channel_properties' => [
                'account_number'      => (string) $refund->account_number,
                'account_holder_name' => $refund->account_name,
            ],
            'amount'      => (int) $refund->grand_total_refunded,
            'currency'    => 'IDR',
            'description' => Str::limit('Refund ' . optional($relation)->kode_unik, 30, ''),
        ];

        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->withHeaders(['Idempotency-key' => $referenceId])
                ->timeout(30)
                ->post($this->baseUrl, $payload);

            $body = $response->json();

            if ($response->successful() && isset($body['id'])) {
                $refund->update([
                    'xendit_reference_id'  => $referenceId,
                    'xendit_payout_id'     => $body['id'],
                    'xendit_payout_status' => $body['status'] ?? 'ACCEPTED',
                    'status'               => 'processing',
                    'sent_to_xendit_at'    => now(),
                    'failure_code'         => null,
                    'failure_message'      => null,
                ]);

                return ['success' => true, 'refund' => $refund, 'message' => 'Payout dibuat, status: ' . ($body['status'] ?? 'ACCEPTED')];
            }

            // ❌ Gagal di tahap PEMBUATAN (validasi/saldo Xendit kurang dsb), bukan gagal transfer
            $errorCode = $body['error_code'] ?? 'UNKNOWN_ERROR';
            $errorMsg  = $body['message'] ?? 'Gagal membuat payout tanpa pesan spesifik.';

            $refund->update([
                'xendit_reference_id' => $referenceId,
                'status'              => 'failed',
                'failure_code'        => $errorCode,
                'failure_message'     => $errorMsg,
                'sent_to_xendit_at'   => now(),
            ]);

            Log::error('Gagal membuat payout Xendit', [
                'refund_id'  => $refund->id,
                'error_code' => $errorCode,
                'message'    => $errorMsg,
            ]);

            return ['success' => false, 'refund' => $refund, 'message' => $errorMsg];

        } catch (\Exception $e) {
            Log::error('Exception saat membuat payout Xendit: ' . $e->getMessage(), ['refund_id' => $refund->id]);

            $refund->update([
                'xendit_reference_id' => $referenceId,
                'status'              => 'failed',
                'failure_code'        => 'NETWORK_ERROR',
                'failure_message'     => $e->getMessage(),
                'sent_to_xendit_at'   => now(),
            ]);

            return ['success' => false, 'refund' => $refund, 'message' => 'Kesalahan jaringan: ' . $e->getMessage()];
        }
    }

    /**
     * 🔍 Tarik status TERKINI sebuah payout langsung dari Xendit (GET).
     * Dipakai untuk sinkronisasi manual saat webhook tidak/terlambat diterima.
     *
     * return: ['success' => bool, 'status' => string|null, 'raw' => array|null, 'message' => string]
     */
    public function fetchPayoutStatus(Refund $refund): array
    {
        // Utamakan lookup by payout id (unik). Fallback ke reference_id bila id belum tersimpan.
        if ($refund->xendit_payout_id) {
            $url = $this->baseUrl . '/' . $refund->xendit_payout_id;
        } elseif ($refund->xendit_reference_id) {
            $url = $this->baseUrl . '?reference_id=' . urlencode($refund->xendit_reference_id);
        } else {
            return ['success' => false, 'status' => null, 'raw' => null, 'message' => 'Refund belum pernah dikirim ke Xendit.'];
        }

        try {
            $response = Http::withBasicAuth($this->apiKey, '')->timeout(30)->get($url);
            $body = $response->json();

            if (!$response->successful()) {
                Log::error('Gagal cek status payout Xendit', [
                    'refund_id' => $refund->id,
                    'http'      => $response->status(),
                    'body'      => $body,
                ]);
                return [
                    'success' => false,
                    'status'  => null,
                    'raw'     => $body,
                    'message' => $body['message'] ?? ('HTTP ' . $response->status()),
                ];
            }

            // Query by reference_id mengembalikan list {data:[...]}; by id mengembalikan objek langsung.
            $payout = (isset($body['data']) && is_array($body['data']))
                ? ($body['data'][0] ?? null)
                : $body;

            if (!$payout) {
                return ['success' => false, 'status' => null, 'raw' => $body, 'message' => 'Payout tidak ditemukan di Xendit.'];
            }

            return [
                'success' => true,
                'status'  => strtoupper($payout['status'] ?? ''),
                'raw'     => $payout,
                'message' => 'OK',
            ];
        } catch (\Throwable $e) {
            Log::error('Exception cek status payout Xendit: ' . $e->getMessage(), ['refund_id' => $refund->id]);
            return ['success' => false, 'status' => null, 'raw' => null, 'message' => 'Kesalahan jaringan: ' . $e->getMessage()];
        }
    }
}
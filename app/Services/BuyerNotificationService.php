<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Membangun daftar notifikasi aktivitas pembeli secara dinamis (derived)
 * dari data transaksi tiket, transaksi merchandise, event, dan refund yang
 * sudah ada — tanpa tabel khusus, sehingga selalu sinkron dengan kondisi
 * terkini dan tidak menyentuh alur pembelian/refund yang berjalan.
 *
 * Notifikasi tiket & merch digabung menjadi satu feed dan diurutkan bersama.
 */
class BuyerNotificationService
{
    /**
     * Ambil notifikasi untuk seorang pembeli, terurut dari yang terbaru.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function for(?User $user, int $limit = 40): array
    {
        if (!$user || empty($user->email)) {
            return [];
        }

        $notifications = array_merge(
            self::ticketNotifications($user),
            self::merchNotifications($user)
        );

        // Urutkan dari yang terbaru, lalu batasi jumlahnya
        usort($notifications, fn ($a, $b) => $b['time']->timestamp <=> $a['time']->timestamp);
        $notifications = array_slice($notifications, 0, $limit);

        // Lengkapi field turunan untuk kebutuhan tampilan & badge JS
        foreach ($notifications as &$n) {
            $n['time_iso']   = $n['time']->toIso8601String();
            $n['time_human'] = $n['time']->diffForHumans();
            unset($n['time']);
        }
        unset($n);

        return $notifications;
    }

    /**
     * Notifikasi dari pembelian tiket event & refund tiket.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function ticketNotifications(User $user): array
    {
        $rows = DB::table('transactions')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->leftJoin('refunds', 'transactions.id', '=', 'refunds.transaction_id')
            ->where('transactions.email', $user->email)
            ->where('transactions.payment_status', 'paid')
            ->select(
                'transactions.id as trx_id',
                'transactions.checkout_time',
                'transactions.created_at as trx_created_at',
                'events.title as event_title',
                'events.status as event_status',
                'events.is_rescheduled as is_rescheduled',
                'events.updated_at as event_updated_at',
                'refunds.status as refund_status',
                'refunds.updated_at as refund_updated_at',
                'refunds.processed_at as refund_processed_at'
            )
            ->get();

        $notifications = [];
        $ticketsUrl = route('user.tickets');

        foreach ($rows as $row) {
            $title = $row->event_title ?? 'Event';
            $hasRefund = !is_null($row->refund_status);

            // 1) Pembelian tiket berhasil
            $notifications[] = [
                'id'      => 'purchase-' . $row->trx_id,
                'type'    => 'purchase',
                'icon'    => '🎟️',
                'color'   => 'green',
                'title'   => 'Pembelian Berhasil',
                'message' => "Kamu berhasil membeli tiket untuk \"{$title}\".",
                'time'    => self::parse($row->checkout_time ?? $row->trx_created_at),
                'url'     => $ticketsUrl,
            ];

            // 2) Event batal / reschedule → berhak refund
            $refundUrl = $hasRefund ? $ticketsUrl : route('buyer.refund.create', $row->trx_id);

            if ($row->event_status === 'cancelled') {
                $notifications[] = [
                    'id'      => 'cancelled-' . $row->trx_id,
                    'type'    => 'cancelled',
                    'icon'    => '🚫',
                    'color'   => 'red',
                    'title'   => 'Event Dibatalkan',
                    'message' => "Event \"{$title}\" telah membatalkan acaranya."
                        . ($hasRefund ? '' : ' Kamu berhak mengajukan refund.'),
                    'time'    => self::parse($row->event_updated_at),
                    'url'     => $refundUrl,
                ];
            } elseif ((int) $row->is_rescheduled > 0) {
                $notifications[] = [
                    'id'      => 'reschedule-' . $row->trx_id,
                    'type'    => 'reschedule',
                    'icon'    => '📅',
                    'color'   => 'blue',
                    'title'   => 'Jadwal Event Berubah',
                    'message' => "Event \"{$title}\" yang tiketnya kamu miliki melakukan reschedule."
                        . ($hasRefund ? '' : ' Kamu berhak mengajukan refund.'),
                    'time'    => self::parse($row->event_updated_at),
                    'url'     => $refundUrl,
                ];
            }

            // 3) Status pengajuan refund tiket
            if ($hasRefund) {
                $notifications[] = self::refundNotification(
                    $row->refund_status,
                    $title,
                    self::parse($row->refund_processed_at ?? $row->refund_updated_at),
                    $ticketsUrl,
                    'refund-' . $row->trx_id
                );
            }
        }

        return $notifications;
    }

    /**
     * Notifikasi dari pembelian merchandise & refund merch.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function merchNotifications(User $user): array
    {
        $rows = DB::table('transaction_merch')
            ->join('events', 'transaction_merch.event_id', '=', 'events.id')
            ->leftJoin('refunds', 'transaction_merch.id', '=', 'refunds.transaction_merch_id')
            ->where('transaction_merch.email', $user->email)
            ->where('transaction_merch.payment_status', 'paid')
            ->select(
                'transaction_merch.id as trx_id',
                'transaction_merch.checkout_time',
                'transaction_merch.created_at as trx_created_at',
                'events.title as event_title',
                'events.status as event_status',
                'events.merch_cancel_decision',
                'events.updated_at as event_updated_at',
                'refunds.status as refund_status',
                'refunds.updated_at as refund_updated_at',
                'refunds.processed_at as refund_processed_at'
            )
            ->get();

        $notifications = [];
        $merchUrl = route('user.merch');

        foreach ($rows as $row) {
            $title = $row->event_title ?? 'Event';
            $hasRefund = !is_null($row->refund_status);

            // 1) Pembelian merch berhasil
            $notifications[] = [
                'id'      => 'merch-purchase-' . $row->trx_id,
                'type'    => 'merch_purchase',
                'icon'    => '🛍️',
                'color'   => 'green',
                'title'   => 'Pembelian Merch Berhasil',
                'message' => "Kamu berhasil membeli merchandise dari event \"{$title}\".",
                'time'    => self::parse($row->checkout_time ?? $row->trx_created_at),
                'url'     => $merchUrl,
            ];

            // 2) Event batal & EO memutuskan refund → merch berhak refund
            if ($row->event_status === 'cancelled' && $row->merch_cancel_decision === 'refund') {
                $notifications[] = [
                    'id'      => 'merch-cancelled-' . $row->trx_id,
                    'type'    => 'merch_cancelled',
                    'icon'    => '🚫',
                    'color'   => 'red',
                    'title'   => 'Merchandise Bisa Direfund',
                    'message' => "Event \"{$title}\" dibatalkan. Merchandise yang kamu beli berhak direfund."
                        . ($hasRefund ? '' : ' Ajukan refund sekarang.'),
                    'time'    => self::parse($row->event_updated_at),
                    'url'     => $hasRefund ? $merchUrl : route('user.merch-refund.create', $row->trx_id),
                ];
            }

            // 3) Status pengajuan refund merch
            if ($hasRefund) {
                $notifications[] = self::refundNotification(
                    $row->refund_status,
                    $title . ' (merchandise)',
                    self::parse($row->refund_processed_at ?? $row->refund_updated_at),
                    $merchUrl,
                    'merch-refund-' . $row->trx_id
                );
            }
        }

        return $notifications;
    }

    /**
     * Bangun satu notifikasi status refund (dipakai bersama tiket & merch).
     *
     * @return array<string, mixed>
     */
    private static function refundNotification(string $status, string $label, Carbon $time, string $url, string $id): array
    {
        if ($status === 'refunded') {
            return [
                'id' => $id, 'type' => 'refund_success', 'icon' => '✅', 'color' => 'green',
                'title'   => 'Refund Berhasil',
                'message' => "Proses refund untuk \"{$label}\" telah berhasil dan dana dikembalikan.",
                'time'    => $time, 'url' => $url,
            ];
        }

        if ($status === 'rejected') {
            return [
                'id' => $id, 'type' => 'refund_rejected', 'icon' => '❌', 'color' => 'red',
                'title'   => 'Refund Ditolak',
                'message' => "Pengajuan refund untuk \"{$label}\" ditolak. Silakan cek riwayat untuk detail.",
                'time'    => $time, 'url' => $url,
            ];
        }

        // waiting / pending
        return [
            'id' => $id, 'type' => 'refund_pending', 'icon' => '⏳', 'color' => 'amber',
            'title'   => 'Refund Sedang Diproses',
            'message' => "Pengajuan refund untuk \"{$label}\" sedang dalam antrean proses.",
            'time'    => $time, 'url' => $url,
        ];
    }

    private static function parse($value): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : Carbon::now();
        } catch (\Throwable $e) {
            return Carbon::now();
        }
    }
}

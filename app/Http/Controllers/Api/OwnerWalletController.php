<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
// 🔄 FIX DOUBLE-DEDUCTION: dipakai di updateWithdrawalStatus() untuk memaksa
// recompute event_wallets/merch_wallets lewat SATU rumus yang sama (bukan
// pengurangan manual terpisah yang bisa dobel-hitung — lihat catatan lengkap
// di updateWithdrawalStatus()).
use App\Http\Controllers\Api\EOTicketController;
use App\Http\Controllers\Api\EOMerchController;

class OwnerWalletController extends Controller
{
    /**
     * Mendapatkan ringkasan saldo riil berjalan dari event_wallets (tiket)
     * dan merch_wallets (merchandise) beserta riwayat pencairan dana global/per event,
     * riwayat dana keluar akibat refund (nilai riil tanpa biaya platform), dan
     * rincian saldo minus per event.
     * Endpoint: GET /api/owner/wallet-ledgers?event_title=Nama+Event
     */
    public function getWalletLedgers(Request $request)
    {
        try {
            // Tangkap filter nama event dari Flutter jika ada
            $eventTitleFilter = $request->query('event_title');
            $targetEventId = null;

            // Jika filter diisi dan bukan 'Semua Event', cari event_id terkait
            if ($eventTitleFilter && strtolower($eventTitleFilter) !== 'semua event') {
                $event = DB::table('events')->where('title', $eventTitleFilter)->first();
                if ($event) {
                    $targetEventId = $event->id;
                } else {
                    $targetEventId = -1;
                }
            }

            // =========================================================
            // 1. HITUNG SALDO BERDASARKAN FILTER EVENT ATAU GLOBAL
            // =========================================================
            $eventWalletsQuery = DB::table('event_wallets');
            $merchWalletsQuery = DB::table('merch_wallets');

            if ($targetEventId !== null) {
                $eventWalletsQuery->where('event_id', $targetEventId);
                $merchWalletsQuery->where('event_id', $targetEventId);
            }

            $eventWalletsTotal = $eventWalletsQuery->sum('available_balance') ?? 0;
            $merchWalletsTotal = $merchWalletsQuery->sum('available_balance') ?? 0;

            // =========================================================
            // 2. RINCIAN SALDO MINUS PER EVENT (TIKET & MERCH)
            //    Catatan: 'negative_balance' adalah nilai defisit riil karena
            //    'available_balance' dijaga tidak pernah turun di bawah 0
            //    (lihat updateWithdrawalStatus -> max(0, ...)). Jadi indikator
            //    utama saldo minus adalah negative_balance > 0.
            // =========================================================
            $minusWalletsDetail = $this->buildMinusWalletsDetail($targetEventId);
            $totalMinusWalletsCount = count($minusWalletsDetail);

            // =========================================================
            // 3. LOG MUTASI KEUANGAN TERPADU: PENCAIRAN DANA (WITHDRAWALS)
            // =========================================================
            $withdrawalActivities = $this->buildWithdrawalActivities($targetEventId);

            // =========================================================
            // 4. LOG MUTASI KEUANGAN TERPADU: DANA KELUAR AKIBAT REFUND
            //    Nilai yang ditampilkan = grand_total_refunded SAJA
            //    (TANPA refunds_tax / biaya platform), karena itulah nominal
            //    riil yang dipotong dari saldo wallet event/merch terkait.
            // =========================================================
            $refundActivities = $this->buildRefundActivities($targetEventId);

            // Gabungkan withdrawal + refund, urutkan terbaru, batasi 40 baris
            $ledgerActivities = $withdrawalActivities
                ->concat($refundActivities)
                ->sortByDesc('created_at_raw')
                ->take(40)
                ->values()
                ->map(function ($item) {
                    unset($item['created_at_raw']);
                    return $item;
                })
                ->all();

            // =========================================================
            // 5. DATA PENDING APPROVALS (PENCAIRAN DANA)
            // =========================================================
            $ticketPending = DB::table('withdrawals')
                ->join('events', 'withdrawals.event_id', '=', 'events.id')
                ->join('eo', 'withdrawals.eo_id', '=', 'eo.id')
                ->where('withdrawals.status', 'pending')
                ->select([
                    'withdrawals.id',
                    DB::raw("'TIKET' as source"),
                    'withdrawals.amount',
                    'withdrawals.note as eo_note',
                    'withdrawals.invoice_file',
                    'events.title as requester_name',
                    'eo.bank_name',
                    'eo.account_name as bank_account_name',
                    'eo.account_number as bank_account_number'
                ]);

            $merchPending = DB::table('merch_withdrawals')
                ->join('events', 'merch_withdrawals.event_id', '=', 'events.id')
                ->join('eo', 'merch_withdrawals.eo_id', '=', 'eo.id')
                ->where('merch_withdrawals.status', 'pending')
                ->select([
                    'merch_withdrawals.id',
                    DB::raw("'MERCH' as source"),
                    'merch_withdrawals.amount',
                    'merch_withdrawals.note as eo_note',
                    'merch_withdrawals.invoice_file',
                    'events.title as requester_name',
                    'eo.bank_name',
                    'eo.account_name as bank_account_name',
                    'eo.account_number as bank_account_number'
                ]);

            if ($targetEventId !== null) {
                $ticketPending->where('withdrawals.event_id', $targetEventId);
                $merchPending->where('merch_withdrawals.event_id', $targetEventId);
            }

            $pendingApprovals = $ticketPending->unionAll($merchPending)->get();

            $pendingApprovalsData = collect($pendingApprovals)->map(function ($item) {
                $fullInvoiceUrl = '';
                if (!empty($item->invoice_file)) {
                    $fullInvoiceUrl = url($item->invoice_file);
                }

                return [
                    'id' => (int) $item->id,
                    'source' => $item->source,
                    'amount' => abs((int) $item->amount),
                    'requester_name' => $item->requester_name ?? 'Organizer',
                    'description' => 'Pencairan Event: ' . ($item->requester_name ?? ''),
                    'bank_name' => $item->bank_name ?? '-',
                    'bank_account_name' => $item->bank_account_name ?? '-',
                    'bank_account_number' => $item->bank_account_number ?? '-',
                    'note' => $item->eo_note ?? 'Tidak ada catatan dari Organizer.',
                    'invoice_file' => $fullInvoiceUrl
                ];
            })->all();

            // =========================================================
            // 6. RESPONSE JSON
            // =========================================================
            return response()->json([
                'status' => 'success',
                'message' => 'Data ledger saldo kas berjalan berhasil dimuat.',
                'event_wallets_total' => (int) $eventWalletsTotal,
                'merch_wallets_total' => (int) $merchWalletsTotal,
                'minus_wallets_count' => (int) $totalMinusWalletsCount,
                'minus_wallets_detail' => $minusWalletsDetail,
                'refund_note' => 'Nominal refund pada log di bawah adalah jumlah riil yang dipotong dari saldo wallet event/merch (TIDAK termasuk biaya platform Rp 2.500 per refund).',
                'pending_approvals' => $pendingApprovalsData,
                'ledger_activities' => $ledgerActivities
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kendala saat membaca data kas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bangun rincian saldo minus per event, untuk wallet tiket (event_wallets)
     * maupun wallet merchandise (merch_wallets).
     */
    private function buildMinusWalletsDetail($targetEventId)
    {
        $minusEventWalletsQuery = DB::table('event_wallets')
            ->join('events', 'event_wallets.event_id', '=', 'events.id')
            ->where(function ($q) {
                $q->where('event_wallets.available_balance', '<', 0)
                  ->orWhere('event_wallets.negative_balance', '>', 0);
            })
            ->select(
                'events.id as event_id',
                'events.title as event_name',
                DB::raw("'TIKET' as wallet_type"),
                'event_wallets.available_balance',
                'event_wallets.held_balance',
                'event_wallets.negative_balance'
            );

        $minusMerchWalletsQuery = DB::table('merch_wallets')
            ->join('events', 'merch_wallets.event_id', '=', 'events.id')
            ->where(function ($q) {
                $q->where('merch_wallets.available_balance', '<', 0)
                  ->orWhere('merch_wallets.negative_balance', '>', 0);
            })
            ->select(
                'events.id as event_id',
                'events.title as event_name',
                DB::raw("'MERCH' as wallet_type"),
                'merch_wallets.available_balance',
                'merch_wallets.held_balance',
                'merch_wallets.negative_balance'
            );

        if ($targetEventId !== null) {
            $minusEventWalletsQuery->where('event_wallets.event_id', $targetEventId);
            $minusMerchWalletsQuery->where('merch_wallets.event_id', $targetEventId);
        }

        return collect($minusEventWalletsQuery->get())
            ->concat($minusMerchWalletsQuery->get())
            ->map(function ($item) {
                return [
                    'event_id' => (int) $item->event_id,
                    'event_name' => $item->event_name,
                    'wallet_type' => $item->wallet_type, // TIKET / MERCH
                    'available_balance' => (int) $item->available_balance,
                    'held_balance' => (int) $item->held_balance,
                    'negative_balance' => (int) $item->negative_balance,
                ];
            })
            ->sortBy('available_balance')
            ->values()
            ->all();
    }

    /**
     * Bangun log mutasi untuk pencairan dana (withdrawals tiket & merch).
     */
    private function buildWithdrawalActivities($targetEventId)
    {
        $ticketWithdrawals = DB::table('withdrawals')
            ->join('events', 'withdrawals.event_id', '=', 'events.id')
            ->select(
                'withdrawals.id',
                DB::raw("'TIKET_WITHDRAW' as type"),
                'withdrawals.amount',
                'withdrawals.status',
                'events.title as event_name',
                'withdrawals.created_at'
            );

        $merchWithdrawals = DB::table('merch_withdrawals')
            ->join('events', 'merch_withdrawals.event_id', '=', 'events.id')
            ->select(
                'merch_withdrawals.id',
                DB::raw("'MERCH_WITHDRAW' as type"),
                'merch_withdrawals.amount',
                'merch_withdrawals.status',
                'events.title as event_name',
                'merch_withdrawals.created_at'
            );

        if ($targetEventId !== null) {
            $ticketWithdrawals->where('withdrawals.event_id', $targetEventId);
            $merchWithdrawals->where('merch_withdrawals.event_id', $targetEventId);
        }

        $rawActivities = $ticketWithdrawals->unionAll($merchWithdrawals)
            ->orderBy('created_at', 'desc')
            ->limit(40)
            ->get();

        return collect($rawActivities)->map(function ($item) {
            $statusText = ' (Status: ' . $item->status . ')';
            $prefix = $item->type === 'TIKET_WITHDRAW' ? 'Pencairan Tiket Event: ' : 'Pencairan Merchandise Event: ';

            return [
                'id' => (int) $item->id,
                'type' => $item->type,
                'amount' => -abs((int) $item->amount),
                'description' => $prefix . $item->event_name . $statusText,
                'event_name' => $item->event_name,
                'status' => $item->status,
                'batch_id' => null,
                'batch_name' => null,
                'batch_label' => null,
                'created_at_raw' => $item->created_at,
                'created_at' => $item->created_at ? date('d M Y, H:i', strtotime($item->created_at)) : '-'
            ];
        });
    }

    /**
     * Bangun log mutasi untuk dana keluar akibat refund (tiket & merch).
     * Hanya refund yang benar-benar berdampak ke wallet yang ditampilkan
     * (status 'rejected' dikecualikan karena tidak pernah memotong saldo).
     * Nilai yang dipakai = grand_total_refunded (TANPA refunds_tax).
     */
    private function buildRefundActivities($targetEventId)
    {
        // Refund TIKET: event didapat lewat transactions.event_id
        $ticketRefundsQuery = DB::table('refunds')
            ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->leftJoin('refund_batches', 'refunds.refund_batch_id', '=', 'refund_batches.id')
            ->whereNotNull('refunds.transaction_id')
            ->where('refunds.status', '!=', 'rejected')
            ->select(
                'refunds.id',
                DB::raw("'REFUND_TIKET' as type"),
                'refunds.grand_total_refunded as amount',
                'refunds.status',
                'events.title as event_name',
                'refund_batches.id as batch_id',
                'refund_batches.name as batch_name',
                'refunds.created_at'
            );

        if ($targetEventId !== null) {
            $ticketRefundsQuery->where('transactions.event_id', $targetEventId);
        }

        // Refund MERCH: event didapat lewat transaction_merch_details -> products.event_id
        $merchRefundsQuery = DB::table('refunds')
            ->join('transaction_merch', 'refunds.transaction_merch_id', '=', 'transaction_merch.id')
            ->join('transaction_merch_details', 'transaction_merch.id', '=', 'transaction_merch_details.transaction_merch_id')
            ->join('products', 'transaction_merch_details.product_id', '=', 'products.id')
            ->join('events', 'products.event_id', '=', 'events.id')
            ->leftJoin('refund_batches', 'refunds.refund_batch_id', '=', 'refund_batches.id')
            ->whereNotNull('refunds.transaction_merch_id')
            ->where('refunds.status', '!=', 'rejected')
            ->groupBy(
                'refunds.id',
                'refunds.grand_total_refunded',
                'refunds.status',
                'events.title',
                'refund_batches.id',
                'refund_batches.name',
                'refunds.created_at'
            )
            ->select(
                'refunds.id',
                DB::raw("'REFUND_MERCH' as type"),
                'refunds.grand_total_refunded as amount',
                'refunds.status',
                'events.title as event_name',
                'refund_batches.id as batch_id',
                'refund_batches.name as batch_name',
                'refunds.created_at'
            );

        if ($targetEventId !== null) {
            $merchRefundsQuery->where('events.id', $targetEventId);
        }

        $rawTicketRefunds = $ticketRefundsQuery->get();
        $rawMerchRefunds = $merchRefundsQuery->get();

        return collect($rawTicketRefunds)->concat($rawMerchRefunds)->map(function ($item) {
            $statusText = ' (Status: ' . $item->status . ')';
            $prefix = $item->type === 'REFUND_TIKET' ? 'Refund Tiket Event: ' : 'Refund Merchandise Event: ';
            // Label pendek & aman untuk badge UI (hindari nama batch panjang bikin overflow di layar).
            $batchLabel = $item->batch_id ? ('Batch #' . $item->batch_id) : null;

            return [
                'id' => (int) $item->id,
                'type' => $item->type,
                'amount' => -abs((int) round((float) $item->amount)),
                // Deskripsi dibuat singkat & konsisten dengan log withdrawal lain, info batch
                // dipisah ke field batch_label supaya tidak memanjangkan/memotong teks utama.
                'description' => $prefix . ($item->event_name ?? 'Tidak diketahui') . $statusText,
                'event_name' => $item->event_name,
                'status' => $item->status,
                'batch_id' => $item->batch_id ? (int) $item->batch_id : null,
                'batch_name' => $item->batch_name, // nama lengkap batch, disimpan untuk keperluan lain
                'batch_label' => $batchLabel, // label singkat untuk badge di UI
                'created_at_raw' => $item->created_at,
                'created_at' => $item->created_at ? date('d M Y, H:i', strtotime($item->created_at)) : '-'
            ];
        });
    }

    /**
     * Memperbarui status pengajuan penarikan dana (Approve / Reject)
     * Endpoint: PUT /api/owner/withdrawals/{id}/status
     */
    public function updateWithdrawalStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'source' => 'required|in:TIKET,MERCH',
            'note' => 'nullable|string'
        ]);

        try {
            $status = $request->status;
            $source = $request->source;
            $ownerNote = $request->note;
            $now = now();

            if ($source === 'TIKET') {
                $withdrawal = DB::table('withdrawals')->where('id', $id)->first();
                if (!$withdrawal) {
                    return response()->json(['status' => 'error', 'message' => 'Data penarikan tiket tidak ditemukan.'], 404);
                }

                if ($withdrawal->status !== 'pending') {
                    return response()->json(['status' => 'error', 'message' => 'Pengajuan ini sudah diproses sebelumnya.'], 400);
                }

                DB::beginTransaction();

                // 🛠️ FIX BUG DOUBLE-DEDUCTION:
                // Sebelumnya di sini available_balance/held_balance dikurangi manual
                // dengan `$wallet->available_balance - $withdrawal->amount`. Masalahnya,
                // rumus recompute di EOTicketController::eventWallets() SUDAH menghitung
                // withdrawal berstatus 'pending' sebagai "sudah terpotong" (whereIn status
                // ['approved','pending']). Jadi saat withdrawal ini di-approve, amount-nya
                // ikut terpotong LAGI di sini -> dobel hitung sesaat sampai recompute
                // berikutnya membetulkan sendiri. Diperbaiki dengan HANYA mengubah status,
                // lalu memaksa recompute pakai method yang sama (satu sumber kebenaran
                // rumus, bukan dua rumus berbeda yang bisa selisih).
                DB::table('withdrawals')->where('id', $id)->update([
                    'status' => $status,
                    'owner_note' => $ownerNote ?? $withdrawal->owner_note,
                    'approved_at' => $status === 'approved' ? $now : null,
                    'updated_at' => $now
                ]);

                DB::commit();

                try {
                    app(EOTicketController::class)->eventWallets($withdrawal->eo_id);
                } catch (\Throwable $e) {
                    // Tidak fatal: wallet akan tetap ter-sinkron di poll /eo/real-revenue
                    // berikutnya. Cukup dicatat agar kelihatan kalau sync langsungnya gagal.
                    \Illuminate\Support\Facades\Log::error('[OwnerWalletController] Gagal sync event_wallets setelah approve withdrawal: ' . $e->getMessage());
                }

            } else {
                $withdrawal = DB::table('merch_withdrawals')->where('id', $id)->first();
                if (!$withdrawal) {
                    return response()->json(['status' => 'error', 'message' => 'Data penarikan merchandise tidak ditemukan.'], 404);
                }

                if ($withdrawal->status !== 'pending') {
                    return response()->json(['status' => 'error', 'message' => 'Pengajuan ini sudah diproses sebelumnya.'], 400);
                }

                DB::beginTransaction();

                // 🛠️ FIX BUG DOUBLE-DEDUCTION (sama seperti cabang TIKET di atas):
                // hanya ubah status, lalu paksa recompute lewat merchWallets() yang
                // menjadi satu-satunya rumus sumber kebenaran untuk merch_wallets.
                DB::table('merch_withdrawals')->where('id', $id)->update([
                    'status' => $status,
                    'owner_note' => $ownerNote ?? $withdrawal->owner_note,
                    'approved_at' => $status === 'approved' ? $now : null,
                    'updated_at' => $now
                ]);

                DB::commit();

                try {
                    app(EOMerchController::class)->merchWallets($withdrawal->eo_id);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('[OwnerWalletController] Gagal sync merch_wallets setelah approve withdrawal: ' . $e->getMessage());
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Pengajuan penarikan dana berhasil di-" . ($status === 'approved' ? 'setujui.' : 'tolak.')
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui status penarikan: ' . $e->getMessage()
            ], 500);
        }
    }
}
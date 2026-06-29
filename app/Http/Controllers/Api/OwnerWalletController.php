<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class OwnerWalletController extends Controller
{
    /**
     * Mendapatkan ringkasan saldo riil berjalan dari event_wallets (tiket)
     * dan merch_wallets (merchandise) beserta riwayat pencairan dana global/per event.
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
            // 2. DETEKSI SALDO MINUS
            // =========================================================
            $minusEventQuery = DB::table('event_wallets')
                ->where(function($q) {
                    $q->where('available_balance', '<', 0)
                      ->orWhere('negative_balance', '>', 0);
                });

            $minusMerchQuery = DB::table('merch_wallets')
                ->where(function($q) {
                    $q->where('available_balance', '<', 0)
                      ->orWhere('negative_balance', '>', 0);
                });

            if ($targetEventId !== null) {
                $minusEventQuery->where('event_id', $targetEventId);
                $minusMerchQuery->where('event_id', $targetEventId);
            }

            $totalMinusWalletsCount = $minusEventQuery->count() + $minusMerchQuery->count();

            // =========================================================
            // 3. LOG MUTASI KEUANGAN TERPADU (NATIVE UNION TANPA TOSQL)
            // =========================================================
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

            // Gunakan Native Laravel Union, otomatis mengurutkan & membatasi record dengan aman
            $rawActivities = $ticketWithdrawals->unionAll($merchWithdrawals)
                ->orderBy('created_at', 'desc')
                ->limit(30)
                ->get();

            $ledgerActivities = collect($rawActivities)->map(function ($item) {
                $statusText = $item->status === 'pending' ? ' (Status: pending)' : ' (Status: ' . $item->status . ')';
                $prefix = $item->type === 'TIKET_WITHDRAW' ? 'Pencairan Tiket Event: ' : 'Pencairan Merchandise Event: ';

                return [
                    'id' => (int) $item->id,
                    'type' => $item->type,
                    'amount' => -abs((int) $item->amount), 
                    'description' => $prefix . $item->event_name . $statusText,
                    'event_name' => $item->event_name, 
                    'status' => $item->status,
                    'created_at' => $item->created_at ? date('d M Y, H:i', strtotime($item->created_at)) : '-'
                ];
            })->all();

            // =========================================================
            // 4. DATA PENDING APPROVALS (SUDAH DIBUAT DINAMIS JOIN KE TABEL EO)
            // =========================================================
            $ticketPending = DB::table('withdrawals')
                ->join('events', 'withdrawals.event_id', '=', 'events.id')
                ->join('eo', 'withdrawals.eo_id', '=', 'eo.id') // <-- JOIN TABEL EO
                ->where('withdrawals.status', 'pending')
                ->select([
                    'withdrawals.id', 
                    DB::raw("'TIKET' as source"), 
                    'withdrawals.amount', 
                    'withdrawals.note as eo_note',
                    'events.title as requester_name',
                    'eo.bank_name',
                    'eo.account_name as bank_account_name',
                    'eo.account_number as bank_account_number'
                ]);

            $merchPending = DB::table('merch_withdrawals')
                ->join('events', 'merch_withdrawals.event_id', '=', 'events.id')
                ->join('eo', 'merch_withdrawals.eo_id', '=', 'eo.id') // <-- JOIN TABEL EO
                ->where('merch_withdrawals.status', 'pending')
                ->select([
                    'merch_withdrawals.id', 
                    DB::raw("'MERCH' as source"), 
                    'merch_withdrawals.amount', 
                    'merch_withdrawals.note as eo_note',
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
                return [
                    'id' => (int) $item->id,
                    'source' => $item->source,
                    'amount' => abs((int) $item->amount),
                    'requester_name' => $item->requester_name ?? 'Organizer',
                    'description' => 'Pencairan Event: ' . ($item->requester_name ?? ''),
                    // Mengirim data rekening & catatan asli dari database ke Flutter
                    'bank_name' => $item->bank_name ?? '-',
                    'bank_account_name' => $item->bank_account_name ?? '-',
                    'bank_account_number' => $item->bank_account_number ?? '-',
                    'note' => $item->eo_note ?? 'Tidak ada catatan dari Organizer.'
                ];
            })->all();

            // =========================================================
            // 5. RESPONSE JSON
            // =========================================================
            return response()->json([
                'status' => 'success',
                'message' => 'Data ledger saldo kas berjalan berhasil dimuat.',
                'event_wallets_total' => (int) $eventWalletsTotal,
                'merch_wallets_total' => (int) $merchWalletsTotal,
                'minus_wallets_count' => (int) $totalMinusWalletsCount,
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
     * Memperbarui status pengajuan penarikan dana (Approve / Reject)
     * Endpoint: PUT /api/owner/withdrawals/{id}/status
     */
    public function updateWithdrawalStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'source' => 'required|in:TIKET,MERCH', 
            'note' => 'nullable|string' // Ini yang dikirim Flutter sebagai owner_note
        ]);

        try {
            $status = $request->status;
            $source = $request->source;
            $ownerNote = $request->note; // Kita ubah penamaan variabelnya agar lebih jelas
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

                if ($status === 'approved') {
                    $wallet = DB::table('event_wallets')->where('event_id', $withdrawal->event_id)->first();
                    if (!$wallet) {
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => 'Gagal: Dompet Event Wallet tidak ditemukan.'], 404);
                    }

                    $newHeld = max(0, (int)$wallet->held_balance - (int)$withdrawal->amount);
                    $newAvailable = max(0, (int)$wallet->available_balance - (int)$withdrawal->amount);

                    DB::table('event_wallets')->where('event_id', $withdrawal->event_id)->update([
                        'held_balance' => $newHeld,
                        'available_balance' => $newAvailable,
                        'updated_at' => $now
                    ]);
                }

                // PERBAIKAN: Simpan ke owner_note, biarkan kolom note tetap bawaan EO
                DB::table('withdrawals')->where('id', $id)->update([
                    'status' => $status,
                    'owner_note' => $ownerNote ?? $withdrawal->owner_note,
                    'approved_at' => $status === 'approved' ? $now : null,
                    'updated_at' => $now
                ]);

                DB::commit();

            } else {
                $withdrawal = DB::table('merch_withdrawals')->where('id', $id)->first();
                if (!$withdrawal) {
                    return response()->json(['status' => 'error', 'message' => 'Data penarikan merchandise tidak ditemukan.'], 404);
                }

                if ($withdrawal->status !== 'pending') {
                    return response()->json(['status' => 'error', 'message' => 'Pengajuan ini sudah diproses sebelumnya.'], 400);
                }

                DB::beginTransaction();

                if ($status === 'approved') {
                    $wallet = DB::table('merch_wallets')->where('event_id', $withdrawal->event_id)->first();
                    if (!$wallet) {
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => 'Gagal: Dompet Merch Wallet tidak ditemukan.'], 404);
                    }

                    $newHeld = max(0, (int)$wallet->held_balance - (int)$withdrawal->amount);
                    $newAvailable = max(0, (int)$wallet->available_balance - (int)$withdrawal->amount);

                    DB::table('merch_wallets')->where('event_id', $withdrawal->event_id)->update([
                        'held_balance' => $newHeld,
                        'available_balance' => $newAvailable,
                        'updated_at' => $now
                    ]);
                }

                // PERBAIKAN: Simpan ke owner_note, biarkan kolom note tetap bawaan EO
                DB::table('merch_withdrawals')->where('id', $id)->update([
                    'status' => $status,
                    'owner_note' => $ownerNote ?? $withdrawal->owner_note,
                    'approved_at' => $status === 'approved' ? $now : null,
                    'updated_at' => $now
                ]);

                DB::commit();
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
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class AdminCustomerManagementController extends Controller
{
    /**
     * Helper untuk memformat URL Avatar secara dinamis
     */
    private function formatAvatarUrl($avatar)
    {
        if (!$avatar) {
            return null;
        }
        
        // Jika sudah berupa URL penuh (http:// atau https:// seperti googleusercontent), langsung return
        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }
        
        // Jika berupa file path lokal upload-an, bungkus dengan asset storage
        return url('storage/' . $avatar);
    }

    /**
     * Ambil Semua Daftar Customer / EO
     * Endpoint: GET /api/admin/users
     */
    public function getCustomerList()
    {
        try {
            $customers = DB::table('users')
                ->leftJoin('eo', 'users.id', '=', 'eo.user_id')
                ->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.role',
                    'users.avatar',
                    'eo.nama_badan_usaha as organization_name',
                    'eo.status as eo_status'
                ])
                ->whereIn('users.role', ['user', 'eo'])
                ->get();

            $formatted = $customers->map(function($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name ?? 'Tanpa Nama',
                    'email' => $customer->email,
                    'role' => strtoupper($customer->role),
                    // Menggunakan helper formatAvatarUrl
                    'avatar' => $this->formatAvatarUrl($customer->avatar),
                    'is_registered_as_eo' => $customer->organization_name ? true : false,
                    'organization_name' => $customer->organization_name ?? '-'
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formatted
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getCustomerList: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal server.',
                'error_debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ambil rekam jejak aktivitas finansial & manajemen EO berdasarkan ID User
     * Endpoint: GET /api/admin/users/{id}/activity
     */
    public function getCustomerActivity($id)
    {
        try {
            $user = DB::table('users')->where('id', $id)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengguna tidak ditemukan.'
                ], 404);
            }

            $userEmail = $user->email;

            // Log Tiket Konsumen
            $ticketPurchases = DB::table('transactions')
                ->join('events', 'transactions.event_id', '=', 'events.id')
                ->where('transactions.email', $userEmail)
                ->select([
                    'transactions.id',
                    'events.title as item_name',
                    'transactions.grand_total as amount',
                    'transactions.created_at as date',
                    'transactions.payment_status as status'
                ])->get()->map(function($item) {
                    $item->type = 'TIKET';
                    return $item;
                });

            // Log Merch Konsumen
            $merchPurchases = DB::table('transaction_merch')
                ->where('transaction_merch.email', $userEmail)
                ->select([
                    'transaction_merch.id',
                    'transaction_merch.kode_unik as item_name',
                    'transaction_merch.grand_total as amount',
                    'transaction_merch.created_at as date',
                    'transaction_merch.payment_status as status'
                ])->get()->map(function($item) {
                    $item->item_name = 'Invoice Merch: ' . ($item->item_name ?? '#' . $item->id);
                    $item->type = 'MERCHANDISE';
                    return $item;
                });

            $allActivities = $ticketPurchases->concat($merchPurchases);

            // Cek Profil Mitra EO
            $eoProfile = DB::table('eo')->where('user_id', $id)->first();

            if ($eoProfile) {
                $eoId = $eoProfile->id;

                // Pengajuan Acara
                $eventLogs = DB::table('events')
                    ->where('eo_id', $eoId)
                    ->select([
                        'id',
                        'title as item_name',
                        DB::raw('0 as amount'),
                        'created_at as date',
                        'status'
                    ])->get()->map(function($item) {
                        $item->type = 'MANAJEMEN_EVENT';
                        return $item;
                    });

                // Penarikan Dompet Mitra
                $withdrawalLogs = DB::table('merch_withdrawals')
                    ->join('merch_wallets', 'merch_withdrawals.event_id', '=', 'merch_wallets.event_id')
                    ->join('events', 'merch_wallets.event_id', '=', 'events.id')
                    ->where('merch_withdrawals.eo_id', $eoId)
                    ->select([
                        'merch_withdrawals.id',
                        DB::raw("CONCAT('WD Dana: ', events.title) as item_name"),
                        'merch_withdrawals.amount',
                        'merch_withdrawals.created_at as date',
                        'merch_withdrawals.status'
                    ])->get()->map(function($item) {
                        $item->type = 'WITHDRAW_EO';
                        return $item;
                    });

                // Aksi Refund Aktor
                $refundLogs = DB::table('refunds')
                    ->join('refund_batches', 'refunds.refund_batch_id', '=', 'refund_batches.id')
                    ->join('events', 'refund_batches.event_id', '=', 'events.id')
                    ->where('refund_batches.eo_id', $eoId)
                    ->select([
                        'refunds.id',
                        DB::raw("CONCAT('Refund Selesai (', events.title, ')') as item_name"),
                        'refunds.grand_total_refunded as amount',
                        'refunds.created_at as date',
                        'refunds.status'
                    ])->get()->map(function($item) {
                        $item->type = 'REFUND_ACTION';
                        return $item;
                    });

                $allActivities = $allActivities->concat($eventLogs)->concat($withdrawalLogs)->concat($refundLogs);
            }

            $sortedActivities = $allActivities->sortByDesc('date')->values();

            return response()->json([
                'status' => 'success',
                'customer_info' => [
                    'name' => $user->name ?? 'Tanpa Nama',
                    'email' => $user->email,
                    'role' => strtoupper($user->role),
                    // Menggunakan helper formatAvatarUrl
                    'avatar' => $this->formatAvatarUrl($user->avatar),
                    'is_registered_as_eo' => $eoProfile ? true : false,
                    'eo_details' => $eoProfile ? [
                        'organization_name' => $eoProfile->nama_badan_usaha,
                        'joined_at' => $eoProfile->created_at,
                        'verification_status' => strtoupper($eoProfile->status),
                        'balance' => (int) $eoProfile->balance,
                        'rejected_reason' => $eoProfile->rejected_reason
                    ] : null
                ],
                'activities' => $sortedActivities
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getCustomerActivity: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal server.',
                'error_debug' => $e->getMessage()
            ], 500);
        }
    }
}
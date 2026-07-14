<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EoTransactionController extends Controller
{
    public function getOutgoingTransactions(Request $request)
    {
        try {
            // 1. Ambil user EO yang sedang login
            // Sesuaikan cara mengambil eo_id jika disimpan di tabel/kolom lain (misal: Auth::user()->eo->id)
            $eoId = Auth::user()->eo_id ?? 1; 

            // 2. Query data Penarikan Tiket (withdrawals)
            $ticketWithdrawals = DB::table('withdrawals')
                ->join('events', 'withdrawals.event_id', '=', 'events.id')
                ->where('withdrawals.eo_id', $eoId)
                ->select([
                    'withdrawals.id as unique_id',
                    DB::raw("'ticket_withdrawal' as type"),
                    'events.title as event_title',
                    'withdrawals.amount',
                    'withdrawals.status',
                    'withdrawals.note',
                    'withdrawals.created_at'
                ]);

            // 3. Query data Penarikan Merchandise (merch_withdrawals)
            $merchWithdrawals = DB::table('merch_withdrawals')
                ->join('events', 'merch_withdrawals.event_id', '=', 'events.id')
                ->where('merch_withdrawals.eo_id', $eoId)
                ->select([
                    'merch_withdrawals.id as unique_id',
                    DB::raw("'merch_withdrawal' as type"),
                    'events.title as event_title',
                    'merch_withdrawals.amount',
                    'merch_withdrawals.status',
                    'merch_withdrawals.note',
                    'merch_withdrawals.created_at'
                ]);

            // 4. Query data Pengembalian Dana (refunds) melalui refund_batches
            $refunds = DB::table('refunds')
                ->join('refund_batches', 'refunds.refund_batch_id', '=', 'refund_batches.id')
                ->join('events', 'refund_batches.event_id', '=', 'events.id')
                ->where('refund_batches.eo_id', $eoId)
                ->select([
                    'refunds.id as unique_id',
                    DB::raw("'refund' as type"),
                    'events.title as event_title',
                    'refunds.grand_total_refunded as amount',
                    'refunds.status',
                    DB::raw("CONCAT('Refund ke rekening ', refunds.bank_name, ' a/n ', refunds.account_name) as note"),
                    'refunds.created_at'
                ]);

            // 5. Gabungkan ketiga query menggunakan UNION dan urutkan dari yang terbaru
            $transactions = $ticketWithdrawals
                ->unionAll($merchWithdrawals)
                ->unionAll($refunds)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data transaksi keluar.',
                'data' => $transactions
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
            ], 500);
        }
    }
}
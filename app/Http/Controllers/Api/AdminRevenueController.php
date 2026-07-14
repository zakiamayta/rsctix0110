<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminRevenueController extends Controller
{
    public function getRevenueDetail(Request $request)
    {
        $filter = $request->query('filter', 'all'); // all, today, month, year
        
        // A. AMBIL DATA PENDAPATAN ASLI PLATFORM DARI PLATFORM_WALLETS
        $platformWallet = DB::table('platform_wallets')->first();
        $totalServiceTaxEarned = $platformWallet ? (int) $platformWallet->total_service_tax_earned : 0;
        $totalRefundOperationalCost = $platformWallet ? (int) $platformWallet->total_refund_fees_spent : 0;
        $currentPlatformBalance = $platformWallet ? (int) $platformWallet->current_balance : 0;

        // B. HITUNG DANA YANG SUDAH BERHASIL DI-WITHDRAW OLEH EO (APPROVED)
        // Perbaikan: Menghapus status 'paid' karena tidak ada di ENUM database merch_withdrawals
        $totalEoWithdrawMerch = (int) DB::table('merch_withdrawals')
            ->where('status', 'approved')
            ->sum('amount');
        
        // Jika ke depannya ada tabel withdrawal khusus tiket (misal: event_withdrawals), silakan di-sum di sini:
        $totalEoWithdrawTicket = 0; 
        // $totalEoWithdrawTicket = (int) DB::table('event_withdrawals')->where('status', 'approved')->sum('amount');

        $totalEoWithdrawCombined = $totalEoWithdrawMerch + $totalEoWithdrawTicket;
        
        // 1. Inisialisasi Query Dasar Omset Bruto (Hanya yang paid dan tidak direfund sepenuhnya)
        $ticketQuery = DB::table('transactions')
            ->leftJoin('refunds', 'transactions.id', '=', 'refunds.transaction_id')
            ->where('transactions.payment_status', 'paid')
            ->where(function($query) {
                $query->whereNull('refunds.id')
                      ->orWhere('refunds.status', '!=', 'refunded');
            });

        $merchQuery = DB::table('transaction_merch')
            ->leftJoin('refunds', 'transaction_merch.id', '=', 'refunds.transaction_merch_id')
            ->where('transaction_merch.payment_status', 'paid')
            ->where(function($query) {
                $query->whereNull('refunds.id')
                      ->orWhere('refunds.status', '!=', 'refunded');
            });
        
        // 2. Query History Gabungan untuk Aliran Riwayat Transaksi Terbaru
        $ticketHistoryQuery = DB::table('transactions')
            ->leftJoin('events', 'transactions.event_id', '=', 'events.id')
            ->leftJoin('refunds', 'transactions.id', '=', 'refunds.transaction_id')
            ->select([
                'transactions.id as id',
                'transactions.kode_unik as code',
                'events.title as item_name',
                'transactions.grand_total as amount',
                'transactions.paid_time as date',
                DB::raw("'Tiket' as type")
            ])
            ->where('transactions.payment_status', 'paid')
            ->where(function($query) {
                $query->whereNull('refunds.id')
                      ->orWhere('refunds.status', '!=', 'refunded');
            });

        $merchHistoryQuery = DB::table('transaction_merch')
            ->leftJoin('refunds', 'transaction_merch.id', '=', 'refunds.transaction_merch_id')
            ->select([
                'transaction_merch.id as id',
                'transaction_merch.kode_unik as code',
                DB::raw("'Produk Merchandise' as item_name"),
                'transaction_merch.grand_total as amount',
                'transaction_merch.paid_time as date',
                DB::raw("'Merch' as type")
            ])
            ->where('transaction_merch.payment_status', 'paid')
            ->where(function($query) {
                $query->whereNull('refunds.id')
                      ->orWhere('refunds.status', '!=', 'refunded');
            });

        // 3. Terapkan Filter Rentang Waktu
        if ($filter !== 'all') {
            $now = Carbon::now();
            $startDate = null;

            switch ($filter) {
                case 'today':
                    $startDate = $now->startOfDay()->toDateTimeString();
                    break;
                case 'month':
                    $startDate = $now->startOfMonth()->toDateTimeString();
                    break;
                case 'year':
                    $startDate = $now->startOfYear()->toDateTimeString();
                    break;
            }

            if ($startDate) {
                $ticketQuery->where('transactions.paid_time', '>=', $startDate);
                $merchQuery->where('transaction_merch.paid_time', '>=', $startDate);
                $ticketHistoryQuery->where('transactions.paid_time', '>=', $startDate);
                $merchHistoryQuery->where('transaction_merch.paid_time', '>=', $startDate);
            }
        }

        // 4. Kalkulasi Total Bruto Sesuai Filter Periode
        $totalTicketRevenue = (int) $ticketQuery->sum('transactions.grand_total');
        $totalMerchRevenue = (int) $merchQuery->sum('transaction_merch.grand_total');
        $totalGrossRevenue = $totalTicketRevenue + $totalMerchRevenue;

        // 5. Eksekusi Union & Pengurutan Data
        $transactionsList = $ticketHistoryQuery->unionAll($merchHistoryQuery)->get();
        $sortedTransactions = $transactionsList->sortByDesc('date')->values();

        $formattedTransactions = $sortedTransactions->map(function ($item) {
            return [
                'id' => $item->id,
                'code' => $item->code ?? 'N/A',
                'item_name' => $item->item_name ?? 'Item Tanpa Nama',
                'amount' => (int) $item->amount,
                'date' => $item->date ? Carbon::parse($item->date)->toIso8601String() : null,
                'type' => $item->type,
            ];
        });

        // 6. Return Response JSON Terstruktur Lengkap
        return response()->json([
            'status' => 'success',
            'summary' => [
                'total_ticket_revenue' => $totalTicketRevenue,
                'total_merch_revenue' => $totalMerchRevenue,
                'total_gross_revenue' => $totalGrossRevenue,
                'total_service_tax_earned' => $totalServiceTaxEarned,
                'total_refund_fees_spent' => $totalRefundOperationalCost,
                'total_eo_withdraw' => $totalEoWithdrawCombined, // Menggunakan total gabungan yang valid
                'current_platform_balance' => $currentPlatformBalance
            ],
            'transactions' => $formattedTransactions
        ], 200);
    }
}
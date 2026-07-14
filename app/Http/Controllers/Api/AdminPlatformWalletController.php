<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminPlatformWalletController extends Controller
{
    /**
     * Mengambil rincian data keuangan platform berserta riwayat transaksi & refund untuk log buku besar.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Proteksi akses khusus admin
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Halaman ini khusus Admin Utama.'
            ], 403);
        }

        try {
            // 2. Ambil data ringkasan (Summary) dari tabel platform_wallets
            $wallet = DB::table('platform_wallets')->first();

            // KONDISI DIPERBAIKI: Jika wallet tidak ada, atau datanya masih nol semua, jalankan perhitungan real-time
            if ($wallet && ((float)$wallet->total_service_tax_earned > 0 || (float)$wallet->current_balance > 0)) {
                $totalServiceTaxEarned = (float)$wallet->total_service_tax_earned;
                $totalRefundFeesSpent  = (float)$wallet->total_refund_fees_spent;
                $currentBalance        = (float)$wallet->current_balance;
            } else {
                // 📊 REAL-TIME FALLBACK LOGIC: Hitung langsung dari akumulasi transaksi sistem
                
                // Ambil service_tax dari transaksi tiket (status paid atau refunded)
                $ticketTax = DB::table('transactions')
                    ->whereIn('payment_status', ['paid', 'refunded'])
                    ->sum('service_tax');

                // Ambil service_tax dari transaksi merchandise (status paid atau refunded)
                // Service tax TIDAK dikembalikan saat refund — platform tetap menyimpannya, konsisten dengan tiket.
                $merchTax = DB::table('transaction_merch')
                    ->whereIn('payment_status', ['paid', 'refunded'])
                    ->sum('service_tax');

                $totalServiceTaxEarned = (float)($ticketTax + $merchTax);

                // Ambil akumulasi pengeluaran biaya admin refund dari bank/Xendit (kolom refunds_tax)
                $totalRefundFeesSpent = (float)DB::table('refunds')
                    ->where('status', 'refunded')
                    ->sum('refunds_tax');

                // Jika kolom refunds_tax bernilai 0 namun ada baris data refund, beri fallback minimal Rp 2.500 per refund
                if ($totalRefundFeesSpent == 0) {
                    $refundCount = DB::table('refunds')->where('status', 'refunded')->count();
                    $totalRefundFeesSpent = (float)($refundCount * 2500);
                }

                $currentBalance = $totalServiceTaxEarned - $totalRefundFeesSpent;

                // Update atau sisipkan data baru ke platform_wallets agar sinkron ke depannya
                if (!$wallet) {
                    DB::table('platform_wallets')->insert([
                        'total_service_tax_earned' => $totalServiceTaxEarned,
                        'total_refund_fees_spent'  => $totalRefundFeesSpent,
                        'current_balance'          => $currentBalance,
                        'created_at'               => now(),
                        'updated_at'               => now()
                    ]);
                } else {
                    DB::table('platform_wallets')->where('id', $wallet->id)->update([
                        'total_service_tax_earned' => $totalServiceTaxEarned,
                        'total_refund_fees_spent'  => $totalRefundFeesSpent,
                        'current_balance'          => $currentBalance,
                        'updated_at'               => now()
                    ]);
                }
            }

            // 3. AMBIL LOG RINCIAN BUKU BESAR SECARA DINAMIS
            $formattedLedgers = [];

            // Ambil Data Pendapatan Pajak Tiket
            $tickets = DB::table('transactions')
                ->whereIn('payment_status', ['paid', 'refunded'])
                ->select(['kode_unik as reference', 'service_tax as amount', 'updated_at as date'])
                ->get();

            foreach ($tickets as $t) {
                if ((float)$t->amount > 0) {
                    $formattedLedgers[] = [
                        'type' => 'income_ticket',
                        'reference' => 'REF-' . ($t->reference ?? 'TKT'),
                        'amount' => (float)$t->amount,
                        'date' => $t->date ? date('Y-m-d H:i:s', strtotime($t->date)) : now()->toDateTimeString(),
                    ];
                }
            }

            // Ambil Data Pendapatan Pajak Merchandise
            $merches = DB::table('transaction_merch')
                ->where('payment_status', 'paid')
                ->select(['kode_unik as reference', 'service_tax as amount', 'updated_at as date'])
                ->get();

            foreach ($merches as $m) {
                if ((float)$m->amount > 0) {
                    $formattedLedgers[] = [
                        'type' => 'income_merch',
                        'reference' => 'REF-' . ($m->reference ?? 'MRCH'),
                        'amount' => (float)$m->amount,
                        'date' => $m->date ? date('Y-m-d H:i:s', strtotime($m->date)) : now()->toDateTimeString(),
                    ];
                }
            }

            // Ambil Data Beban Pengeluaran Biaya Refund (Xendit Fee / Bank Fee)
            $refunds = DB::table('refunds')
                ->where('status', 'refunded')
                ->select(['id as reference', 'refunds_tax as amount', 'updated_at as date'])
                ->get();

            foreach ($refunds as $r) {
                $refundTaxAmount = (float)$r->amount;
                // Fallback jika nominal pajak refund di DB tercatat kosong padahal statusnya berhasil keluar
                if ($refundTaxAmount == 0) {
                    $refundTaxAmount = 2500.0;
                }

                $formattedLedgers[] = [
                    'type' => 'expense_refund',
                    'reference' => 'RFD-' . $r->reference,
                    'amount' => $refundTaxAmount,
                    'date' => $r->date ? date('Y-m-d H:i:s', strtotime($r->date)) : now()->toDateTimeString(),
                ];
            }

            // Urutkan rincian buku besar berdasarkan tanggal terbaru (descending)
            usort($formattedLedgers, function ($a, $b) {
                return strcmp($b['date'], $a['date']);
            });

            // 4. Return respon JSON yang sudah dipastikan kuncinya klop dengan Flutter
            return response()->json([
                'success' => true,
                'message' => 'Data buku kas platform_wallets berhasil dimuat.',
                'data' => [
                    'total_service_tax_earned' => $totalServiceTaxEarned,
                    'total_refund_fees_spent'  => $totalRefundFeesSpent,
                    'current_balance'          => $currentBalance,
                    'ledgers'                  => $formattedLedgers
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data buku kas platform akibat kendala internal server.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
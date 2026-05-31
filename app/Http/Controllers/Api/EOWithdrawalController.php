<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class EOWithdrawalController extends Controller
{
    /**
     * 🔥 LIST WALLET EVENT EO
     */
    public function eventWallets($eoId)
    {
        try {

            /**
             * 🔥 AMBIL SEMUA EVENT EO
             */
            $events = DB::table('events')

                ->where('eo_id', $eoId)

                ->orderByDesc('id')

                ->get();

            $result = [];

            foreach ($events as $event) {

                /**
                 * 🔥 TOTAL TRANSAKSI PAID
                 */
                $paidTotal = DB::table('transactions')

                    ->where('event_id', $event->id)

                    ->where('payment_status', 'paid')

                    ->sum('total_amount');

                /**
                 * 🔥 CEK WALLET
                 */
                $wallet = DB::table('event_wallets')

                    ->where('event_id', $event->id)

                    ->first();

                /**
                 * 🔥 JIKA BELUM ADA
                 */
                if (!$wallet) {

                    DB::table('event_wallets')

                        ->insert([

                            'eo_id' => $eoId,

                            'event_id' => $event->id,

                            'available_balance' => 0,

                            /**
                             * semua saldo masuk held dulu
                             */
                            'held_balance' => $paidTotal,

                            'negative_balance' => 0,

                            'withdraw_locked' => 0,

                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    $wallet = DB::table('event_wallets')

                        ->where('event_id', $event->id)

                        ->first();
                }

                /**
                 * 🔥 UPDATE HELD BALANCE
                 * sinkron dengan transaksi paid
                 */
                DB::table('event_wallets')

                    ->where('event_id', $event->id)

                    ->update([

                        'held_balance' => $paidTotal,

                        'updated_at' => now(),
                    ]);

                $wallet = DB::table('event_wallets')

                    ->where('event_id', $event->id)

                    ->first();

                $result[] = [

                    'event_id' => $event->id,

                    'event_name' => $event->title,

                    'poster' => $event->poster,

                    'date' => $event->date,

                    'status' => $event->status,

                    'available_balance' =>
                        (int) $wallet->available_balance,

                    'held_balance' =>
                        (int) $wallet->held_balance,

                    'negative_balance' =>
                        (int) $wallet->negative_balance,

                    'withdraw_locked' =>
                        (int) $wallet->withdraw_locked,
                ];
            }

            return response()->json([

                'success' => true,

                'data' => $result,
            ]);

        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
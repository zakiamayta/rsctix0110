<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use App\Models\Withdrawal;
use App\Models\EventWallet;
use App\Services\TicketWalletService;

class WithdrawalApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (!auth()->check()) {
                return redirect()->route('login');
            }

            if (auth()->user()->role !== 'owner') {
                abort(403, 'Akses hanya untuk Owner');
            }

            return $next($request);
        });
    }

/*
    |--------------------------------------------------------------------------
    | LIST WITHDRAWAL (DENGAN FILTER & SALDO WALLET DINAMIS)
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        // 1. Ambil semua event yang memiliki wallet tiket/event untuk pilihan dropdown filter
        $filterEvents = \App\Models\Event::whereHas('eventWallet')->get();

        // 2. Buat query dasar untuk pengajuan penarikan dana
        $withdrawalQuery = Withdrawal::with([
            'eo',
            'event'
        ])->latest();

        // 3. Buat query dasar untuk menghitung saldo wallet event
        $walletQuery = EventWallet::query();

        // 4. Jika terdapat filter event_id yang dipilih oleh Owner
        if ($request->filled('event_id')) {
            $eventId = $request->event_id;
            
            // Filter daftar penarikan berdasarkan event terkait
            $withdrawalQuery->where('event_id', $eventId);
            
            // Filter hitungan saldo wallet hanya untuk event terkait
            $walletQuery->where('event_id', $eventId);
        }

        // 5. Hitung total saldo tersedia (Available Balance) secara dinamis
        $totalAvailableBalance = $walletQuery->sum('available_balance');

        // 6. Eksekusi data penarikan dengan tetap membawa query string filter saat berpindah halaman
        $withdrawals = $withdrawalQuery->paginate(20)->withQueryString();

        return view(
            'owner.withdrawals.index',
            compact('withdrawals', 'totalAvailableBalance', 'filterEvents')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL WITHDRAWAL
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $withdrawal = Withdrawal::with([
            'eo',
            'event'
        ])->findOrFail($id);

        $wallet = EventWallet::where(
            'event_id',
            $withdrawal->event_id
        )->first();

        return view(
            'owner.withdrawals.show',
            compact(
                'withdrawal',
                'wallet'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE WITHDRAWAL
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        $id
    ) {

        $withdrawal = Withdrawal::findOrFail($id);

        $request->validate([
            'transfer_proof' =>
                'required|file|mimes:jpg,jpeg,png,pdf|max:4096',

            'owner_note' =>
                'nullable|string|max:1000',
        ]);

        if ($withdrawal->status !== 'pending') {

            return back()->with(
                'error',
                'Withdrawal sudah diproses'
            );
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | WALLET
            |--------------------------------------------------------------------------
            */

            $wallet = EventWallet::where(
                'event_id',
                $withdrawal->event_id
            )
            ->lockForUpdate()
            ->first();

            if (!$wallet) {

                DB::rollBack();

                return back()->with(
                    'error',
                    'Wallet event tidak ditemukan'
                );
            }

            if (
                $wallet->available_balance <
                $withdrawal->amount
            ) {

                DB::rollBack();

                return back()->with(
                    'error',
                    'Saldo wallet tidak mencukupi'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | UPLOAD BUKTI TRANSFER
            |--------------------------------------------------------------------------
            */

            $proofPath = null;

            if ($request->hasFile('transfer_proof')) {

                if (
                    !File::exists(
                        public_path('images/withdrawals')
                    )
                ) {
                    File::makeDirectory(
                        public_path('images/withdrawals'),
                        0755,
                        true
                    );
                }

                $file = $request->file(
                    'transfer_proof'
                );

                $filename =
                    Str::uuid()
                    . '.'
                    . $file->getClientOriginalExtension();

                $file->move(
                    public_path('images/withdrawals'),
                    $filename
                );

                $proofPath =
                    'images/withdrawals/' .
                    $filename;
            }

            /*
            |--------------------------------------------------------------------------
            | POTONG SALDO WALLET
            |--------------------------------------------------------------------------
            */

            $wallet->available_balance =
                $wallet->available_balance -
                $withdrawal->amount;

            $wallet->save();

            /*
            |--------------------------------------------------------------------------
            | UPDATE WITHDRAWAL
            |--------------------------------------------------------------------------
            */

            $withdrawal->status = 'approved';
            $withdrawal->transfer_proof = $proofPath;
            $withdrawal->owner_note = $request->owner_note;
            $withdrawal->approved_at = now();
            $withdrawal->paid_at = now();
            $withdrawal->save();

            DB::commit();

            TicketWalletService::recalculate($withdrawal->event_id);

            return redirect()
                ->route('owner.withdrawals.index')
                ->with(
                    'success',
                    'Withdrawal berhasil diapprove'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT WITHDRAWAL
    |--------------------------------------------------------------------------
    */

    public function reject(
        Request $request,
        $id
    ) {

        $withdrawal = Withdrawal::findOrFail($id);

        $request->validate([
            'owner_note' =>
                'required|string|max:1000',
        ]);

        if ($withdrawal->status !== 'pending') {

            return back()->with(
                'error',
                'Withdrawal sudah diproses'
            );
        }

$withdrawal->status = 'rejected';
$withdrawal->owner_note = $request->owner_note;
$withdrawal->approved_at = null;
$withdrawal->save();

TicketWalletService::recalculate($withdrawal->event_id);

return redirect()
    ->route('owner.withdrawals.index')
    ->with(
        'success',
        'Withdrawal berhasil ditolak'
    );
    }
}
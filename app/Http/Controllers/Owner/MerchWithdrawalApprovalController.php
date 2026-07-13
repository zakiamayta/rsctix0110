<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// Memanggil Model Utama Finansial Merchandise
use App\Models\MerchWithdrawal; 
use App\Models\MerchWallet; 
use App\Services\MerchWalletService;

class MerchWithdrawalApprovalController extends Controller
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
    | 1. LIST WITHDRAWAL MERCHANDISE
    |--------------------------------------------------------------------------
    */
/*
    |--------------------------------------------------------------------------
    | 1. LIST WITHDRAWAL MERCHANDISE (DENGAN FILTER & SALDO DINAMIS)
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        // A. Ambil semua event yang memiliki wallet merchandise untuk komponen dropdown filter
        $filterEvents = \App\Models\Event::whereHas('merchWallet')->get();

        // B. Buat query dasar untuk penarikan dana (withdrawals)
        $withdrawalQuery = MerchWithdrawal::with(['eo', 'event'])->latest();

        // C. Buat query dasar untuk menghitung total saldo wallet merchandise
        $walletQuery = MerchWallet::query();

        // D. Jika ada filter event_id yang dipilih
        if ($request->filled('event_id')) {
            $eventId = $request->event_id;
            
            // Filter daftar penarikan berdasarkan event tersebut
            $withdrawalQuery->where('event_id', $eventId);
            
            // Filter hitungan saldo hanya untuk event tersebut
            $walletQuery->where('event_id', $eventId);
        }

        // E. Hitung total saldo yang tersedia (Available Balance) secara dinamis
        $totalAvailableBalance = $walletQuery->sum('available_balance');

        // F. Ambil data penarikan dengan pagination
        $withdrawals = $withdrawalQuery->paginate(20)->withQueryString();

        return view(
            'owner.withdrawals.merch',
            compact('withdrawals', 'totalAvailableBalance', 'filterEvents')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 2. DETAIL WITHDRAWAL MERCHANDISE (Menggunakan Pola Struktur Tiket)
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        // Membaca relasi EO dan Event secara aman via Eloquent ORM seperti bagian tiket
        $withdrawal = MerchWithdrawal::with([
            'eo',
            'event'
        ])->findOrFail($id);

        // Mengambil data wallet merchandise berdasarkan event_id terkait
        $wallet = MerchWallet::where(
            'event_id',
            $withdrawal->event_id
        )->first();

        return view(
            'owner.withdrawals.merch-show',
            compact(
                'withdrawal',
                'wallet'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 3. APPROVE WITHDRAWAL MERCHANDISE
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, $id) 
    {
        $withdrawal = MerchWithdrawal::findOrFail($id);

        $request->validate([
            'transfer_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'owner_note'     => 'nullable|string|max:1000',
        ]);

        if ($withdrawal->status !== 'pending') {
            return back()->with(
                'error',
                'Pencairan dana merchandise sudah diproses sebelumnya.'
            );
        }

        DB::beginTransaction();

        try {
            // Memasang lockForUpdate agar saldo tidak bocor saat diproses bersamaan
            $wallet = MerchWallet::where(
                'event_id',
                $withdrawal->event_id
            )
            ->lockForUpdate()
            ->first();

            if (!$wallet) {
                DB::rollBack();
                return back()->with('error', 'Wallet merchandise event tidak ditemukan.');
            }

            if ($wallet->available_balance < $withdrawal->amount) {
                DB::rollBack();
                return back()->with('error', 'Saldo wallet merchandise tidak mencukupi.');
            }

            /* --- UPLOAD BUKTI TRANSFER --- */
            $proofPath = null;

            if ($request->hasFile('transfer_proof')) {

                if (!File::exists(public_path('images/merch_withdrawals'))) {
                    File::makeDirectory(
                        public_path('images/merch_withdrawals'),
                        0755,
                        true
                    );
                }

                $file = $request->file('transfer_proof');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

                $file->move(
                    public_path('images/merch_withdrawals'),
                    $filename
                );

                $proofPath = 'images/merch_withdrawals/' . $filename;
            }

            /* --- POTONG SALDO WALLET --- */
            $wallet->available_balance = $wallet->available_balance - $withdrawal->amount;
            $wallet->save();

            /* --- UPDATE DATA WITHDRAWAL --- */
            $withdrawal->status = 'approved';
            $withdrawal->transfer_proof = $proofPath;
            $withdrawal->owner_note = $request->owner_note;
            $withdrawal->approved_at = now();
            $withdrawal->paid_at = now();
            $withdrawal->save();

            DB::commit();

            TicketWalletService::recalculate($withdrawal->event_id);

            return redirect()
                ->route('owner.withdrawals.merch.index')
                ->with('success', 'Pencairan dana merchandise berhasil disetujui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 4. REJECT WITHDRAWAL MERCHANDISE
    |--------------------------------------------------------------------------
    */
    public function reject(Request $request, $id) 
    {
        $withdrawal = MerchWithdrawal::findOrFail($id);

        $request->validate([
            'owner_note' => 'required|string|max:1000',
        ]);

        if ($withdrawal->status !== 'pending') {
            return back()->with(
                'error',
                'Pencairan dana merchandise sudah diproses sebelumnya.'
            );
        }

        $withdrawal->status = 'rejected';
        $withdrawal->owner_note = $request->owner_note;
        $withdrawal->approved_at = null;
        $withdrawal->save();

        TicketWalletService::recalculate($withdrawal->event_id);

        return redirect()
            ->route('owner.withdrawals.merch.index')
            ->with('success', 'Pengajuan pencairan dana merchandise berhasil ditolak.');
    }
}
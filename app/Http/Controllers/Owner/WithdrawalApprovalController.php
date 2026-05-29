<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Withdrawal;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
    | LIST WITHDRAWAL
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | LOAD EO + DATA REKENING EO
        |--------------------------------------------------------------------------
        */

        $withdrawals = Withdrawal::with('eo')
            ->latest()
            ->get();

        return view(
            'owner.withdrawals.index',
            compact('withdrawals')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL
    |--------------------------------------------------------------------------
    */

    public function show(Withdrawal $withdrawal)
    {
        /*
        |--------------------------------------------------------------------------
        | LOAD RELATION EO
        |--------------------------------------------------------------------------
        */

        $withdrawal->load('eo');

        return view(
            'owner.withdrawals.show',
            compact('withdrawal')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE WITHDRAWAL
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        Withdrawal $withdrawal
    ) {
        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'transfer_proof' => 'required|image|max:4096',
            'note'           => 'nullable|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CEK STATUS
        |--------------------------------------------------------------------------
        */

        if ($withdrawal->status === 'approved') {

            return back()->with(
                'error',
                'Withdrawal sudah diapprove'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UPLOAD BUKTI TRANSFER
        |--------------------------------------------------------------------------
        */

        $proofPath = null;

        if ($request->hasFile('transfer_proof')) {

            $file = $request->file('transfer_proof');

            /*
            |--------------------------------------------------------------------------
            | BUAT FOLDER JIKA BELUM ADA
            |--------------------------------------------------------------------------
            */

            if (!File::exists(public_path('images/withdrawals'))) {

                File::makeDirectory(
                    public_path('images/withdrawals'),
                    0755,
                    true
                );
            }

            $filename = Str::uuid() . '.'
                . $file->getClientOriginalExtension();

            $file->move(
                public_path('images/withdrawals'),
                $filename
            );

            $proofPath = 'images/withdrawals/' . $filename;
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE WITHDRAWAL
        |--------------------------------------------------------------------------
        */

        $withdrawal->update([
            'status'          => 'approved',
            'transfer_proof'  => $proofPath,
            'note'            => $request->note,
            'approved_at'     => now(),
        ]);

        return redirect()
            ->route('owner.withdrawals.index')
            ->with(
                'success',
                'Withdrawal berhasil diapprove'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT WITHDRAWAL
    |--------------------------------------------------------------------------
    */

    public function reject(
        Request $request,
        Withdrawal $withdrawal
    ) {
        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'note' => 'required|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | HAPUS BUKTI TRANSFER JIKA ADA
        |--------------------------------------------------------------------------
        */

        if (
            $withdrawal->transfer_proof &&
            file_exists(public_path($withdrawal->transfer_proof))
        ) {
            File::delete(
                public_path($withdrawal->transfer_proof)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE STATUS
        |--------------------------------------------------------------------------
        */

        $withdrawal->update([
            'status'          => 'rejected',
            'note'            => $request->note,
            'approved_at'     => null,
            'transfer_proof'  => null,
        ]);

        return redirect()
            ->route('owner.withdrawals.index')
            ->with(
                'success',
                'Withdrawal berhasil ditolak'
            );
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransactionMerch;
use Illuminate\Http\Request;

class AbsenMerchController extends Controller
{
    public function show($kode_unik)
    {
        $transaction = TransactionMerch::where('kode_unik', $kode_unik)
            ->with('details.product')
            ->firstOrFail();

        return view('admin.absen.form-merch', compact('transaction'));
    }

    public function verify(Request $request, $kode_unik)
    {
        $request->validate(['password' => 'required']);
        $passwordBenar = env('ABSEN_MERCH_PASS', 'merch123');

        if ($request->password === $passwordBenar) {
            session(['absen_verified' => true]);
            return redirect()->route('admin.absen.form-merch', $kode_unik);
        }

        return back()->with('error', 'Password salah!');
    }

    public function store($kode_unik)
    {
        $transaction = TransactionMerch::where('kode_unik', $kode_unik)->firstOrFail();
        $transaction->update(['is_absen' => true]);

        session()->forget('absen_verified');
        return back();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class AbsensiController extends Controller
{
public function showPasswordForm($kode)
{
    $transaction = Transaction::where('kode_unik', $kode)->first();

    if (!$transaction) {
        abort(404);
    }

    return view('absen.form', compact('transaction'));
}


public function handleScan(Request $request, $kode)
{
    // Validasi input password
    $request->validate([
        'password' => 'required',
    ]);

    if ($request->password !== config('app.gate_password', 'gate123')) {
        return back()->with('error', 'Password salah.');
    }

    // Ambil transaksi berdasarkan kode_unik
    $transaction = Transaction::where('kode_unik', $kode)->first();

    if (!$transaction) {
        return back()->with('error', 'Transaksi tidak ditemukan.');
    }

    if ($transaction->is_registered) {
        return view('absen.warning', [
            'message' => 'Anda sudah melakukan registrasi.'
        ]);
    }

    $transaction->is_registered = true;
    $transaction->registered_at = now();
    $transaction->save();

    $details = DB::table('ticket_attendees')
        ->where('transaction_id', $transaction->id)
        ->get();

    return view('absen.success', compact('transaction', 'details'));
}

}

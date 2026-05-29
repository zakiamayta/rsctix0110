<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EoController extends Controller
{
    public function create()
{
    $banks = array_merge(
        config('banks.primary'),
        config('banks.others')
    );

    return view('eo.register', compact('banks'));
}
    public function store(Request $request)
    {
        Log::info('EO Register: Request masuk', [
            'user_id' => Auth::guard('user')->id()
        ]);

        try {

            // VALIDASI
            $request->validate([
                'nama_badan_usaha'      => 'required|string|max:255',
                'alamat_badan_usaha'    => 'required|string',
                'penanggung_jawab'      => 'required|string|max:255',
                'dokumen_badan_usaha' => 'required|file|mimes:pdf|max:10240', // 10MB
                'ktp_penanggung_jawab' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'bank_name'       => 'required|string|max:100',
                'account_name'    => 'required|string|max:150',
                'account_number'  => 'required|string|max:100',
            ]);

            $userId = Auth::guard('user')->id();

            Log::info('EO Register: Validasi lolos', ['user_id' => $userId]);

            // CEK SUDAH PUNYA EO
            $existing = DB::table('eo')->where('user_id', $userId)->first();

            if ($existing) {
                Log::warning('EO Register: User sudah punya EO', [
                    'user_id' => $userId,
                    'eo_id'   => $existing->id
                ]);

                return redirect()->route('home')
                    ->with('error', 'Kamu sudah terdaftar sebagai Event Organizer');
            }

            // UPLOAD FILE
            $dokumenPath = null;
            $ktpPath = null;

            if ($request->hasFile('dokumen_badan_usaha')) {

                $file = $request->file('dokumen_badan_usaha');

                // 🔥 VALIDASI EXTENSION (double safety selain validator)
                if ($file->getClientOriginalExtension() !== 'pdf') {
                    return back()->withErrors([
                        'dokumen_badan_usaha' => 'Dokumen harus berupa PDF'
                    ]);
                }

                // 🔥 NAMA FILE LEBIH RAPI
                $filename = 'dokumen_' . time() . '.pdf';

                // 🔥 SIMPAN FILE
                $file->move(public_path('uploads/eo'), $filename);

                $dokumenPath = 'uploads/eo/' . $filename;

                Log::info('EO Register: Upload dokumen berhasil', [
                    'path' => $dokumenPath
                ]);
            }
            if ($request->hasFile('ktp_penanggung_jawab')) {
                $file = $request->file('ktp_penanggung_jawab');
                $filename = 'ktp_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/eo'), $filename);
                $ktpPath = 'uploads/eo/' . $filename;

                Log::info('EO Register: Upload KTP berhasil', [
                    'path' => $ktpPath
                ]);
            }

            // SIMPAN DATA
            DB::table('eo')->insert([
                'user_id'                => $userId,
                'nama_badan_usaha'       => $request->nama_badan_usaha,
                'alamat_badan_usaha'     => $request->alamat_badan_usaha,
                'dokumen_badan_usaha'    => $dokumenPath,
                'penanggung_jawab'       => $request->penanggung_jawab,
                'ktp_penanggung_jawab'   => $ktpPath,
                'bank_name'        => $request->bank_name,
                'account_name'     => $request->account_name,
                'account_number'   => $request->account_number,
                'status'                 => 'pending',
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            Log::info('EO Register: Data EO berhasil disimpan', [
                'user_id' => $userId
            ]);

            return redirect()->route('eo.waiting')
                ->with('success', 'Pendaftaran EO berhasil! Menunggu verifikasi.');

        } catch (\Exception $e) {

            Log::error('EO Register ERROR', [
                'user_id' => Auth::guard('user')->id(),
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile()
            ]);

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan, coba lagi');
        }
    }

    public function profile()
{
    $user = auth('user')->user();

    $eo = \App\Models\Eo::where('user_id', $user->id)->first();

    return view('eo.profile', compact('eo'));
}

public function updateProfile(Request $request)
{
    $user = auth('user')->user();

    $eo = \App\Models\Eo::where('user_id', $user->id)->first();

    $request->validate([
        'nama_badan_usaha' => 'required',
        'phone' => 'required',
        'bank_name' => 'nullable',
        'account_name' => 'nullable',
        'account_number' => 'nullable',
        'logo' => 'nullable|image|max:2048'
    ]);

    /*
    |--------------------------------------------------------------------------
    | UPDATE NOMOR HP KE TABEL USERS
    |--------------------------------------------------------------------------
    */

    $user->phone = $request->phone;
    $user->save();

    /*
    |--------------------------------------------------------------------------
    | UPLOAD LOGO
    |--------------------------------------------------------------------------
    */

    if ($request->hasFile('logo')) {

        $file = $request->file('logo');

        $filename = time().'_'.$file->getClientOriginalName();

        $file->move(public_path('uploads/eo'), $filename);

        $eo->logo = 'uploads/eo/'.$filename;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE DATA EO
    |--------------------------------------------------------------------------
    */

    $eo->nama_badan_usaha = $request->nama_badan_usaha;

    $eo->bank_name = $request->bank_name;
    $eo->account_name = $request->account_name;
    $eo->account_number = $request->account_number;

    $eo->save();

    return back()->with(
        'success',
        'Profil EO berhasil diperbarui'
    );
}
}
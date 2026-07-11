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

            // CEK EO YANG SUDAH ADA
            $existing = DB::table('eo')->where('user_id', $userId)->first();

            if ($existing && in_array($existing->status, ['pending', 'approved'])) {
                Log::warning('EO Register: User sudah punya EO aktif', [
                    'user_id' => $userId,
                    'eo_id'   => $existing->id,
                    'status'  => $existing->status,
                ]);

                return redirect()->route('home')
                    ->with('error', 'Kamu sudah terdaftar sebagai Event Organizer');
            }

            // UPLOAD FILE
            $dokumenPath = null;
            $ktpPath = null;

            if ($request->hasFile('dokumen_badan_usaha')) {

                $file = $request->file('dokumen_badan_usaha');

                if ($file->getClientOriginalExtension() !== 'pdf') {
                    return back()->withErrors([
                        'dokumen_badan_usaha' => 'Dokumen harus berupa PDF'
                    ]);
                }

                $filename = 'dokumen_' . time() . '.pdf';

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
            $data = [
                'nama_badan_usaha'      => $request->nama_badan_usaha,
                'alamat_badan_usaha'    => $request->alamat_badan_usaha,
                'dokumen_badan_usaha'   => $dokumenPath,
                'penanggung_jawab'      => $request->penanggung_jawab,
                'ktp_penanggung_jawab'  => $ktpPath,
                'bank_name'             => $request->bank_name,
                'account_name'          => $request->account_name,
                'account_number'        => $request->account_number,
                'status'                => 'pending',
                'rejected_reason'       => null,
                'updated_at'            => now(),
            ];

            if ($existing && $existing->status === 'rejected') {
                // Daftar ulang: update record lama, bukan insert baru
                DB::table('eo')->where('id', $existing->id)->update($data);

                Log::info('EO Register: Daftar ulang setelah ditolak', [
                    'user_id' => $userId,
                    'eo_id'   => $existing->id
                ]);
            } else {
                // Daftar baru
                $data['user_id']    = $userId;
                $data['created_at'] = now();

                DB::table('eo')->insert($data);

                Log::info('EO Register: Data EO berhasil disimpan', [
                    'user_id' => $userId
                ]);
            }

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
        $user = auth()->user();

        $eo = \App\Models\Eo::where('user_id', $user->id)->first();

        return view('eo.profile', compact('eo'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $eo = \App\Models\Eo::where('user_id', $user->id)->first();

        if (!$eo) {
            return back()->with('error', 'Data EO tidak ditemukan.');
        }

        // 🔍 DEBUG SEMENTARA — hapus/comment lagi setelah masalah logo terkonfirmasi selesai
        Log::info('DEBUG UPDATE PROFIL EO', [
            'user_id'   => $user->id,
            'has_file'  => $request->hasFile('logo'),
            'all_files' => $request->allFiles(),
        ]);

        $request->validate([
            'nama_badan_usaha' => 'required|string|max:255',
            'phone'            => 'required|string|max:20',
            'bank_name'        => 'nullable|string|max:100',
            'account_name'     => 'nullable|string|max:150',
            'account_number'   => 'nullable|string|max:100',
            // 🔧 Rule diperjelas pakai "mimes" (bukan hanya "image") supaya webp/format
            // dari HP tidak ditolak diam-diam oleh rule "image" bawaan Laravel.
            'logo'             => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
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

            // Pastikan folder tujuan ada (jaga-jaga kalau belum pernah dibuat)
            if (!file_exists(public_path('uploads/eo'))) {
                mkdir(public_path('uploads/eo'), 0755, true);
            }

            // Hapus logo lama supaya tidak menumpuk file sampah
            if ($eo->logo && file_exists(public_path($eo->logo))) {
                @unlink(public_path($eo->logo));
            }

            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            $file->move(public_path('uploads/eo'), $filename);

            $eo->logo = 'uploads/eo/' . $filename;

            Log::info('DEBUG LOGO TERSIMPAN', ['path' => $eo->logo]);
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

    public function waiting()
{
    $eo = DB::table('eo')->where('user_id', auth()->id())->first();
    return view('eo.waiting', compact('eo'));
}
}   
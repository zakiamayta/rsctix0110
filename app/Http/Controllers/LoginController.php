<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required'
    ]);

    // 1. Cari user berdasarkan email
    $user = User::where('email', $request->email)->first();

    // 2. JIKA USER BELUM TERDAFTAR -> Buat akun baru otomatis
    if (!$user) {
        $user = User::create([
            'email' => $request->email,
            'PASSWORD' => Hash::make($request->password), // Sesuaikan nama kolom password Anda
            'role' => 'user', // Default role untuk pendaftar baru
            'profile_complete' => 0, // Tandai belum lengkap
        ]);

        Log::info('Akun baru berhasil dibuat otomatis lewat form', ['user_id' => $user->id]);
    } else {
        // 3. JIKA USER SUDAH ADA -> Cek passwordnya
        if (!Hash::check($request->password, $user->PASSWORD)) {
            Log::warning('Login gagal, password salah', ['email' => $request->email]);
            return back()->withErrors(['login' => 'Email atau password salah.'])->withInput();
        }
    }

    // 4. Login menggunakan Guard 'user' (agar sinkron dengan ProfileController)
    Auth::login($user);
    $request->session()->regenerate();

    Log::info('Login success', [
        'user_id' => $user->id,
        'role'    => $user->role
    ]);

    // 5. Cek kelengkapan profil terlebih dahulu sebelum masuk ke dashboard/home
    return redirect()->route('profile.complete');

    // 6. Alur redirect berdasarkan role jika profil sudah lengkap
    switch ($user->role) {
        case 'admin':
            return redirect()->route('admin.dashboard');
        case 'owner':
            return redirect()->route('owner.dashboard');
        case 'eo':
            $eo = \DB::table('eo')->where('user_id', $user->id)->first();
            if (!$eo || $eo->status !== 'approved') {
                return redirect()->route('eo.waiting');
            }
            return redirect()->route('eo.dashboard');
        default:
            return redirect('/'); // User biasa ke halaman utama
    }
}

public function logout(Request $request)
{
    // 1. Logout dari semua guard yang mungkin aktif
    Auth::guard('user')->logout();
    Auth::logout();

    // 2. Bersihkan seluruh data session di server
    $request->session()->flush(); 
    $request->session()->invalidate();

    // 3. Buat ulang token CSRF agar session lama mati total
    $request->session()->regenerateToken();

    // 4. Hapus cookie session secara paksa dari browser
    return redirect()->route('login')
        ->with('success', 'Anda berhasil logout.')
        ->withCookie(\Cookie::forget('laravel_session')); // Sesuaikan dengan nama cookie session Anda jika diubah
}
}

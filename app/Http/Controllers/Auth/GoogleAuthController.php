<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            return redirect('/login')
                ->with('error', 'Login Google gagal, silakan coba lagi.');
        }

        // ─── PERBAIKAN LOGIKA: Pisahkan Create dan Update secara eksplisit ───
        // 1. Cari user berdasarkan email terlebih dahulu
        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            // 2. Jika user BELUM ADA, buat baru dengan role default 'user'
            $user = User::create([
                'email'            => $googleUser->getEmail(),
                'google_id'        => $googleUser->getId(),
                'name'             => $googleUser->getName(),
                'avatar'           => str_replace('=s96-c', '=s200-c', $googleUser->getAvatar()),
                'role'             => 'user', // Aman tersimpan sebagai string murni
                'profile_complete' => 0,
            ]);
        } else {
            // 3. Jika user SUDAH ADA (bisa saja Admin/Owner), UPDATE data Google-nya saja
            // TANPA MENYENTUH ATAU MENIMPA KOLOM 'role' MEREKA!
            $user->update([
                'google_id' => $googleUser->getId(),
                'name'      => $googleUser->getName(),
                'avatar'    => str_replace('=s96-c', '=s200-c', $googleUser->getAvatar()),
            ]);
        }

        // Autentikasi ke DUA guard yang dipakai aplikasi
        Auth::login($user);
        Auth::guard('user')->login($user);

        request()->session()->regenerate();
        request()->session()->save();

        /*
        =========================================
        1. VALIDASI: USER BIASA
        =========================================
        */
        if ($user->role === 'user') {
            if ($user->profile_complete == 0) {
                session()->forget('url.intended'); 
                return redirect()->to('/complete-profile');
            }

            if (session()->has('url.intended')) {
                return redirect()->intended('/');
            }

            return redirect()->to('/');
        }

        /*
        =========================================
        2. VALIDASI: OWNER
        =========================================
        */
        if ($user->role === 'owner') {
            return redirect()->route('owner.dashboard');
        }

        /*
        =========================================
        3. VALIDASI: ADMIN
        =========================================
        */
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        /*
        =========================================
        4. VALIDASI: EO
        =========================================
        */
        if ($user->role === 'eo') {
            $eo = DB::table('eo')
                ->where('user_id', $user->id)
                ->first();

            if (!$eo || $eo->status !== 'approved') {
                return redirect()->route('eo.waiting');
            }

            if (session()->has('url.intended')) {
                return redirect()->intended('/');
            }

            return redirect()->to('/');
        }

        // Fallback terakhir
        return redirect()->to('/');
    }
}
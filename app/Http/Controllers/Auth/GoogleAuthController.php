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

        // 🎯 PERBAIKAN 1: Pastikan kolom 'role' diberi nilai default jika user baru dibuat
        $user = User::updateOrCreate(
            [
                'email' => $googleUser->getEmail()
            ],
            [
                'google_id' => $googleUser->getId(),
                'name'      => $googleUser->getName(),
                'avatar'    => str_replace('=s96-c', '=s200-c', $googleUser->getAvatar()),
                // Jika user baru (belum punya role di DB), otomatis jadikan 'user'
                'role'      => DB::raw('IFNULL(role, "user")') 
            ]
        );

        // Jika DB::raw tidak bersahabat dengan properti fillable Anda, pakai fallback manual ini:
        if (!$user->role) {
            $user->role = 'user';
            $user->save();
        }

        Auth::login($user);

        request()->session()->regenerate();
        request()->session()->save();

        /*
        =========================================
        1. VALIDASI: USER BIASA (Harus Melengkapi Profil)
        =========================================
        */
        if ($user->role === 'user') {
            // Jika profil belum lengkap (0), kunci jalan ke halaman /complete-profile
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

        // Fallback terakhir jika terjadi sesuatu yang aneh dengan role
        return redirect()->to('/');
    }
}
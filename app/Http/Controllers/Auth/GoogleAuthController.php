<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect ke Google
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Callback dari Google
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect('/loginuser')->with('error', 'Login Google gagal.');
        }

        // cari atau buat user
        $user = User::updateOrCreate(
            [
                'email' => $googleUser->getEmail()
            ],
            [
                'google_id' => $googleUser->getId(),
                'name'      => $googleUser->getName(),
                'avatar' => str_replace('=s96-c', '=s200-c', $googleUser->getAvatar()),

            ]
        );

        /**
         * LOGIN KHUSUS USER GOOGLE
         * TIDAK pakai auth default (admin)
         */
        Auth::guard('user')->login($user);

        /**
         * JANGAN langsung ke homepage
         */
        if ($user->profile_complete == 0) {
            return redirect('/complete-profile');
        }

        return redirect('/');
    }
}

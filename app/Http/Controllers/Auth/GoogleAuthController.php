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

        return redirect('/')
            ->with('error','Login Google gagal');
    }

    $user = User::updateOrCreate(
        [
            'email' => $googleUser->getEmail()
        ],
        [
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName(),
            'avatar' => str_replace(
                '=s96-c',
                '=s200-c',
                $googleUser->getAvatar()
            )
        ]
    );

    Auth::login($user);

    /*
    Kalau sebelumnya dipaksa login karena session habis,
    kembali ke halaman yang tadi dibuka
    */
    if (session()->has('url.intended')) {
        return redirect()->intended('/');
    }

    /*
    USER BIASA
    */
    if ($user->role === 'user') {

        if ($user->profile_complete == 0) {
            return redirect('/complete-profile');
        }

        return redirect('/');
    }

    /*
    OWNER
    */
    if ($user->role === 'owner') {
        return redirect()->route('owner.dashboard');
    }

    /*
    ADMIN
    */
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    /*
    EO
    */
    if ($user->role === 'eo') {

        $eo = DB::table('eo')
            ->where('user_id', $user->id)
            ->first();

        if (!$eo || $eo->status !== 'approved') {
            return redirect()->route('eo.waiting');
        }

        return redirect('/');
    }

    return redirect('/');
}
}
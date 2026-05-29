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

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->PASSWORD)) {

        Log::warning('Login gagal', [
            'email' => $request->email
        ]);

        return back()->withErrors(['login' => 'Email atau password salah.'])->withInput();
    }

    Auth::login($user);

    Log::info('Login success', [
        'user_id' => $user->id,
        'role'    => $user->role
    ]);

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
            Auth::logout();
            abort(403, 'Role tidak dikenali');
    }
}


    public function logout(Request $request)
{
    Auth::logout();

    $request->session()->invalidate();       // Clear session
    $request->session()->regenerateToken();  // Prevent CSRF attack after logout

    return redirect()->route('login')->with('success', 'Anda berhasil logout.');
}

}

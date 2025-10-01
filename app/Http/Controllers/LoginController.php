<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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
            return back()->withErrors(['login' => 'Email atau password salah.'])->withInput();
        }

        Auth::login($user);
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
{
    Auth::logout();

    $request->session()->invalidate();       // Clear session
    $request->session()->regenerateToken();  // Prevent CSRF attack after logout

    return redirect()->route('login')->with('success', 'Anda berhasil logout.');
}

}

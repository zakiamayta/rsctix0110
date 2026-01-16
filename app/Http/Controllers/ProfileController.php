<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        if (Auth::user()->profile_complete) {
            return redirect('/dashboard');
        }

        return view('auth.complete-profile');
    }

    public function update(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => 'required|string|max:20',
            'birth_date' => 'required|date',
            'gender'     => 'nullable|in:male,female',
        ]);

        $user = Auth::user();

        $user->update([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'phone'      => $request->phone,
            'birth_date' => $request->birth_date,
            'gender'     => $request->gender,
            'profile_complete' => 1,
        ]);

        return redirect('/dashboard');
    }
}

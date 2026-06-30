<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        Log::info('OPEN COMPLETE PROFILE PAGE', [
            'user_id' => $user?->id,
            'email' => $user?->email,
        ]);

        // 🎯 PERBAIKAN 1: Amankan jika user ternyata belum login / session habis
        if (!$user) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if ($user->profile_complete == 1) {
            return redirect('/');
        }

        return view('auth.complete-profile');
    }

    public function update(Request $request)
    {
        Log::info('SUBMIT COMPLETE PROFILE', [
            'request' => $request->all()
        ]);

        try {
            $request->validate([
                'name'       => 'required|string|max:100',
                'phone'      => 'required|string|max:20',
                'birth_date' => 'required|date',
                'gender'     => 'nullable|in:male,female',
            ]);

            $user = Auth::user();

            // 🎯 PERBAIKAN 2: Amankan juga proses update dari user anonim
            if (!$user) {
                return redirect()->route('login')->with('error', 'Sesi Anda telah habis.');
            }

            Log::info('USER BEFORE UPDATE', [
                'id' => $user->id,
                'profile_complete' => $user->profile_complete
            ]);

            $updated = $user->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'profile_complete' => 1,
            ]);

            Log::info('UPDATE RESULT', [
                'success' => $updated
            ]);

            return redirect('/')
                ->with('success', 'Profil berhasil dilengkapi');

        } catch (\Throwable $e) {
            Log::error('FAILED COMPLETE PROFILE', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return back()
                ->with('error', 'Gagal menyimpan profil');
        }
    }
}
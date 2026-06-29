<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Google_Client;

class GoogleMobileController extends Controller
{
    public function logo()
    {
        $path = base_path('public_html/logoRSC.png');

        if (!file_exists($path)) {
            abort(404, 'File tidak ditemukan');
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
        ]);
    }

    /// LOGIN GOOGLE
    public function login(Request $request)
    {
        $request->validate([
            'id_token' => 'required'
        ]);

        $client = new Google_Client([
            'client_id' => env('GOOGLE_CLIENT_ID')
        ]);

        $payload = $client->verifyIdToken($request->id_token);

        if (!$payload) {
            return response()->json([
                'message' => 'Token tidak valid'
            ], 401);
        }

        $email = $payload['email'];
        $name = $payload['name'];
        $googleId = $payload['sub'];
        $avatar = $payload['picture'] ?? null;

        /// CREATE / UPDATE USER
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Jika user baru daftar lewat google, default role tetap 'user'
            $user = User::create([
                'email'     => $email,
                'google_id' => $googleId,
                'name'      => $name,
                'avatar'    => $avatar,
                'role'      => 'user', 
            ]);
        } else {
            // Jika user sudah ada, cukup update data google info terbarunya
            $user->update([
                'google_id' => $googleId,
                'name'      => $name,
                'avatar'    => $avatar,
            ]);
        }

        /// GENERATE TOKEN
        $token = $user->createToken('mobile')->plainTextToken;

        /// CHECK PROFILE COMPLETE
        $isComplete = !empty($user->phone) &&
                      !empty($user->birth_date) &&
                      !empty($user->gender);

        // Ambil data EO jika diperlukan untuk kebutuhan internal fitur di dalam MainNavigation
        $eo = DB::table('eo')->where('user_id', $user->id)->first();

        return response()->json([
            'user' => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'phone'            => $user->phone,
                'birth_date'       => $user->birth_date,
                'gender'           => $user->gender,
                'avatar'           => $user->avatar,
                'profile_complete' => (bool)$user->profile_complete,
                'role'             => $user->role, // Flutter akan membaca role ini ('owner', 'eo', atau 'user')
            ],
            'token'               => $token,
            'is_profile_complete' => $isComplete,
            
            // Flag pendukung untuk fitur di dalam aplikasi (jika role dia owner atau punya data di tabel eo)
            'is_eo'               => ($user->role === 'owner' || $eo) ? true : false,
            'eo_id'               => $eo?->id,
            'eo_status'           => $eo?->status ?? ($user->role === 'owner' ? 'active' : null),
        ]);
    }

    /// GET PROFILE
    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    /// UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
        ]);

        $user = $request->user();

        $profileComplete =
            !empty($request->phone) &&
            !empty($request->birth_date) &&
            !empty($request->gender);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'profile_complete' => $profileComplete,
        ]);

        return response()->json([
            'message' => 'Profile berhasil diperbarui',
            'user' => $user
        ]);
    }

    /// LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    
}
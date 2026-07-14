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
        // NOTE: DB::table() adalah query builder mentah (bukan Eloquent model), jadi TIDAK
        // menjalankan $casts apapun. Tipe data yang balik dari MySQL bisa berbeda antara
        // environment lokal (SQLite/driver dev) vs hosting production (MySQL asli di Domainesia).
        // Karena itu SEMUA field yang dikirim ke Flutter WAJIB di-cast eksplisit di bawah ini,
        // supaya SharedPreferences.setInt/setString/setBool di sisi Flutter tidak throw
        // "type 'X' is not a subtype of type 'Y'" saat build release.
        $eo = DB::table('eo')->where('user_id', $user->id)->first();

        return response()->json([
            'user' => [
                'id'               => (int) $user->id,
                'name'             => (string) ($user->name ?? ''),
                'email'            => (string) ($user->email ?? ''),
                'phone'            => $user->phone !== null ? (string) $user->phone : null,
                'birth_date'       => $user->birth_date !== null ? (string) $user->birth_date : null,
                'gender'           => $user->gender !== null ? (string) $user->gender : null,
                'avatar'           => $user->avatar !== null ? (string) $user->avatar : null,
                'profile_complete' => (bool) $user->profile_complete,
                'role'             => (string) ($user->role ?? 'user'), // Flutter akan membaca role ini ('owner', 'eo', atau 'user')
            ],
            'token'               => (string) $token,
            'is_profile_complete' => (bool) $isComplete,

            // Flag pendukung untuk fitur di dalam aplikasi (jika role dia owner atau punya data di tabel eo)
            'is_eo'               => (bool) (($user->role === 'owner' || $eo) ? true : false),

            // eo_id dipaksa integer murni agar tidak dibaca String oleh Flutter Release
            'eo_id'               => $eo ? (int) $eo->id : null,

            // eo_status dipaksa string murni (fix bug sama seperti eo_id di atas —
            // sebelumnya field ini tidak di-cast dan menyebabkan error type mismatch
            // di SharedPreferences.setString saat login sebagai EO di flutter build apk release)
            'eo_status'           => $eo && $eo->status !== null
                                        ? (string) $eo->status
                                        : ($user->role === 'owner' ? 'active' : null),
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
            !empty($request->name) && // Tambahan validasi jika name wajib diisi saat lengkap
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
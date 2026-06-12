<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OwnerDashboardController extends Controller
{
    /**
     * Mengambil data khusus halaman Owner beserta data statistik approval terupdate.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Proteksi ketat: Hanya role 'owner' yang lolos
        if ($user->role !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Halaman ini khusus Owner.'
            ], 403);
        }

        // =====================================================================
        // GENERATE FULL URL AVATAR AGAR BISA DIBACA FLUTTER (GOOGLE AUTH FRIENDLY)
        // =====================================================================
        $avatarUrl = null;
        if ($user->avatar) {
            // Jika berisi URL Google (http:// atau https://), langsung gunakan string tersebut
            if (filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                $avatarUrl = $user->avatar;
            } else {
                // Jika suatu saat ada user non-google upload foto lokal, dibungkus asset()
                $avatarUrl = asset($user->avatar); 
            }
        }

        // =====================================================================
        // QUERY STATISTIK REAL-TIME
        // =====================================================================
        $pendingEo = 0;
        if (Schema::hasTable('eo')) {
            $pendingEo = DB::table('eo')->where('status', 'pending')->count();
        }

        $pendingEvents = DB::table('events')->where('status', 'pending')->count();

        $pendingDataChanges = DB::table('events')
            ->whereIn('status', ['pending_cancel', 'pending_reschedule'])
            ->count();

        $pendingTicketWithdraws = DB::table('withdrawals')->where('status', 'pending')->count();
        $pendingMerchWithdraws = DB::table('merch_withdrawals')->where('status', 'pending')->count();
        $totalPendingWithdraws = $pendingTicketWithdraws + $pendingMerchWithdraws;

        // =====================================================================
        // RESPONSE JSON UNTUK FLUTTER
        // =====================================================================
        return response()->json([
            'success' => true,
            'message' => 'Berhasil memuat data dashboard owner',
            'user' => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'role'             => $user->role,
                'avatar'           => $avatarUrl, // Mengembalikan URL murni Google / Asset lokal
                'profile_complete' => (bool) $user->profile_complete,
            ],
            'statistics' => [
                'pending_eo'           => (int) $pendingEo,
                'pending_events'       => (int) $pendingEvents,
                'pending_data_changes' => (int) $pendingDataChanges,
                'pending_withdraws'    => (int) $totalPendingWithdraws,
            ]
        ], 200);
    }
}
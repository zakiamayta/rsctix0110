<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckEoApproved
{
    public function handle($request, Closure $next)
    {
        $userId = Auth::guard('user')->id();

        $eo = DB::table('eo')
            ->where('user_id', $userId)
            ->first();

        if (!$eo) {
            return redirect()->route('eo.register')
                ->with('error', 'Daftar EO dulu');
        }

        if ($eo->status === 'pending') {
            return redirect()->route('eo.waiting');
        }

        if ($eo->status === 'rejected') {
            return redirect()->route('eo.register')
                ->with('error', 'Pengajuan EO ditolak');
        }

        return $next($request);
    }
}
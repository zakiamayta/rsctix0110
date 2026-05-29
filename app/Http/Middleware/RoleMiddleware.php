<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::guard('web')->user();

        // LOG
        Log::info('RoleMiddleware check', [
            'user_id' => $user?->id,
            'role'    => $user?->role,
            'allowed' => $roles
        ]);

        // BELUM LOGIN
        if (!$user) {
            Log::warning('Unauthorized: user not logged in');
            return redirect()->route('login');
        }

        // CEK ROLE
        if (!in_array($user->role, $roles)) {
            Log::warning('Unauthorized role access', [
                'user_role' => $user->role,
                'allowed'   => $roles
            ]);

            abort(403, 'Akses ditolak');
        }

        return $next($request);
    }
}
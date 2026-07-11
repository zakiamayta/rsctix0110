<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // 🔥 TAMBAHKAN BARIS INI AGAR SESSION COOKIE TIDAK HILANG
        $middleware->trustProxies(at: '*');

    })
    ->withExceptions(function (Exceptions $exceptions) {

        // 🔥 TANGANI ERROR 419 PAGE EXPIRED (CSRF TOKEN MISMATCH)
        // Terjadi saat session sudah expired di server tapi form masih membawa token lama.
        // Daripada munculkan halaman 419 mentah, paksa logout bersih lalu arahkan ke home.
        $exceptions->renderable(function (TokenMismatchException $e, $request) {
            \Log::info('TokenMismatchException tertangkap!');
            Auth::guard('user')->logout();
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')
                ->with('warning', 'Sesi Anda telah berakhir karena tidak aktif dalam waktu lama. Silakan login kembali.');
        });

    })->create();
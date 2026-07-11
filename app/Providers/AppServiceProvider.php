<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Services\BuyerNotificationService;
use Xendit\Xendit;

class AppServiceProvider extends ServiceProvider
{
    
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Kode bawaan Anda: Memaksa HTTPS agar CSS muncul di ngrok
        if (str_contains(config('app.url'), 'ngrok-free.dev')) {
            URL::forceScheme('https');
        }

        // 2. DETEKSI GLOBAL: Cek keputusan merchandise untuk event yang batal (Tanpa Middleware)
        view()->composer('*', function ($view) {
            
            // Pastikan user sudah login dan rolenya adalah EO
            if (auth()->check() && auth()->user()->role === 'eo') {
                
                $eo = DB::table('eo')->where('user_id', auth()->id())->first();

                if ($eo) {
                    // CARI GLOBAL: Apakah ada event resmi batal (cancelled) tapi keputusan merchnya masih NULL?
                    $pendingMerchEvent = Event::where('eo_id', $eo->id)
                        ->where('status', 'cancelled')
                        ->whereNull('merch_cancel_decision')
                        ->whereHas('products', function ($q) {
                            $q->where('type', 'merch');
                        })
                        ->first();

                    // Jika ditemukan event menggantung, lempar data ke layout blade secara otomatis
                    if ($pendingMerchEvent) {
                        $view->with('globalPendingMerchEvent', $pendingMerchEvent);
                    }
                }
            }
        });

        // 3. NOTIFIKASI PEMBELI: Suntik daftar notifikasi aktivitas ke navbar
        //    (hanya dihitung untuk user yang login, dan hanya saat navbar dirender)
        view()->composer('layouts.navbar', function ($view) {
            $notifications = auth()->check()
                ? BuyerNotificationService::for(auth()->user())
                : [];

            $view->with('buyerNotifications', $notifications);
        });
    }
}
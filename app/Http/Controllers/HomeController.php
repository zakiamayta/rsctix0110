<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Event;
use App\Models\Product;

class HomeController extends Controller
{

public function __construct()
    {
        // Jalankan filter otomatis untuk setiap request yang masuk ke HomeController
        $this->middleware(function ($request, $next) {
            
            // Cek apakah user menggunakan guard 'user' dan sudah login
            if (Auth::guard('user')->check()) {
                $user = Auth::guard('user')->user();
                
                // Jika profile_complete masih 0, tendang ke halaman profil
                if ($user && $user->profile_complete == 0) {
                    return redirect()->route('profile.complete')
                        ->with('warning', 'Lengkapi profil Anda terlebih dahulu.');
                }
            }

            return $next($request);
        });
    }

    public function index()
    {
        // ✅ HANYA tampilkan event yang sudah di-approve
        $events = Event::where('status', 'approved')
            ->orderBy('date')
            ->get();

        $tickets = Product::where('type', 'ticket')->get();
        $merchandise = Product::where('type', 'merch')->get();

        return view('home', compact('events', 'tickets', 'merchandise'));
    }
}
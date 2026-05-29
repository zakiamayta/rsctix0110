<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Event;

class OwnerController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            // cek login
            if (!auth()->check()) {
                return redirect()->route('login');
            }

            // hanya owner
            if (auth()->user()->role !== 'owner') {
                abort(403, 'Akses hanya untuk owner');
            }

            return $next($request);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | OWNER DASHBOARD
    |--------------------------------------------------------------------------
    */

   public function dashboard()
{
    // =========================
    // EO
    // =========================

    $totalEO = DB::table('eo')->count();

    $approvedEO = DB::table('eo')
        ->where('status', 'approved')
        ->count();

    $pendingEO = DB::table('eo')
        ->where('status', 'pending')
        ->count();

    $rejectedEO = DB::table('eo')
        ->where('status', 'rejected')
        ->count();

    // =========================
    // EVENTS
    // =========================

    $totalEvents = DB::table('events')->count();

    $approvedEvents = DB::table('events')
        ->where('status', 'approved')
        ->count();

    $pendingEvents = DB::table('events')
        ->where('status', 'pending')
        ->count();

    $rejectedEvents = DB::table('events')
        ->where('status', 'rejected')
        ->count();

    // =========================
    // USERS
    // =========================

    $totalUsers = DB::table('users')->count();

    // =========================
    // RECENT EO
    // =========================

    $recentEO = DB::table('eo')
        ->join('users', 'eo.user_id', '=', 'users.id')
        ->select(
            'eo.*',
            'users.name',
            'users.email'
        )
        ->latest('eo.created_at')
        ->take(5)
        ->get();

    // =========================
    // RECENT EVENTS
    // =========================

    $recentEvents = Event::with('eo')
        ->latest()
        ->take(5)
        ->get();

    return view('owner.dashboard', compact(
        'totalEO',
        'approvedEO',
        'pendingEO',
        'rejectedEO',

        'totalEvents',
        'approvedEvents',
        'pendingEvents',
        'rejectedEvents',

        'totalUsers',

        'recentEO',
        'recentEvents'
    ));
}
    

    /*
    |--------------------------------------------------------------------------
    | EO APPROVAL
    |--------------------------------------------------------------------------
    */

    public function eoIndex()
    {
        $eoList = DB::table('eo')
            ->join('users', 'eo.user_id', '=', 'users.id')
            ->select(
                'eo.*',
                'users.name',
                'users.email'
            )
            ->orderBy('eo.created_at', 'desc')
            ->get();

        return view('owner.eo-approval', compact('eoList'));
    }

    public function approve($id)
    {
        $eo = DB::table('eo')
            ->where('id', $id)
            ->first();

        if (!$eo) {
            abort(404);
        }

        // approve EO
        DB::table('eo')
            ->where('id', $id)
            ->update([
                'status' => 'approved'
            ]);

        // ubah role user jadi eo
        DB::table('users')
            ->where('id', $eo->user_id)
            ->update([
                'role' => 'eo'
            ]);

        return back()->with(
            'success',
            'EO berhasil di-approve'
        );
    }

    public function reject($id)
    {
        DB::table('eo')
            ->where('id', $id)
            ->update([
                'status' => 'rejected'
            ]);

        return back()->with(
            'success',
            'EO berhasil ditolak'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT APPROVAL
    |--------------------------------------------------------------------------
    */

    public function eventIndex()
    {
        $events = Event::with('eo')
            ->latest()
            ->get();

        return view('owner.event-approval', compact('events'));
    }

    public function approveEvent($id)
    {
        $event = Event::findOrFail($id);

        $event->status = 'approved';
        $event->save();

        return back()->with(
            'success',
            'Event berhasil di-approve'
        );
    }

    public function rejectEvent($id)
    {
        $event = Event::findOrFail($id);

        $event->status = 'rejected';
        $event->save();

        return back()->with(
            'error',
            'Event berhasil ditolak'
        );
    }
}
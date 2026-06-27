<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TransactionMerch;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AbsenMerchController extends Controller
{
    public function show($kode_unik)
    {
        $transaction = TransactionMerch::where('kode_unik', $kode_unik)
            ->with('details.product')
            ->firstOrFail();

        return view('admin.absen.form-merch', compact('transaction'));
    }

    public function verify(Request $request, $kode_unik)
    {
        $request->validate(['password' => 'required']);
        $passwordBenar = env('ABSEN_MERCH_PASS', 'merch123');

        if ($request->password === $passwordBenar) {
            session(['absen_verified' => true]);
            return redirect()->route('admin.absen.form-merch', $kode_unik);
        }

        return back()->with('error', 'Password salah!');
    }

    public function store($kode_unik)
    {
        $transaction = TransactionMerch::where('kode_unik', $kode_unik)->firstOrFail();
        $transaction->update(['is_absen' => true]);

        session()->forget('absen_verified');
        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | FITUR TAMBAHAN: PANTAUAN MERCHANDISE SISI EO (DASHBOARD)
    |--------------------------------------------------------------------------
    */
    public function indexMerch(Request $request)
    {
        $user = Auth::user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();
        
        if (!$eo) {
            return redirect()->back()->with('error', 'Profil Event Organizer tidak ditemukan.');
        }

        $eventIds = Event::where('eo_id', $eo->id)->pluck('id');
        $query = TransactionMerch::with(['event', 'details.product'])
            ->whereIn('event_id', $eventIds)
            ->where('payment_status', 'PAID');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_unik', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('status')) {
            $query->where('is_absen', $request->status === 'sudah' ? 1 : 0);
        }

        $events = Event::where('eo_id', $eo->id)->get();
        $merchTransactions = $query->latest('id')->paginate(10)->withQueryString();

        return view('eo.absensi.merch', compact('events', 'merchTransactions'));
    }

    public function merchManual($id)
    {
        $transaction = TransactionMerch::findOrFail($id);
        $transaction->update(['is_absen' => true]);

        return redirect()->back()->with('success', "Merchandise berhasil ditandai telah diambil.");
    }

    public function batalMerch($id)
    {
        $transaction = TransactionMerch::findOrFail($id);
        $transaction->update(['is_absen' => false]);

        return redirect()->back()->with('success', "Status pengambilan merchandise berhasil dibatalkan.");
    }
}
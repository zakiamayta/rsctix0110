<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminEventController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $now = Carbon::now();

        // Mengambil data event dikombinasikan dengan nama EO dari tabel eo
        $query = DB::table('events')
            ->join('eo', 'events.eo_id', '=', 'eo.id')
            ->select('events.id', 'events.title', 'eo.nama_badan_usaha as eo', 'events.date', 'events.status', 'events.is_rescheduled');

        // Pencocokan logika filter berdasarkan tab aplikasi Flutter
        if ($status === 'all') {
            // Loloskan semua data event tanpa memandang statusnya
        } elseif ($status === 'approved') {
            // Event yang sudah disetujui dan jadwalnya masih akan datang (aktif)
            $query->where('events.status', '=', 'approved')
                  ->where('events.date', '>=', $now);
        } elseif ($status === 'done') {
            // Event yang sudah disetujui dan tanggalnya sudah lewat
            $query->where('events.status', '=', 'approved')
                  ->where('events.date', '<', $now);
        } elseif ($status === 'rescheduled') {
            // Hanya event yang SEDANG mengajukan reschedule (butuh approval admin)
            // Catatan: is_rescheduled hanya counter riwayat (berapa kali pernah
            // direschedule) dan BUKAN indikator status saat ini, jadi tidak
            // dipakai untuk filter di sini agar tidak nyangkut ke tab lain.
            $query->where('events.status', '=', 'pending_reschedule');
        } elseif ($status === 'cancelled') {
            // Event yang dibatalkan atau sedang dalam proses pengajuan batal
            $query->whereIn('events.status', ['cancelled', 'pending_cancel']);
        }

        // Urutkan berdasarkan tanggal terbaru
        $events = $query->orderBy('events.date', 'desc')->get();

        $formattedEvents = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'eo' => $event->eo,
                'date' => Carbon::parse($event->date)->toIso8601String(),
                'status' => $event->status,
                'is_rescheduled' => $event->is_rescheduled
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedEvents
        ], 200);
    }

    // public function approve($id)
    // {
    //     $event = DB::table('events')->where('id', $id)->first();

    //     if (!$event) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Event tidak ditemukan.'
    //         ], 404);
    //     }

    //     // Jika event dalam kondisi pengajuan reschedule, setujui tanggal baru dan reset status ke approved
    //     if ($event->status === 'pending_reschedule') {
    //         DB::table('events')->where('id', $id)->update([
    //             'status' => 'approved',
    //             'date' => $event->proposed_date ?? $event->date, 
    //             'proposed_date' => null,
    //             'updated_at' => Carbon::now()
    //         ]);
    //     } else {
    //         // Aksi persetujuan normal untuk status 'pending' awal
    //         DB::table('events')->where('id', $id)->update([
    //             'status' => 'approved',
    //             'updated_at' => Carbon::now()
    //         ]);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Event berhasil disetujui!'
    //     ], 200);
    // }
}
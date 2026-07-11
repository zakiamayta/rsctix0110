<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Jadwal;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class EoEventController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('login');
            }

            if (auth()->user()->role !== 'eo') {
                abort(403, 'Akses hanya untuk EO');
            }

            return $next($request);
        });
    }

/**
     * 📌 Event milik EO (Approved & Cancelled)
     * Ditambahkan fitur deteksi otomatis event batal yang membutuhkan keputusan merchandise
     */
    public function index()
    {
        $user = auth()->user();

        $eo = DB::table('eo')
            ->where('user_id', $user->id)
            ->first();

        // 1. Ambil list event disetujui & batal untuk ditampilkan di tabel/grid utama seperti biasa
        $events = Event::where('eo_id', $eo->id)
            ->whereIn('status', [
                'approved',
                'cancelled'
            ])
            ->latest()
            ->get();

        // 2. 🔥 DETEKSI POPUP: Cari apakah ada event yang sudah disetujui BATAL (cancelled) oleh Owner,
        // namun keputusan penanganan dana merchandise-nya belum dipilih (masih NULL)
        $pendingMerchEvent = Event::where('eo_id', $eo->id)
            ->where('status', 'cancelled')
            ->whereNull('merch_cancel_decision')
            ->whereHas('products', function ($q) {
                $q->where('type', 'merch');
            })
            ->first(); // Ambil satu event gantung terlama untuk dipaksa diselesaikan lewat modal popup

        // Kirim $pendingMerchEvent ke halaman blade eo.event-show
        return view('eo.event-show', compact('events', 'pendingMerchEvent'));
    }

    /**
     * 📌 Status Event dalam Proses Pengajuan / Bermasalah
     */
    public function status()
    {
        $events = Event::whereHas('eo', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->whereIn('status', [
                'pending',
                'pending_cancel',
                'pending_reschedule',
                'rejected'
            ])
            ->latest()
            ->get();

        return view('eo.status', compact('events'));
    }

    /**
     * 📌 Form create
     */
    public function create()
    {
        return view('eo.create');
    }

    /**
     * 📌 STORE EVENT BARU
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'date' => 'required',
            'location' => 'required',
            'poster' => 'nullable|image|max:2048',
        ]);

        $this->validateEventSchedule($request);

        $user = auth()->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();

        DB::transaction(function () use ($request, $eo, $user) {
            $posterPath = null;

            if ($request->hasFile('poster')) {
                $file = $request->file('poster');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/events'), $filename);
                $posterPath = 'images/events/' . $filename;
            }

            $event = Event::create([
                'eo_id' => $eo->id,
                'title' => $request->title,
                'description' => $request->description,
                'lineup' => $request->lineup,
                'organizer' => $user->name,
                'instagram' => $request->instagram,
                'date' => $request->date,
                'ticket_sale_start' => $request->ticket_sale_start,
                'ticket_redeem_start' => $request->ticket_redeem_start,
                'min_age' => $request->min_age,
                'location' => $request->location,
                'poster' => $posterPath,
                'max_tickets_per_email' => $request->max_tickets_per_email ?? 3,
                'status' => 'pending',
            ]);

            if ($request->jadwal) {
                foreach ($request->jadwal as $jadwalData) {
                    $jadwal = Jadwal::create([
                        'event_id' => $event->id,
                        'info' => $jadwalData['info'],
                        'tanggal' => $jadwalData['tanggal'],
                        'deskripsi' => $jadwalData['deskripsi'] ?? null,
                    ]);

                    if (isset($jadwalData['tickets'])) {
                        foreach ($jadwalData['tickets'] as $ticketData) {
                            Ticket::create([
                                'event_id' => $event->id,
                                'jadwal_id' => $jadwal->id,
                                'name' => $ticketData['name'],
                                'price' => $ticketData['price'],
                                'stock' => $ticketData['stock'],
                                'start_sale' => $ticketData['start_sale'] ?? null,
                                'end_sale' => $ticketData['end_sale'] ?? null,
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()
            ->route('eo.event.index')
            ->with('success', 'Event berhasil diajukan');
    }

    /**
     * 📌 Form Edit Penyesuaian Jadwal (Setelah Reschedule Disetujui)
     */
    public function edit(Event $event)
    {
        $user = auth()->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();

        if (!$eo || $event->eo_id != $eo->id) {
            abort(403);
        }

        if (!$event->can_adjust_schedule) {
            abort(403, 'Anda tidak memiliki hak akses edit. Tunggu persetujuan reschedule dari Owner.');
        }

        if ($event->is_rescheduled >= 3) {
            abort(403, 'Batas maksimum penyesuaian jadwal (3 kali) untuk event ini telah habis.');
        }

        $event->load('jadwals.tickets');
        return view('eo.event-edit', compact('event'));
    }

    /**
     * 📌 UPDATE PENYESUAIAN JADWAL
     */
    public function update(Request $request, Event $event)
    {
        $user = auth()->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();

        if (!$eo || $event->eo_id != $eo->id) {
            abort(403);
        }

        if (!$event->can_adjust_schedule) {
            abort(403, 'Aksi ilegal. Hak penyesuaian jadwal tidak tersedia.');
        }

        if ($event->is_rescheduled >= 3) {
            abort(403, 'Batas maksimum penyesuaian jadwal (3 kali) untuk event ini telah habis.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'location' => 'required|string|max:255',
            'poster' => 'nullable|image|max:5120',
        ]);

        $this->validateEventSchedule($request);

        DB::transaction(function () use ($request, $event) {
            if ($request->hasFile('poster')) {
                if ($event->poster && file_exists(public_path($event->poster))) {
                    File::delete(public_path($event->poster));
                }

                $file = $request->file('poster');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/events'), $filename);
                $event->poster = 'images/events/' . $filename;
            }

            $updateData = [
                'title' => $request->title,
                'date' => $request->date,
                'location' => $request->location,
                'description' => $request->description,
                'instagram' => $request->instagram,
                'lineup' => $request->lineup,
                'min_age' => $request->min_age,
                'max_tickets_per_email' => $request->max_tickets_per_email,
                'ticket_sale_start' => $request->ticket_sale_start,
                'ticket_redeem_start' => $request->ticket_redeem_start,
                'poster' => $event->poster,
                'status' => 'approved',
                'can_adjust_schedule' => false,
                'proposed_date' => null,
                'owner_note' => null,
                'reschedule_reason' => null,
            ];

            $event->update($updateData);

            Ticket::where('event_id', $event->id)->delete();
            $event->jadwals()->delete();

            if ($request->jadwal) {
                foreach ($request->jadwal as $jadwalData) {
                    $jadwal = Jadwal::create([
                        'event_id' => $event->id,
                        'info' => $jadwalData['info'],
                        'tanggal' => $jadwalData['tanggal'],
                        'deskripsi' => $jadwalData['deskripsi'] ?? null,
                    ]);

                    if (!empty($jadwalData['tickets'])) {
                        foreach ($jadwalData['tickets'] as $ticketData) {
                            Ticket::create([
                                'event_id' => $event->id,
                                'jadwal_id' => $jadwal->id,
                                'name' => $ticketData['name'],
                                'price' => $ticketData['price'],
                                'stock' => $ticketData['stock'],
                                'start_sale' => $ticketData['start_sale'] ?? null,
                                'end_sale' => $ticketData['end_sale'] ?? null,
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()
            ->route('eo.event.index')
            ->with('success', 'Jadwal event berhasil diperbarui.');
    }

    /**
     * 📌 Delete Event
     */
    public function destroy(Event $event)
    {
        $user = auth()->user();

        $eo = DB::table('eo')
            ->where('user_id', $user->id)
            ->first();

        if (!$eo || $event->eo_id != $eo->id) {
            abort(403);
        }

        if ($event->poster && file_exists(public_path($event->poster))) {
            File::delete(public_path($event->poster));
        }

        $event->delete();

        return redirect()
            ->route('eo.event.index')
            ->with('success', 'Event berhasil dihapus');
    }

    /**
     * 📌 Detail Event untuk EO
     */
    public function show(Event $event)
    {
        $user = auth()->user();

        $eo = DB::table('eo')
            ->where('user_id', $user->id)
            ->first();

        if (!$eo || $event->eo_id != $eo->id) {
            abort(403);
        }

        $event->load([
            'jadwals.tickets'
        ]);

        return view('eo.event-detail', compact('event'));
    }

    /**
     * 📌 Form Edit Event yang Ditolak (Rejected)
     */
    public function editRejected(Event $event)
    {
        $user = auth()->user();

        $event->load([
            'jadwals.tickets'
        ]);

        $eo = DB::table('eo')
            ->where('user_id', $user->id)
            ->first();

        if (!$eo || $event->eo_id != $eo->id) {
            abort(403);
        }

        return view('eo.event-edit-rejected', compact('event'));
    }

    /**
     * 📌 RESUBMIT EVENT SETELAH REJECTED
     */
    public function resubmit(Request $request, Event $event)
    {
        $user = auth()->user();

        $eo = DB::table('eo')
            ->where('user_id', $user->id)
            ->first();

        if (!$eo || $event->eo_id != $eo->id) {
            abort(403);
        }

        $request->validate([
            'title' => 'required',
            'date' => 'required',
            'location' => 'required',
            'poster' => 'nullable|image|max:2048',
        ]);

        $this->validateEventSchedule($request);

        DB::transaction(function () use ($request, $event) {
            $posterPath = $event->poster;

            if ($request->hasFile('poster')) {
                if ($event->poster && file_exists(public_path($event->poster))) {
                    File::delete(public_path($event->poster));
                }

                $file = $request->file('poster');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/events'), $filename);
                $posterPath = 'images/events/' . $filename;
            }

            $event->update([
                'title' => $request->title,
                'date' => $request->date,
                'location' => $request->location,
                'description' => $request->description,
                'instagram' => $request->instagram,
                'lineup' => $request->lineup,
                'min_age' => $request->min_age,
                'max_tickets_per_email' => $request->max_tickets_per_email,
                'ticket_sale_start' => $request->ticket_sale_start,
                'ticket_redeem_start' => $request->ticket_redeem_start,
                'poster' => $posterPath,
                'status' => 'pending',
            ]);

            Ticket::where('event_id', $event->id)->delete();
            $event->jadwals()->delete();

            if ($request->jadwal) {
                foreach ($request->jadwal as $jadwalData) {
                    $jadwal = Jadwal::create([
                        'event_id' => $event->id,
                        'info' => $jadwalData['info'],
                        'tanggal' => $jadwalData['tanggal'],
                        'deskripsi' => $jadwalData['deskripsi'] ?? null,
                    ]);

                    if (!empty($jadwalData['tickets'])) {
                        foreach ($jadwalData['tickets'] as $ticketData) {
                            Ticket::create([
                                'event_id' => $event->id,
                                'jadwal_id' => $jadwal->id,
                                'name' => $ticketData['name'],
                                'price' => $ticketData['price'],
                                'stock' => $ticketData['stock'],
                                'start_sale' => $ticketData['start_sale'] ?? null,
                                'end_sale' => $ticketData['end_sale'] ?? null,
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()
            ->route('eo.event.index')
            ->with('success', 'Event berhasil di-resubmit');
    }

    /**
     * 🛑 EO mengajukan pembatalan ke Owner (Cancel Request)
     */
    public function requestCancel(Event $event)
    {
        $user = auth()->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();

        if ($event->eo_id !== $eo->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah event ini.');
        }

        if ($event->status !== 'approved') {
            return back()->with('error', 'Hanya event dengan status Approved yang bisa diajukan pembatalan.');
        }

        $event->update([
            'status' => 'pending_cancel'
        ]);

        return back()->with('success', 'Pengajuan pembatalan event telah dikirim ke Owner.');
    }

    /**
     * 📅 Menampilkan Form Pengajuan Reschedule
     */
    public function showRescheduleForm(Event $event)
    {
        $user = auth()->user();

        $eo = DB::table('eo')
            ->where('user_id', $user->id)
            ->first();

        if (!$eo || $event->eo_id != $eo->id) {
            abort(403);
        }
        $event->load(['jadwals.tickets']);

        return response()->view('eo.event-reschedule', compact('event'));
    }

    /**
     * 📅 Menampilkan Halaman Edit Reschedule
     */
    public function editReschedule(Event $event)
    {
        $user = auth()->user();

        $eo = DB::table('eo')
            ->where('user_id', $user->id)
            ->first();

        if (!$eo || $event->eo_id != $eo->id) {
            abort(403);
        }
        return view('eo.event-reschedule', compact('event'));
    }

    /**
     * 📅 Kirim Pengajuan Reschedule Ke Owner
     */
    public function requestReschedule(Request $request, Event $event)
    {
        $request->validate([
            'proposed_date' => 'required|date|after:now',
            'reschedule_reason' => 'required|string|max:1000',
        ]);

        $user = auth()->user();
        $eo = DB::table('eo')
            ->where('user_id', $user->id)
            ->first();

        if (!$eo || $event->eo_id !== $eo->id) {
            abort(403);
        }

        if ($event->is_rescheduled >= 3) {
            return back()->with('error', 'Maaf, batas maksimal pengajuan reschedule untuk event ini (maksimal 3 kali) sudah habis.');
        }

        if ($event->status !== 'approved') {
            return back()->with('error', 'Hanya event approved yang bisa direschedule.');
        }

        $currentDate = Carbon::parse($event->date);
        $newDate = Carbon::parse($request->proposed_date);

        if ($currentDate->equalTo($newDate)) {
            return back()
                ->withInput()
                ->with('error', 'Tanggal baru harus berbeda dengan tanggal event saat ini.');
        }

        $event->update([
            'status' => 'pending_reschedule',
            'proposed_date' => $request->proposed_date,
            'reschedule_reason' => $request->reschedule_reason,
            'reschedule_rejected_reason' => null, // bersihkan alasan penolakan pengajuan sebelumnya
        ]);

        return back()->with('success', 'Request reschedule berhasil dikirim.');
    }

    /**
     * 🛠️ VALIDASI ATURAN WAKTU JADWAL & TIKET
     */
    private function validateEventSchedule(Request $request)
    {
        $eventDate = Carbon::parse($request->date);

        if ($request->ticket_sale_start) {
            $saleStart = Carbon::parse($request->ticket_sale_start);
            $latestStartSale = $eventDate->copy()->subDays(2);

            if ($saleStart->gt($latestStartSale)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'Mulai penjualan tiket harus minimal H-2 sebelum event.'
                    ])->throwResponse();
            }
        }

        if ($request->ticket_redeem_start) {
            $redeemDate = Carbon::parse($request->ticket_redeem_start);

            if ($redeemDate->gt($eventDate)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'Penukaran tiket tidak boleh melebihi tanggal event.'
                    ])->throwResponse();
            }
        }

        foreach ($request->jadwal ?? [] as $jadwal) {
            $jadwalDate = Carbon::parse($jadwal['tanggal']);

            if ($jadwalDate->lt($eventDate)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'Tanggal jadwal tidak boleh sebelum tanggal event.'
                    ])->throwResponse();
            }

            if ($jadwalDate->gt($eventDate->copy()->addDays(14))) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'Jadwal maksimal 14 hari setelah tanggal event.'
                    ])->throwResponse();
            }

            foreach (($jadwal['tickets'] ?? []) as $ticket) {
                if (!empty($ticket['start_sale'])) {
                    $startSale = Carbon::parse($ticket['start_sale']);
                    $latestStartSale = $eventDate->copy()->subDays(2);

                    if ($startSale->gt($latestStartSale)) {
                        return back()
                            ->withInput()
                            ->withErrors([
                                'Penjualan tiket harus dimulai minimal H-2 sebelum event.'
                            ])->throwResponse();
                    }
                }

                if (!empty($ticket['start_sale']) && !empty($ticket['end_sale'])) {
                    $startSale = Carbon::parse($ticket['start_sale']);
                    $endSale = Carbon::parse($ticket['end_sale']);

                    if ($endSale->lt($startSale)) {
                        return back()
                            ->withInput()
                            ->withErrors([
                                'Akhir penjualan tiket tidak boleh sebelum mulai penjualan.'
                            ])->throwResponse();
                    }
                }

                if (!empty($ticket['end_sale'])) {
                    $endSale = Carbon::parse($ticket['end_sale']);

                    if ($endSale->gt($eventDate)) {
                        return back()
                            ->withInput()
                            ->withErrors([
                                'Akhir penjualan tiket tidak boleh melebihi tanggal event.'
                            ])->throwResponse();
                    }
                }
            }
        }
    }

/**
     * ✅ Menyimpan keputusan pembatalan merchandise dari EO (Refund / Ship)
     * ALUR MURNI: Tidak ada potong saldo, tidak ada utang, tidak ada auto-insert refunds.
     * Tugas EO hanya mengunci status keputusan agar gerbang pengajuan pembeli terbuka.
     */
    public function submitMerchDecision(Request $request, Event $event)
    {
        // 1. Validasi input keputusan EO
        $request->validate([
            'merch_decision' => 'required|in:refund,ship_independently'
        ]);

        $user = auth()->user();

        // 2. Ambil data profil EO untuk validasi kepemilikan
        $eo = DB::table('eo')->where('user_id', $user->id)->first();

        if (!$eo) {
            return redirect()->back()->with('error', 'Akses ditolak. Profil EO Anda tidak ditemukan.');
        }

        // 3. Pastikan Event ini benar milik EO yang sedang login
        if ($event->eo_id !== $eo->id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk event ini.');
        }

        DB::beginTransaction();
        try {
            // 4. Update keputusan merchandise langsung ke kolom event terkait
            $event->update([
                'merch_cancel_decision' => $request->merch_decision
            ]);

            $eventId = $event->id;

            // 5. JIKA EO MEMILIH KIRIM MANDIRI, barulah kita buat manifes absensi fisiknya
            if ($request->merch_decision === 'ship_independently') {
                
                $merchDetails = DB::table('transaction_merch_details')
                    ->join('transaction_merch', 'transaction_merch_details.transaction_merch_id', '=', 'transaction_merch.id')
                    ->where('transaction_merch.event_id', $eventId)
                    ->where('transaction_merch.payment_status', 'paid')
                    ->select('transaction_merch_details.id as detail_id', 'transaction_merch.id as tx_id')
                    ->get();

                foreach ($merchDetails as $detail) {
                    // Hindari duplikasi insert manifes
                    $existsAttendee = DB::table('merch_attendees')
                        ->where('transaction_merch_id', $detail->tx_id)
                        ->where('transaction_merch_detail_id', $detail->detail_id)
                        ->exists();

                    if (!$existsAttendee) {
                        DB::table('merch_attendees')->insert([
                            'transaction_merch_id'        => $detail->tx_id,
                            'transaction_merch_detail_id' => $detail->detail_id,
                            'is_completed'                => 0, // Default awal belum dicentang
                            'created_at'                  => now(),
                            'updated_at'                  => now()
                        ]);
                    }
                }
            }

            // PENTING: JIKA EO MEMILIH 'refund', KITA TIDAK MELAKUKAN KODE FINANSIAL ATAU INSERT APAPUN DI SINI!
            // Urusan potong dana/utang diproses terpusat di AdminRefunds.
            // Dengan mengosongkan ini, BuyerMerchRefundController milik pembeli berfungsi 100%.

            DB::commit();

            $msg = $request->merch_decision === 'refund'
                ? 'Keputusan disimpan. Formulir pengisian rekening pengembalian dana (refund) kini telah dibuka untuk para pembeli merchandise.'
                : 'Keputusan disimpan. Silakan lakukan pengiriman mandiri dan kelola pencentangan manifes di dashboard Anda.';

            return redirect()->back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan keputusan: ' . $e->getMessage());
        }
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Product;
use App\Support\XenditBankList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HomeApiController extends Controller
{
    public function index()
    {
        // Ambil semua event yang berstatus approved
        $events = Event::where('status', 'approved')
            ->orderBy('date')
            ->get()
            ->map(function ($event) {
                
                // 📑 1. AMBIL TANGGAL TERAKHIR JADWAL BERDASARKAN EVENT INI
                $maxJadwalTanggal = null;
                try {
                    $maxJadwalTanggal = DB::table('jadwal')
                        ->where('event_id', $event->id)
                        ->max('tanggal');
                } catch (\Exception $e) {
                    \Log::error("Gagal mengambil jadwal untuk event ID {$event->id}: " . $e->getMessage());
                }

                // 📑 2. PARSING TANGGAL UTAMA EVENT KE STRING BERFORMAT (YYYY-MM-DD)
                $cleanDate = $event->date 
                    ? Carbon::parse($event->date)->toDateString() 
                    : Carbon::now()->toDateString();

                // 📑 3. TENTUKAN END DATE & REAL END DATE DARI JADWAL (Tabel events tidak memiliki end_date)
                if ($maxJadwalTanggal) {
                    $cleanEndDate = Carbon::parse($maxJadwalTanggal)->toDateString();
                } else {
                    $cleanEndDate = $cleanDate; // Fallback ke tanggal mulai jika belum ada jadwal
                }

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'event_url' => $event->event_url,
                    'description' => $event->description,
                    'lineup' => $event->lineup,
                    'organizer' => $event->organizer,
                    'instagram' => $event->instagram,
                    
                    // Format tanggal bersih siap saji untuk Flutter
                    'date' => $cleanDate,
                    'end_date' => $cleanEndDate, 
                    'real_end_date' => $cleanEndDate,

                    'ticket_sale_start' => $event->ticket_sale_start ? Carbon::parse($event->ticket_sale_start)->toDateTimeString() : null,
                    'ticket_redeem_start' => $event->ticket_redeem_start ? Carbon::parse($event->ticket_redeem_start)->toDateTimeString() : null,
                    'min_age' => $event->min_age,
                    'location' => $event->location,
                    'max_tickets_per_email' => $event->max_tickets_per_email,
                    'status' => $event->status,

                    'poster' => $event->poster
                        ? asset($event->poster)
                        : null,
                ];
            });

        return response()->json([
            'status' => true,
            'events' => $events,
            'tickets' => Product::where('type', 'ticket')->get(),
            'merchandise' => Product::where('type', 'merch')->get(),
        ]);
    }

    public function orderHistory(Request $request)
    {
        $email = $request->user()->email;

        /// =========================================
        /// 🎫 TICKET HISTORY
        /// =========================================
        $tickets = DB::table('transactions')
            ->leftJoin(
                'events',
                'transactions.event_id',
                '=',
                'events.id'
            )
            ->leftJoin(
                'ticket_attendees',
                'transactions.id',
                '=',
                'ticket_attendees.transaction_id'
            )
            ->leftJoin(
                'tickets',
                'ticket_attendees.ticket_id',
                '=',
                'tickets.id'
            )
            ->select(
                'transactions.id',
                'transactions.kode_unik',
                'transactions.total_amount',
                'transactions.service_tax',
                'transactions.grand_total',
                'transactions.payment_status',
                'transactions.payment_method', 
                'transactions.checkout_time',
                'transactions.paid_time',
                'transactions.qr_code',
                'transactions.xendit_invoice_url',
                'events.title as event_title',
                'events.location',
                'events.date as event_date',
                'events.poster',
                DB::raw('COUNT(ticket_attendees.id) as qty_ticket')
            )
            ->where('transactions.email', $email)
            ->groupBy(
                'transactions.id',
                'transactions.kode_unik',
                'transactions.total_amount',
                'transactions.service_tax',
                'transactions.grand_total',
                'transactions.payment_status',
                'transactions.payment_method', 
                'transactions.checkout_time',
                'transactions.paid_time',
                'transactions.qr_code',
                'transactions.xendit_invoice_url',
                'events.title',
                'events.location',
                'events.date',
                'events.poster'
            )
            ->orderByDesc('transactions.id')
            ->get()
            ->map(function ($item) {

                $details = DB::table('ticket_attendees')
                    ->leftJoin('tickets', 'ticket_attendees.ticket_id', '=', 'tickets.id')
                    ->select(
                        'tickets.name',
                        'tickets.price',
                        DB::raw('COUNT(ticket_attendees.id) as qty')
                    )
                    ->where('ticket_attendees.transaction_id', $item->id)
                    ->groupBy('tickets.name', 'tickets.price')
                    ->get()
                    ->map(function ($d) {
                        return [
                            'ticket_name' => $d->name ?? 'Ticket',
                            'qty'         => (int) $d->qty,
                            'price'       => (int) $d->price,
                            'subtotal'    => ((int) $d->price * (int) $d->qty),
                        ];
                    });

                return [
                    'id'                 => $item->id,
                    'kode_unik'          => $item->kode_unik,
                    'payment_status'     => $item->payment_status,
                    'payment_method'     => $item->payment_method ?? '-', 
                    'checkout_time'      => $item->checkout_time,
                    'paid_time'          => $item->paid_time,
                    'qr_code'            => $item->qr_code ? asset($item->qr_code) : null,
                    'xendit_invoice_url' => $item->xendit_invoice_url,
                    'event_title'        => $item->event_title ?? 'Event',
                    'location'           => $item->location,
                    'event_date'         => $item->event_date ? Carbon::parse($item->event_date)->toDateString() : null,
                    'poster'             => $item->poster ? asset($item->poster) : null,
                    'total_amount'       => (int) $item->total_amount,
                    'service_tax'        => (int) $item->service_tax,
                    'grand_total'        => (int) $item->grand_total,
                    'total_price'        => (int) ($item->grand_total > 0 ? $item->grand_total : ((int) $item->total_amount + (int) $item->service_tax)),
                    'qty_ticket'         => (int) $item->qty_ticket,
                    'attendees'          => $details,
                ];
            });

        /// =========================================
        /// 🛍 MERCH HISTORY
        /// =========================================
        $merchandise = DB::table('transaction_merch')
            ->leftJoin(
                'transaction_merch_details',
                'transaction_merch.id',
                '=',
                'transaction_merch_details.transaction_merch_id'
            )
            ->leftJoin(
                'products',
                'transaction_merch_details.product_id',
                '=',
                'products.id'
            )
            ->select(
                'transaction_merch.id',
                'transaction_merch.kode_unik',
                'transaction_merch.total_amount',
                'transaction_merch.service_tax',
                'transaction_merch.grand_total',
                'transaction_merch.payment_status',
                'transaction_merch.payment_method', 
                'transaction_merch.checkout_time',
                'transaction_merch.paid_time',
                'transaction_merch.qr_code',
                'transaction_merch.xendit_invoice_url',
                DB::raw('MIN(products.name) as product_name'),
                DB::raw('COALESCE(SUM(transaction_merch_details.quantity), 0) as qty_merch')
            )
            ->where('transaction_merch.email', $email)
            ->groupBy(
                'transaction_merch.id',
                'transaction_merch.kode_unik',
                'transaction_merch.total_amount',
                'transaction_merch.service_tax',
                'transaction_merch.grand_total',
                'transaction_merch.payment_status',
                'transaction_merch.payment_method', 
                'transaction_merch.checkout_time',
                'transaction_merch.paid_time',
                'transaction_merch.qr_code',
                'transaction_merch.xendit_invoice_url'
            )
            ->orderByDesc('transaction_merch.id')
            ->get()
            ->map(function ($item) {

                $items = DB::table('transaction_merch_details')
                    ->leftJoin('products', 'transaction_merch_details.product_id', '=', 'products.id')
                    ->leftJoin('products_varian', 'transaction_merch_details.varian_id', '=', 'products_varian.id')
                    ->leftJoin('products_ukuran', 'transaction_merch_details.ukuran_id', '=', 'products_ukuran.id')
                    ->leftJoin('images', 'products_varian.id', '=', 'images.product_varian_id')
                    ->select(
                        'products.name as product_name',
                        'products_varian.varian as varian_name',
                        'products_ukuran.ukuran as ukuran_name',
                        'transaction_merch_details.quantity',
                        'transaction_merch_details.subtotal',
                        DB::raw('MIN(images.url) as image_path') 
                    )
                    ->where('transaction_merch_details.transaction_merch_id', $item->id)
                    ->groupBy(
                        'products.name',
                        'products_varian.varian',
                        'products_ukuran.ukuran',
                        'transaction_merch_details.quantity',
                        'transaction_merch_details.subtotal'
                    )
                    ->get()
                    ->map(function ($d) {

                        $name = $d->product_name ?? 'Product';

                        if ($d->varian_name) {
                            $name .= ' - ' . $d->varian_name;
                        }

                        if ($d->ukuran_name) {
                            $name .= ' (' . $d->ukuran_name . ')';
                        }

                        return [
                            'product_name' => $name,
                            'quantity'     => (int) $d->quantity,
                            'subtotal'     => (int) $d->subtotal,
                            'image_url'    => $d->image_path ? asset($d->image_path) : null,
                        ];
                    });

                return [
                    'id'                 => $item->id,
                    'kode_unik'          => $item->kode_unik,
                    'payment_status'     => $item->payment_status,
                    'payment_method'     => $item->payment_method ?? '-', 
                    'checkout_time'      => $item->checkout_time,
                    'paid_time'          => $item->paid_time,
                    'qr_code'            => $item->qr_code ? asset($item->qr_code) : null,
                    'xendit_invoice_url' => $item->xendit_invoice_url,
                    'product_name'       => $item->product_name ?? 'Merchandise',
                    'total_amount'       => (int) $item->total_amount,
                    'service_tax'        => (int) $item->service_tax,
                    'grand_total'        => (int) $item->grand_total,
                    'total_price'        => (int) ($item->grand_total > 0 ? $item->grand_total : ((int) $item->total_amount + (int) $item->service_tax)),
                    'total_item'         => (int) $item->qty_merch,
                    'items'              => $items,
                ];
            });

        return response()->json([
            'status'      => true,
            'tickets'     => $tickets,
            'merchandise' => $merchandise,
        ]);
    }
    public function notifications(Request $request)
    {
        $email = $request->user() ? $request->user()->email : $request->query('email');

        if (!$email) {
            return response()->json([
                'status' => false,
                'message' => 'Email tidak ditemukan.',
                'data' => []
            ], 400);
        }

        // 🎟️ A. TIKET BERHASIL DIBELI (payment_status = paid) -> notifikasi "Pembelian Berhasil"
        $ticketPurchased = DB::table('transactions')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->select(
                'events.id as event_id',
                'events.title as event_title',
                'events.poster as event_poster',
                'transactions.kode_unik',
                'transactions.paid_time'
            )
            ->where('transactions.email', $email)
            ->where('transactions.payment_status', 'paid')
            ->get()
            ->map(function ($item) {
                return [
                    'event_id' => $item->event_id,
                    'invoice_code' => $item->kode_unik,
                    'type' => 'TICKET_PURCHASED',
                    'transaction_type' => 'ticket',
                    'refund_status' => null,
                    'title' => "Pembelian Tiket Berhasil: " . $item->event_title,
                    'short_info' => "Tiket berhasil dibeli & pembayaran dikonfirmasi.",
                    'message' => "Pembayaran tiket untuk event '" . $item->event_title . "' dengan invoice " . $item->kode_unik . " telah berhasil dikonfirmasi.\n\nTerima kasih telah bertransaksi. Tiketmu sudah bisa dilihat di menu 'Tiket Saya'.",
                    'poster' => $item->event_poster ? asset($item->event_poster) : null,
                    'created_at' => $item->paid_time ? Carbon::parse($item->paid_time)->toDateTimeString() : null,
                ];
            })
            ->filter(fn ($n) => $n['created_at'] !== null)
            ->values();

        // 📦 B. MERCH BERHASIL DIBELI (payment_status = paid)
        $merchPurchased = DB::table('transaction_merch')
            ->leftJoin('transaction_merch_details', 'transaction_merch.id', '=', 'transaction_merch_details.transaction_merch_id')
            ->leftJoin('products', 'transaction_merch_details.product_id', '=', 'products.id')
            ->select(
                'transaction_merch.id',
                'transaction_merch.kode_unik',
                'transaction_merch.paid_time',
                DB::raw('MIN(products.name) as product_name')
            )
            ->where('transaction_merch.email', $email)
            ->where('transaction_merch.payment_status', 'paid')
            ->groupBy('transaction_merch.id', 'transaction_merch.kode_unik', 'transaction_merch.paid_time')
            ->get()
            ->map(function ($item) {
                $name = $item->product_name ?? 'Merchandise';
                return [
                    'event_id' => null,
                    'invoice_code' => $item->kode_unik,
                    'type' => 'MERCH_PURCHASED',
                    'transaction_type' => 'merch',
                    'refund_status' => null,
                    'title' => "Pembelian Merch Berhasil: " . $name,
                    'short_info' => "Merchandise berhasil dibeli & pembayaran dikonfirmasi.",
                    'message' => "Pembayaran merchandise '" . $name . "' dengan invoice " . $item->kode_unik . " telah berhasil dikonfirmasi.\n\nTerima kasih telah bertransaksi. Pesananmu sudah bisa dilihat di menu 'Pesanan Saya'.",
                    'poster' => null,
                    'created_at' => $item->paid_time ? Carbon::parse($item->paid_time)->toDateTimeString() : null,
                ];
            })
            ->filter(fn ($n) => $n['created_at'] !== null)
            ->values();

        // 🎟️ C. TIKET: EVENT DIBATALKAN / RESCHEDULE
        // ✅ FIX BUG: left join ke `refunds` supaya kita tahu apakah user SUDAH mengajukan refund
        // untuk invoice ini (dan statusnya apa). Dipakai Flutter untuk menyembunyikan/mengganti
        // tombol "Klaim Refund" saat refund masih diproses, alih-alih user baru tahu setelah submit form.
        $ticketNotifications = DB::table('transactions')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->leftJoin('refunds', 'refunds.transaction_id', '=', 'transactions.id')
            ->select(
                'events.id as event_id',
                'events.title as event_title',
                'events.poster as event_poster',
                'events.status as event_status',
                'events.date as event_old_date',
                'events.proposed_date as event_new_date',
                'transactions.kode_unik',
                'transactions.paid_time',
                'refunds.status as refund_status'
            )
            ->where('transactions.email', $email)
            ->where('transactions.payment_status', 'paid')
            ->where(function ($query) {
                $query->whereIn('events.status', ['cancelled', 'pending_cancel'])
                      ->orWhere(function ($q) {
                          $q->where('events.status', 'approved')
                            ->whereNotNull('events.reschedule_reason')
                            ->where('events.reschedule_reason', '!=', '');
                      });
            })
            ->get()
            ->unique('kode_unik') // Jaga-jaga agar leftJoin refunds tidak menggandakan baris
            ->map(function ($item) {
                $isCancel = in_array($item->event_status, ['cancelled', 'pending_cancel']);
                $type = $isCancel ? "CANCELLED" : "RESCHEDULE";
                $title = $isCancel ? "Pembatalan: " . $item->event_title : "Jadwal Baru: " . $item->event_title;

                if ($isCancel) {
                    $message = "Dengan hormat kami informasikan bahwa event '" . $item->event_title . "' telah dibatalkan.\n\nSesuai kebijakan proteksi pembeli, Anda berhak mendapatkan REFUND TIKET PENUH (100%) untuk kode invoice " . $item->kode_unik . ".";
                } else {
                    $newDateStr = $item->event_new_date ? Carbon::parse($item->event_new_date)->translatedFormat('l, d F Y') : '-';
                    $message = "Event '" . $item->event_title . "' mengalami perubahan jadwal.\n\n🗓️ Jadwal Baru: " . $newDateStr . "\n\nJika berhalangan hadir, Anda berhak mengklaim 100% Refund untuk nomor invoice " . $item->kode_unik . ".";
                }

                // ✅ FIX BUG: sesuaikan short_info dengan status refund yang sudah ada,
                // supaya user langsung tahu dari list notifikasi tanpa perlu buka form dulu.
                $shortInfo = $isCancel ? "Event dibatalkan. Ambil refund tiket di sini." : "Jadwal event berubah. Klik detail refund.";
                if (in_array($item->refund_status, ['waiting', 'pending'])) {
                    $shortInfo = "Refund sudah diajukan & sedang diproses.";
                } elseif ($item->refund_status === 'refunded') {
                    $shortInfo = "Refund sudah selesai & dana telah dikirim.";
                } elseif ($item->refund_status === 'rejected') {
                    $shortInfo = "Pengajuan refund ditolak Admin.";
                }

                return [
                    'event_id' => $item->event_id,
                    'invoice_code' => $item->kode_unik,
                    'type' => $type,
                    'transaction_type' => 'ticket',
                    'refund_status' => $item->refund_status, // null | waiting | pending | refunded | rejected
                    'title' => $title,
                    'short_info' => $shortInfo,
                    'message' => $message,
                    'poster' => $item->event_poster ? asset($item->event_poster) : null,
                    'created_at' => Carbon::parse($item->paid_time)->toDateTimeString(),
                ];
            })
            ->values();

        // 📦 D. MERCH: EVENT TERKAIT DIBATALKAN
        // Sama seperti tiket, disertakan refund_status supaya UI tahu apakah tombol klaim boleh muncul.
        $merchNotifications = DB::table('transaction_merch')
            ->join('transaction_merch_details', 'transaction_merch.id', '=', 'transaction_merch_details.transaction_merch_id')
            ->join('products', 'transaction_merch_details.product_id', '=', 'products.id')
            ->join('events', 'products.event_id', '=', 'events.id')
            ->leftJoin('refunds', 'refunds.transaction_merch_id', '=', 'transaction_merch.id')
            ->select(
                'events.id as event_id',
                'events.title as event_title',
                'events.poster as event_poster',
                'events.status as event_status',
                'transaction_merch.kode_unik',
                'transaction_merch.paid_time',
                'refunds.status as refund_status'
            )
            ->where('transaction_merch.email', $email)
            ->where('transaction_merch.payment_status', 'paid')
            ->whereIn('events.status', ['cancelled', 'pending_cancel'])
            ->get()
            ->unique('kode_unik') // Mencegah duplikasi data jika memesan item merch yang berbeda dalam 1 invoice
            ->map(function ($item) {
                $title = "Pembatalan Merch: " . $item->event_title;
                $message = "Sehubungan dengan dibatalkannya event '" . $item->event_title . "', pesanan Merchandise resmi Anda dengan nomor invoice " . $item->kode_unik . " dibatalkan secara otomatis.\n\nAnda berhak mendapatkan pengembalian dana 100% tanpa potongan.";

                $shortInfo = "Pemesanan Merchandise dibatalkan. Klaim refund di sini.";
                if (in_array($item->refund_status, ['waiting', 'pending'])) {
                    $shortInfo = "Refund sudah diajukan & sedang diproses.";
                } elseif ($item->refund_status === 'refunded') {
                    $shortInfo = "Refund sudah selesai & dana telah dikirim.";
                } elseif ($item->refund_status === 'rejected') {
                    $shortInfo = "Pengajuan refund ditolak Admin.";
                }

                return [
                    'event_id' => $item->event_id,
                    'invoice_code' => $item->kode_unik,
                    'type' => 'CANCELLED',
                    'transaction_type' => 'merch',
                    'refund_status' => $item->refund_status,
                    'title' => $title,
                    'short_info' => $shortInfo,
                    'message' => $message,
                    'poster' => $item->event_poster ? asset($item->event_poster) : null,
                    'created_at' => Carbon::parse($item->paid_time)->toDateTimeString(),
                ];
            })
            ->values();

        // 💸 E. RIWAYAT PROGRES REFUND (waiting -> pending -> refunded / rejected)
        // Notifikasi terpisah supaya user selalu tahu perkembangan pengajuannya,
        // dari "menunggu verifikasi" sampai "selesai" atau "ditolak".
        $refundTicket = DB::table('refunds')
            ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->join('events', 'transactions.event_id', '=', 'events.id')
            ->select(
                'events.id as event_id',
                'events.title as event_title',
                'events.poster as event_poster',
                'transactions.kode_unik',
                'refunds.status as refund_status',
                'refunds.grand_total_refunded',
                'refunds.updated_at'
            )
            ->where('transactions.email', $email)
            ->get()
            ->map(function ($item) {
                return $this->buildRefundStatusNotif(
                    $item->event_id,
                    $item->kode_unik,
                    'ticket',
                    $item->event_title,
                    $item->event_poster,
                    $item->refund_status,
                    $item->grand_total_refunded,
                    $item->updated_at
                );
            });

        $refundMerch = DB::table('refunds')
            ->join('transaction_merch', 'refunds.transaction_merch_id', '=', 'transaction_merch.id')
            ->leftJoin('transaction_merch_details', 'transaction_merch.id', '=', 'transaction_merch_details.transaction_merch_id')
            ->leftJoin('products', 'transaction_merch_details.product_id', '=', 'products.id')
            ->select(
                'transaction_merch.kode_unik',
                'refunds.status as refund_status',
                'refunds.grand_total_refunded',
                'refunds.updated_at',
                DB::raw('MIN(products.name) as product_name')
            )
            ->where('transaction_merch.email', $email)
            ->groupBy('transaction_merch.kode_unik', 'refunds.status', 'refunds.grand_total_refunded', 'refunds.updated_at')
            ->get()
            ->map(function ($item) {
                return $this->buildRefundStatusNotif(
                    null,
                    $item->kode_unik,
                    'merch',
                    $item->product_name ?? 'Merchandise',
                    null,
                    $item->refund_status,
                    $item->grand_total_refunded,
                    $item->updated_at
                );
            });

        // 🏢 F. STATUS PENGAJUAN EO & EVENT
        // Agar EO tetap bisa memantau status pengajuannya (disetujui/ditolak/masih ditinjau)
        // meskipun sedang tidak bisa melakukan pengajuan baru.
        $eoNotifications = collect();

        $user = DB::table('users')->where('email', $email)->first();

        if ($user) {
            $eo = DB::table('eo')->where('user_id', $user->id)->first();

            if ($eo) {
                // --- Status pengajuan akun EO (badan usaha) ---
                $eoType = $eo->status === 'approved'
                    ? 'EO_APPROVED'
                    : ($eo->status === 'rejected' ? 'EO_REJECTED' : 'EO_PENDING');

                $eoTitle = $eo->status === 'approved'
                    ? "Pengajuan EO Disetujui"
                    : ($eo->status === 'rejected' ? "Pengajuan EO Ditolak" : "Pengajuan EO Sedang Ditinjau");

                if ($eo->status === 'approved') {
                    $eoMessage = "Selamat! Pengajuan Event Organizer atas nama '" . $eo->nama_badan_usaha . "' telah disetujui.\n\nKamu sudah bisa membuat event melalui halaman Web.";
                    $eoShortInfo = "Akun EO kamu sudah aktif.";
                } elseif ($eo->status === 'rejected') {
                    $eoMessage = "Mohon maaf, pengajuan Event Organizer atas nama '" . $eo->nama_badan_usaha . "' ditolak.\n\nAlasan: " . ($eo->rejected_reason ?? '-');
                    $eoShortInfo = "Pengajuan ditolak. Lihat alasannya di sini.";
                } else {
                    $eoMessage = "Pengajuan Event Organizer atas nama '" . $eo->nama_badan_usaha . "' sedang ditinjau oleh Admin.\n\nMohon tunggu beberapa saat, kamu akan diberi tahu setelah statusnya diperbarui.";
                    $eoShortInfo = "Sedang ditinjau oleh Admin.";
                }

                $eoNotifications->push([
                    'event_id' => null,
                    'invoice_code' => null,
                    'type' => $eoType,
                    'transaction_type' => 'eo',
                    'refund_status' => null,
                    'title' => $eoTitle,
                    'short_info' => $eoShortInfo,
                    'message' => $eoMessage,
                    'poster' => $eo->logo ? asset($eo->logo) : null,
                    'created_at' => Carbon::parse($eo->updated_at ?? $eo->created_at)->toDateTimeString(),
                ]);

                // --- Status pengajuan tiap Event milik EO ini ---
                $eventSubmissions = DB::table('events')
                    ->where('eo_id', $eo->id)
                    ->whereIn('status', ['pending', 'approved', 'rejected'])
                    ->orderByDesc('updated_at')
                    ->get();

                foreach ($eventSubmissions as $ev) {
                    $evType = $ev->status === 'approved'
                        ? 'EVENT_APPROVED'
                        : ($ev->status === 'rejected' ? 'EVENT_REJECTED' : 'EVENT_PENDING');

                    $evTitle = $ev->status === 'approved'
                        ? "Event Disetujui: " . $ev->title
                        : ($ev->status === 'rejected' ? "Event Ditolak: " . $ev->title : "Event Sedang Ditinjau: " . $ev->title);

                    if ($ev->status === 'approved') {
                        $evMessage = "Event '" . $ev->title . "' telah disetujui dan sudah tayang di aplikasi.\n\nTiket/merchandise sudah bisa dijual sesuai jadwal yang kamu atur.";
                        $evShortInfo = "Event sudah tayang & bisa dijual.";
                    } elseif ($ev->status === 'rejected') {
                        $evMessage = "Mohon maaf, pengajuan event '" . $ev->title . "' ditolak.\n\nAlasan: " . ($ev->rejected_reason ?? '-');
                        $evShortInfo = "Pengajuan ditolak. Lihat alasannya di sini.";
                    } else {
                        $evMessage = "Pengajuan event '" . $ev->title . "' sedang ditinjau oleh Admin.\n\nMohon tunggu beberapa saat, kamu akan diberi tahu setelah statusnya diperbarui.";
                        $evShortInfo = "Sedang ditinjau oleh Admin.";
                    }

                    $eoNotifications->push([
                        'event_id' => $ev->id,
                        'invoice_code' => null,
                        'type' => $evType,
                        'transaction_type' => 'event_submission',
                        'refund_status' => null,
                        'title' => $evTitle,
                        'short_info' => $evShortInfo,
                        'message' => $evMessage,
                        'poster' => $ev->poster ? asset($ev->poster) : null,
                        'created_at' => Carbon::parse($ev->updated_at)->toDateTimeString(),
                    ]);
                }
            }
        }

        // Gabungkan SEMUA jenis notifikasi (pembelian, pembatalan/reschedule, progres refund, EO/event)
        // lalu urutkan berdasarkan tanggal terbaru, dan tempel ID unik per notifikasi.
        $allNotifications = $ticketPurchased
            ->concat($merchPurchased)
            ->concat($ticketNotifications)
            ->concat($merchNotifications)
            ->concat($refundTicket)
            ->concat($refundMerch)
            ->concat($eoNotifications)
            ->sortByDesc('created_at')
            ->values()
            ->map(function ($notif) {
                // 🆔 ID unik & stabil (bukan auto increment) supaya Flutter bisa menyimpan status
                // "sudah dibaca" per notifikasi meskipun tabel `notifications` tidak ada di database.
                $notif['id'] = md5(
                    ($notif['type'] ?? '') . '|' .
                    ($notif['invoice_code'] ?? '') . '|' .
                    ($notif['event_id'] ?? '') . '|' .
                    ($notif['created_at'] ?? '')
                );
                return $notif;
            })
            ->values();

        return response()->json([
            'status' => true,
            'total' => $allNotifications->count(),
            'data' => $allNotifications,
        ]);
    }

    /**
     * Bangun payload notifikasi progres refund (waiting / pending / refunded / rejected)
     * untuk satu baris di tabel `refunds`. Dipakai untuk tiket maupun merchandise.
     */
    private function buildRefundStatusNotif($eventId, $invoiceCode, $trxType, $itemTitle, $poster, $status, $amount, $updatedAt)
    {
        $formattedAmount = 'Rp ' . number_format((float) $amount, 0, ',', '.');

        switch ($status) {
            case 'waiting':
                $type = 'REFUND_WAITING';
                $title = "Refund Menunggu Verifikasi: " . $itemTitle;
                $shortInfo = "Pengajuan refund masuk antrean verifikasi Admin.";
                $message = "Pengajuan refund untuk invoice " . $invoiceCode . " (" . $itemTitle . ") sudah kami terima dan sedang menunggu verifikasi Admin.\n\nNominal yang akan dikembalikan: " . $formattedAmount . ".\n\nMohon tunggu ya, kamu tidak perlu mengajukan refund lagi untuk invoice ini.";
                break;
            case 'pending':
                $type = 'REFUND_PROCESSING';
                $title = "Refund Sedang Diproses: " . $itemTitle;
                $shortInfo = "Refund sudah diverifikasi & sedang diproses transfer.";
                $message = "Kabar baik! Refund untuk invoice " . $invoiceCode . " (" . $itemTitle . ") sudah diverifikasi dan sedang diproses oleh tim kami.\n\nNominal yang akan dikembalikan: " . $formattedAmount . ".\n\nDana akan segera dikirim ke rekening yang kamu daftarkan.";
                break;
            case 'refunded':
                $type = 'REFUND_COMPLETED';
                $title = "Refund Selesai: " . $itemTitle;
                $shortInfo = "Dana refund sudah dikirim ke rekeningmu.";
                $message = "Refund untuk invoice " . $invoiceCode . " (" . $itemTitle . ") telah selesai diproses.\n\nDana sebesar " . $formattedAmount . " sudah dikirim ke rekening tujuan yang kamu daftarkan.\n\nTerima kasih atas kesabarannya.";
                break;
            case 'rejected':
            default:
                $type = 'REFUND_REJECTED';
                $title = "Refund Ditolak: " . $itemTitle;
                $shortInfo = "Pengajuan refund ditolak Admin.";
                $message = "Mohon maaf, pengajuan refund untuk invoice " . $invoiceCode . " (" . $itemTitle . ") ditolak oleh Admin.\n\nSilakan hubungi Customer Service kami untuk informasi lebih lanjut.";
                break;
        }

        return [
            'event_id' => $eventId,
            'invoice_code' => $invoiceCode,
            'type' => $type,
            'transaction_type' => $trxType,
            'refund_status' => $status,
            'title' => $title,
            'short_info' => $shortInfo,
            'message' => $message,
            'poster' => $poster ? asset($poster) : null,
            'created_at' => Carbon::parse($updatedAt)->toDateTimeString(),
        ];
    }

    /**
     * Daftar bank yang tersedia di sistem (bersumber dari XenditBankList / xendit_banks.json).
     * Dipakai Flutter untuk mengisi dropdown "Nama Bank Tujuan" di form refund.
     */
    public function listBanks()
    {
        return response()->json([
            'status' => true,
            'data' => XenditBankList::all(), // [ ['name' => '...', 'code' => 'ID_XXX'], ... ] terurut alfabet
        ]);
    }

    /**
     * Detail nominal refund untuk satu invoice, dipakai Flutter untuk menampilkan
     * kartu "Nominal Dana Refund" di RefundFormScreen SEBELUM form disubmit.
     * Tidak menulis apa pun ke database — murni baca.
     */
    public function refundDetail(Request $request)
    {
        $invoiceCode = trim((string) $request->query('invoice_code', ''));

        if ($invoiceCode === '') {
            return response()->json([
                'status' => false,
                'message' => 'Kode invoice wajib disertakan.',
            ], 422);
        }

        // 1. Cek apakah ini Kode Invoice Tiket (`transactions`)
        $ticketTx = DB::table('transactions')->where('kode_unik', $invoiceCode)->first();
        $merchTx = null;

        if (!$ticketTx) {
            // 2. Jika bukan tiket, cek apakah ini Kode Invoice Merchandise (`transaction_merch`)
            $merchTx = DB::table('transaction_merch')->where('kode_unik', $invoiceCode)->first();
        }

        if (!$ticketTx && !$merchTx) {
            return response()->json([
                'status' => false,
                'message' => 'Nomor invoice transaksi tidak terdaftar di sistem kami.',
            ], 404);
        }

        $alreadyClaimed = DB::table('refunds')
            ->where(function ($q) use ($ticketTx, $merchTx) {
                if ($ticketTx) $q->where('transaction_id', $ticketTx->id);
                if ($merchTx) $q->where('transaction_merch_id', $merchTx->id);
            })->exists();

        // ✅ Nominal refund = grand_total dikurangi service_tax (service_tax tidak dikembalikan).
        // Dihitung eksplisit dari grand_total - service_tax, bukan mengandalkan kolom total_amount,
        // supaya tetap benar meski total_amount di suatu baris tidak sinkron dengan grand_total.
        $refundAmount = $ticketTx
            ? ($ticketTx->grand_total - $ticketTx->service_tax)
            : ($merchTx->grand_total - $merchTx->service_tax);

        return response()->json([
            'status' => true,
            'data' => [
                'invoice_code'    => $invoiceCode,
                'refund_amount'   => (int) $refundAmount,
                'refund_tax'      => 2500,
                'already_claimed' => $alreadyClaimed,
            ],
        ]);
    }

    /**
     * Menerima Submit Form Klaim Refund dari Flutter (Mendukung Tiket & Merchandise)
     */
    public function submitRefund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_code'   => 'required|string',
            // bank_name WAJIB salah satu nama bank resmi dari daftar Xendit (dipilih lewat dropdown di Flutter),
            // bukan lagi input bebas, supaya data rekening konsisten & tidak typo.
            'bank_name'      => ['required', 'string', Rule::in(collect(XenditBankList::all())->pluck('name')->all())],
            'account_number' => 'required|string',
            'account_name'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Mohon isi semua data rekening Anda dengan benar.',
                'errors' => $validator->errors()
            ], 422);
        }

        $invoiceCode = trim($request->invoice_code);
        
        $ticketTx = null;
        $merchTx = null;
        $grandTotalRefund = 0;

        // 1. Cek apakah ini Kode Invoice Tiket (`transactions`)
        $ticketTx = DB::table('transactions')->where('kode_unik', $invoiceCode)->first();

        if (!$ticketTx) {
            // 2. Jika bukan tiket, cek apakah ini Kode Invoice Merchandise (`transaction_merch`)
            $merchTx = DB::table('transaction_merch')->where('kode_unik', $invoiceCode)->first();
        }

        // Jika invoice tidak ditemukan di kedua tabel data
        if (!$ticketTx && !$merchTx) {
            return response()->json([
                'status' => false,
                'message' => 'Nomor invoice transaksi tidak terdaftar di sistem kami.'
            ], 404);
        }

        // 3. Validasi Duplikasi Klaim: Cegah agar user tidak melakukan spam kirim ulang dana
        $alreadyClaimed = DB::table('refunds')
            ->where(function($q) use ($ticketTx, $merchTx) {
                if ($ticketTx) $q->where('transaction_id', $ticketTx->id);
                if ($merchTx) $q->where('transaction_merch_id', $merchTx->id);
            })->exists();

        if ($alreadyClaimed) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan pengembalian dana untuk invoice ini sudah terkirim sebelumnya dan masuk dalam antrean.'
            ], 400);
        }

        // ✅ FIX: Pembeli hanya berhak menerima harga tiket/merch murni,
        // yaitu grand_total dikurangi service_tax. service_tax tidak dikembalikan.
        // Dihitung eksplisit dari grand_total - service_tax (bukan kolom total_amount)
        // supaya konsisten dengan refundDetail() dan tetap benar meski total_amount
        // di suatu baris tidak sinkron dengan grand_total.
        $grandTotalRefund = $ticketTx
            ? ($ticketTx->grand_total - $ticketTx->service_tax)
            : ($merchTx->grand_total - $merchTx->service_tax);

        DB::beginTransaction();
        try {
            // 4. Masukkan baris data baru ke dalam tabel refunds sesuai skema database asli Anda
            DB::table('refunds')->insert([
                'transaction_id'       => $ticketTx ? $ticketTx->id : null,
                'transaction_merch_id' => $merchTx ? $merchTx->id : null,
                'refund_batch_id'      => null, 
                'bank_name'            => $request->bank_name, // simpan persis sesuai nama resmi di xendit_banks.json
                'account_number'       => $request->account_number,
                'account_name'         => strtoupper($request->account_name),
                'grand_total_refunded' => $grandTotalRefund,
                'refunds_tax'          => 2500.00, // Biaya default per refund
                'status'               => 'waiting', // Default masuk antrean awal 'waiting'
                'processed_at'         => null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // 5. CATATAN: payment_status TIDAK diubah di sini.
            // Saat user baru MENGAJUKAN refund, status transaksi harus tetap 'paid'/'unpaid'
            // supaya tiket tetap tampil di "Tiket Saya" dengan badge "refund sedang diproses"
            // (lihat TicketController::myTickets, yang membaca status dari tabel `refunds`,
            // bukan dari `transactions.payment_status`).
            //
            // `transactions.payment_status` baru boleh diubah menjadi 'refunded' oleh endpoint
            // ADMIN/EO ketika mereka benar-benar menyelesaikan proses refund (mengubah
            // `refunds.status` menjadi 'refunded'), bukan pada saat pengajuan pertama kali.
            //
            // Untuk merchandise, tidak ada perubahan status yang perlu dilakukan di sini pun;
            // pencegahan klaim ganda sudah dijamin oleh pengecekan $alreadyClaimed di atas.

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Formulir refund resmi Anda berhasil disimpan ke dalam antrean verifikasi Admin.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal memproses pengajuan: ' . $e->getMessage()
            ], 500);
        }
    }
}
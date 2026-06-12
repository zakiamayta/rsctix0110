<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeApiController extends Controller
{
    public function index()
    {
        $events = Event::where('status', 'approved')
            ->orderBy('date')
            ->get()
            ->map(function ($event) {
                
                // 📑 AMBIL TANGGAL TERAKHIR DARI TABEL JADWAL BERDASARKAN EVENT INI
                $maxJadwalTanggal = DB::table('jadwal')
                    ->where('event_id', $event->id)
                    ->max('tanggal');

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'event_url' => $event->event_url,
                    'description' => $event->description,
                    'lineup' => $event->lineup,
                    'organizer' => $event->organizer,
                    'instagram' => $event->instagram,
                    'date' => $event->date,
                    
                    'end_date' => $event->end_date, 
                    
                    // 📑 FIELD BARU: Kirim tanggal jadwal terakhir (jika tidak ada jadwal, gunakan end_date)
                    'real_end_date' => $maxJadwalTanggal ?? $event->end_date ?? $event->date,

                    'ticket_sale_start' => $event->ticket_sale_start,
                    'ticket_redeem_start' => $event->ticket_redeem_start,
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
                'transactions.payment_method', // 🟢 Menambahkan payment_method tiket
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
                'transactions.payment_method', // 🟢 Wajib masuk groupBy
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
                    'payment_method'     => $item->payment_method ?? '-', // 🟢 Output API tiket
                    'checkout_time'      => $item->checkout_time,
                    'paid_time'          => $item->paid_time,
                    'qr_code'            => $item->qr_code ? asset($item->qr_code) : null,
                    'xendit_invoice_url' => $item->xendit_invoice_url,
                    'event_title'        => $item->event_title ?? 'Event',
                    'location'           => $item->location,
                    'event_date'         => $item->event_date,
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
        /// 🛍 MERCH HISTORY (Fixed & Secured)
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
                'transaction_merch.payment_method', // 🟢 Menambahkan payment_method merch
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
                'transaction_merch.payment_method', // 🟢 Wajib masuk groupBy
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
                    'payment_method'     => $item->payment_method ?? '-', // 🟢 Output API merch
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
}
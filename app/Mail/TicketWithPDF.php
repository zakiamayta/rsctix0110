<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Transaction;
use App\Models\TicketAttendee;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketWithPDF extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;
    public $attendee;

    public function __construct(Transaction $transaction, TicketAttendee $attendee)
    {
        $this->transaction = $transaction;
        $this->attendee = $attendee;
    }

    public function build()
    {
        // render view ke PDF khusus untuk 1 peserta (bukan seluruh transaksi)
        $pdf = Pdf::loadView('admin.export-qr', [
            'guest' => $this->attendee,
            'transaction' => $this->transaction,
        ]);

        return $this->subject('E-Ticket Anda: ' . ($this->transaction->event->title ?? 'Event'))
            ->markdown('emails.ticket', [
                'transaction' => $this->transaction,
                'event' => $this->transaction->event,
                'attendee' => $this->attendee,
                'buyerName' => $this->attendee->name,
            ])
            ->attachData($pdf->output(), 'ticket_' . $this->attendee->kode_unik . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
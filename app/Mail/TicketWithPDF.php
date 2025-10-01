<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketWithPDF extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

public function build()
{
    // render view ke PDF dari views/admin/export-qr.blade.php
    $pdf = Pdf::loadView('admin.export-qr', ['guest' => $this->transaction]);

    return $this->subject('E-Ticket Anda: ' . ($this->transaction->event->title ?? 'Event'))
        ->markdown('emails.ticket', [
            'transaction' => $this->transaction,
            'event' => $this->transaction->event,
            'buyerName' => $this->transaction->attendees->first()->name ?? $this->transaction->email,
        ])
        ->attachData($pdf->output(), 'ticket_' . $this->transaction->kode_unik . '.pdf', [
            'mime' => 'application/pdf',
        ]);
}

}

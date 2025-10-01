<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;
    public $pdfPath;
    public $qrPath;

    public function __construct($transaction, $pdfPath = null, $qrPath = null)
    {
        $this->transaction = $transaction;
        $this->pdfPath = $pdfPath;
        $this->qrPath = $qrPath;
    }

    public function build()
    {
        return $this->view('emails.ticket');
    }
}

<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\TransactionMerch; // ✅ model merch
use Barryvdh\DomPDF\Facade\Pdf;

class MerchInvoiceWithPDF extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;

    public function __construct(TransactionMerch $transaction) // ✅
    {
        $this->transaction = $transaction;
    }

    public function build()
    {
        $pdf = Pdf::loadView('admin.merch-invoice', [
            'transaction' => $this->transaction
        ]);

        return $this->subject('Invoice Merchandise Anda')
            ->markdown('emails.merch_invoice')
            ->attachData(
                $pdf->output(),
                'invoice_merch_' . $this->transaction->kode_unik . '.pdf',
                ['mime' => 'application/pdf']
            );
    }
}

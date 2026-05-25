<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $pembayaran;
    public $pdf;

    public function __construct($booking, $pembayaran, $pdf)
    {
        $this->booking = $booking;
        $this->pembayaran = $pembayaran;
        $this->pdf = $pdf;
    }

    public function build()
    {
        return $this->subject('Invoice Pembayaran - Harkat RentCar')
            ->markdown('emails.invoice')
            ->attachData($this->pdf, 'invoice_' . $this->pembayaran->id . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}

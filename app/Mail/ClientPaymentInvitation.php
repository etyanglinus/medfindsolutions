<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class ClientPaymentInvitation extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public string $clientName, public string $paymentUrl) {}
    public function build() { return $this->subject('Complete your Medfind Solutions subscription')->view('emails.client-payment-invitation'); }
}

<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ProviderPaymentConfirmed extends Mailable
{
    public function __construct(
        public string $clientName,
        public string $loginEmail,
        public string $planName,
        public string $amount,
        public ?string $expiresOn,
        public string $reference,
        public string $accessUrl,
    ) {}

    public function build()
    {
        return $this->subject(__('Payment received — your Medfind Solutions account is active'))->view('emails.provider-payment-confirmed');
    }
}

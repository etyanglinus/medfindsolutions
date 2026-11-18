<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PaybillPaymentRejected extends Mailable
{
    public function __construct(
        public string $providerName,
        public string $reason,
        public string $paymentUrl,
    ) {}

    public function build()
    {
        return $this->subject(__('We could not confirm your M-Pesa payment to MedFind'))->view('emails.paybill-payment-rejected');
    }
}

<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ProviderRenewalReminder extends Mailable
{
    public function __construct(
        public string $providerName,
        public string $planName,
        public string $expiresOn,
        public int $daysLeft,
        public string $renewUrl,
    ) {}

    public function build()
    {
        $subject = $this->daysLeft <= 1
            ? __('Your MedFind listing expires tomorrow')
            : __('Your MedFind listing expires in :days days', ['days' => $this->daysLeft]);
        return $this->subject($subject)->view('emails.provider-renewal-reminder');
    }
}

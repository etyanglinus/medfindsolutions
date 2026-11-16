<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class LicenceExpiryNotice extends Mailable
{
    public function __construct(
        public string $providerName,
        public string $expiresOn,
        public bool $expired,
        public bool $listingHidden,
        public string $uploadUrl,
    ) {}

    public function build()
    {
        return $this->subject($this->expired ? __('Your licence on MedFind has expired') : __('Your licence on MedFind expires on :date', ['date' => $this->expiresOn]))
            ->view('emails.licence-expiry-notice');
    }
}

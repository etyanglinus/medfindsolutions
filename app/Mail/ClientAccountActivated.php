<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ClientAccountActivated extends Mailable
{
    public function __construct(public string $clientName, public string $loginEmail, public string $loginPassword, public string $loginUrl) {}
    public function build()
    {
        return $this->subject('Your Medfind Solutions account is active')->view('emails.client-account-activated');
    }
}

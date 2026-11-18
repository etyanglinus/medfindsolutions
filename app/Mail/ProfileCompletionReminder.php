<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ProfileCompletionReminder extends Mailable
{
    /** @param list<string> $missing */
    public function __construct(
        public string $providerName,
        public string $planName,
        public array $missing,
        public string $loginUrl,
        public int $daysSincePaid,
    ) {}

    public function build()
    {
        return $this->subject(__('Your MedFind listing is not live yet: finish your profile'))->view('emails.profile-completion-reminder');
    }
}

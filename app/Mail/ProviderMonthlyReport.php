<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ProviderMonthlyReport extends Mailable
{
    /**
     * @param array<string, array{current: int, previous: int}> $totals
     */
    public function __construct(
        public string $providerName,
        public string $monthLabel,
        public array $totals,
        public ?string $planName,
        public ?string $expiresOn,
        public bool $renewalDue,
        public bool $expired,
        public string $renewUrl,
        public string $dashboardUrl,
    ) {}

    public function build()
    {
        $views = number_format($this->totals['profile_view']['current'] ?? 0);
        return $this->subject(__('Your MedFind listing in :month: :views profile views', ['month' => $this->monthLabel, 'views' => $views]))
            ->view('emails.provider-monthly-report');
    }
}

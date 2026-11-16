<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\GlobalMail;

/**
 * Artisan smoke-test for the SMTP mailer.
 * Usage: php artisan mail:test you@example.com
 */
class TestSmtp extends Command
{
    protected $signature   = 'mail:test {to : Recipient email address}';
    protected $description = 'Send a test email to verify SMTP connectivity';

    public function handle(): int
    {
        $to = $this->argument('to');
        $this->info("Sending test email to {$to} via " . config('mail.default') . " …");
        $this->line('  Host      : ' . config('mail.mailers.smtp.host'));
        $this->line('  Port      : ' . config('mail.mailers.smtp.port'));
        $this->line('  Encryption: ' . config('mail.mailers.smtp.encryption'));
        $this->line('  Username  : ' . config('mail.mailers.smtp.username'));
        $this->line('  From      : ' . config('mail.from.address'));

        try {
            Mail::to($to)->send(new GlobalMail(
                'MedFind SMTP Test',
                'This is a test email confirming that your SMTP server is working correctly.',
                ['Visit MedFind' => config('app.url')]
            ));
            $this->info('✓ Email sent successfully.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ Failed: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\GlobalMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function settings(): View
    {
        return view('admin.settings.settings');
    }

    /**
     * Show the test-mail form.
     */
    public function testMailForm(): View
    {
        $config = [
            'mailer'     => config('mail.default'),
            'host'       => config('mail.mailers.smtp.host'),
            'port'       => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username'   => config('mail.mailers.smtp.username'),
            'from'       => config('mail.from.address'),
        ];

        return view('admin.settings.test-mail', compact('config'));
    }

    /**
     * Send a test email and report the result inline (no full page reload needed).
     */
    public function sendTestMail(Request $request): RedirectResponse
    {
        $request->validate([
            'to' => ['required', 'email', 'max:255'],
        ], [
            'to.required' => __('Please enter a recipient email address.'),
            'to.email'    => __('Please enter a valid email address.'),
        ]);

        $to = $request->input('to');

        try {
            Mail::to($to)->send(new GlobalMail(
                '✅ ' . config('app.name') . ' — ' . __('SMTP Test Successful'),
                __(
                    'Voilà! Your SMTP configuration is working correctly. This test email was sent from :app at :time.',
                    [
                        'app'  => config('app.name'),
                        'time' => now()->format('d M Y H:i:s T'),
                    ]
                ),
                [__('Visit Dashboard') => route('admin.dashboard')]
            ));

            return redirect()
                ->route('admin.test-mail')
                ->with('test_mail_success', __('Email delivered to :to — your SMTP configuration is working correctly!', ['to' => $to]));
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.test-mail')
                ->withInput()
                ->with('test_mail_error', $e->getMessage());
        }
    }
}

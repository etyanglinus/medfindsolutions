<p>{{ __('Hello') }} {{ $clientName }},</p>
<p>{{ __('We have received your payment and your Medfind Solutions account is now active.') }}</p>
<table cellpadding="4" style="border-collapse:collapse">
    <tr><td><strong>{{ __('Plan') }}</strong></td><td>{{ $planName }}</td></tr>
    <tr><td><strong>{{ __('Amount') }}</strong></td><td>{{ $amount }}</td></tr>
    @if($expiresOn)<tr><td><strong>{{ __('Valid until') }}</strong></td><td>{{ $expiresOn }}</td></tr>@endif
    <tr><td><strong>{{ __('Payment reference') }}</strong></td><td>{{ $reference }}</td></tr>
    <tr><td><strong>{{ __('Login email') }}</strong></td><td>{{ $loginEmail }}</td></tr>
</table>
<p style="margin:24px 0">
    <a href="{{ $accessUrl }}" style="background:#2b5fde;color:#fff;padding:12px 24px;border-radius:24px;text-decoration:none;font-weight:bold">{{ __('Access my account') }}</a>
</p>
<p>{{ __('Use the password you chose when you registered. If you have forgotten it, use "Forgot password" on the login page.') }}</p>
<p>{{ __('After logging in, complete your profile and upload your verification documents. Your public listing is published once our team has reviewed them.') }}</p>
<p>{{ __('This button works for 7 days. After that, log in from the website as usual.') }}</p>

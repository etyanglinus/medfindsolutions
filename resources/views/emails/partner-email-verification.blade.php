<p>{{ __('Hello,') }}</p>
<p>{{ __('Verify your :provider email address to enable sign in.', ['provider' => $providerLabel]) }}</p>
<p><a href="{{ $verificationUrl }}">{{ __('Verify email address') }}</a></p>
<p>{{ __('This link expires in 60 minutes. If you did not create this account, you can ignore this email.') }}</p>

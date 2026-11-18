<div style="font-family:Arial,Helvetica,sans-serif;color:#1f2937;max-width:560px">
<p>{{ __('Hello') }} {{ $providerName }},</p>
<p>{{ __('Thank you for choosing the :plan plan. Your payment is confirmed, but people cannot see your listing yet because your profile is not finished.', ['plan' => $planName]) }}</p>
<p><strong>{{ __('Still to do:') }}</strong></p>
<ul>@foreach($missing as $item)<li>{{ $item }}</li>@endforeach</ul>
<p>{{ __('It takes about 10 minutes. Your plan is already running, so every day without a complete profile is a day people cannot find you.') }}</p>
@include('emails._button', ['url' => $loginUrl, 'label' => __('Finish my profile')])
<p style="font-size:12px;color:#6b7280">{{ __('Sign in with the email and password for your MedFind account. If you need help, reply to this email.') }}</p>
</div>

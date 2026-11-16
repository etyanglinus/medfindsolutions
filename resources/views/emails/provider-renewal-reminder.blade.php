<div style="font-family:Arial,Helvetica,sans-serif;color:#1f2937;max-width:560px">
<p>{{ __('Hello') }} {{ $providerName }},</p>
<p>{{ __('Your :plan plan on MedFind Solutions ends on :date.', ['plan' => $planName, 'date' => $expiresOn]) }}
   {{ __('After that your listing stops appearing in search results.') }}</p>
<p>{{ __('Renew now to stay visible. Your new plan starts when the current one ends, so you lose no days.') }}</p>
@include('emails._button', ['url' => $renewUrl, 'label' => __('Renew now')])
<p style="font-size:12px;color:#6b7280">{{ __('The button opens your plan page; no password needed. It works for 30 days.') }}</p>
</div>

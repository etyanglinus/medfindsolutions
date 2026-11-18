<div style="font-family:Arial,Helvetica,sans-serif;color:#1f2937;max-width:560px">
<p>{{ __('Hello') }} {{ $providerName }},</p>
<p>{{ __('We checked the M-Pesa code you submitted for your MedFind subscription but could not match it to a payment.') }}</p>
<p><strong>{{ __('Reason') }}:</strong> {{ $reason }}</p>
<p>{{ __('If you have paid, open your payment page and enter the code exactly as it appears in your M-Pesa confirmation SMS. If you have not paid yet, the paybill details are on the same page.') }}</p>
@include('emails._button', ['url' => $paymentUrl, 'label' => __('Open my payment page')])
<p style="font-size:12px;color:#6b7280">{{ __('Questions? Reply to this email and our team will help.') }}</p>
</div>

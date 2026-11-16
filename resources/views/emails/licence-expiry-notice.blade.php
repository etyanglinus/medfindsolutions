<div style="font-family:Arial,Helvetica,sans-serif;color:#1f2937;max-width:560px">
<p>{{ __('Hello') }} {{ $providerName }},</p>
@if($expired)
    <p>{{ __('The licence or accreditation document on your MedFind listing expired on :date.', ['date' => $expiresOn]) }}
    @if($listingHidden){{ __('Your listing is hidden from the directory until a current document is approved.') }}@endif</p>
@else
    <p>{{ __('The licence or accreditation document on your MedFind listing expires on :date.', ['date' => $expiresOn]) }}
    {{ __('Upload the renewed document now so your listing stays visible without interruption.') }}</p>
@endif
@include('emails._button', ['url' => $uploadUrl, 'label' => __('Upload a current licence')])
<p style="font-size:12px;color:#6b7280">{{ __('Our team reviews new documents, usually within one working day.') }}</p>
</div>

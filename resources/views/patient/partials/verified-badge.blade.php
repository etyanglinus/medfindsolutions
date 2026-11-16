{{-- "Licence verified" badge: the provider has a licence/accreditation an admin approved and it has not expired.
     Usage: @include('patient.partials.verified-badge', ['type' => 'doctor', 'id' => $doctor->id, 'full' => true]) --}}
@if(app(\App\Services\LicenceVerification::class)->isVerified($type, $id))
<span class="mf-verified{{ !empty($full) ? ' mf-verified--full' : '' }}" title="{{ __('Licence verified by MedFind: an approved, current professional licence is on file.') }}"><i class="fas fa-check-circle" aria-hidden="true"></i> {{ !empty($full) ? __('Licence verified by MedFind') : __('Verified') }}</span>
@once
<style>
.mf-verified{display:inline-flex;align-items:center;gap:4px;flex-shrink:0;white-space:nowrap;background:#e7f6ee;color:#05603a;border:1px solid #a6dfc0;font-size:.72rem;font-weight:700;line-height:1;padding:4px 8px;border-radius:999px;vertical-align:middle}
.mf-verified i{font-size:.75rem !important;width:auto !important;height:auto !important;margin:0 !important;color:#079455 !important;opacity:1 !important}
.mf-verified--full{font-size:.8rem;padding:6px 11px}
</style>
@endonce
@endif

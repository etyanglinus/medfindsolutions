@php
    $licences = app(\App\Services\LicenceVerification::class);
    $verificationOn = $licences->required();
    // Listed today but without a valid licence: these disappear when the switch is turned on.
    $unverifiedListed = [];
    foreach (['doctor' => \Modules\Doctor\app\Models\Doctor::class, 'clinic' => \Modules\Doctor\app\Models\Clinic::class] as $kind => $model) {
        $unverifiedListed[$kind] = $model::query()->where('status', 1)->get(['id'])->reject(fn ($p) => $licences->hasValidLicence($kind, $p->id))->count();
    }
@endphp
<div class="card mb-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h6 class="mb-1">{{ __('Require an approved licence to appear in the directory') }}
            <span class="badge {{ $verificationOn ? 'badge-success' : 'badge-secondary' }}">{{ $verificationOn ? __('ON') : __('OFF') }}</span></h6>
        <div class="small text-muted">{{ __('Applies to specialists and facilities. Schools and suppliers can only be published after verification. Expired licences are handled daily.') }}</div>
        @if(!$verificationOn && array_sum($unverifiedListed) > 0)
            <div class="small text-danger mt-1">{{ __('Turning this on would hide :doctors active specialists and :clinics active facilities that have no approved, unexpired licence. Approve their documents first.', ['doctors' => $unverifiedListed['doctor'], 'clinics' => $unverifiedListed['clinic']]) }}</div>
        @endif
    </div>
    @if(checkAdminHasPermission('doctor.update'))
    <form method="POST" action="{{ route('admin.listing-verification.update') }}">@csrf @method('PUT')
        <input type="hidden" name="required" value="{{ $verificationOn ? 0 : 1 }}">
        <button class="btn {{ $verificationOn ? 'btn-outline-secondary' : 'btn-primary' }}" onclick="return confirm('{{ $verificationOn ? __('Allow unverified providers in the directory again?') : __('Hide providers without an approved licence?') }}')">{{ $verificationOn ? __('Turn off') : __('Turn on') }}</button>
    </form>
    @endif
</div></div>

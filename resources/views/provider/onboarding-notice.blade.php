@if(isset($providerOnboarding) && !$providerOnboarding['complete'])
<div class="alert alert-warning mb-4">
    <strong>{{ __('Complete your account setup to unlock your dashboard.') }}</strong>
    <div>{{ __('Required: profile details, a profile photo or logo, and verification documents.') }}</div>
    @if($providerOnboarding['missing'])<div>{{ __('Missing details') }}: {{ implode(', ', $providerOnboarding['missing']) }}</div>@endif
    <a class="btn btn-sm btn-outline-primary mt-2" href="{{ $providerOnboarding['profile_url'] }}">{{ $providerOnboarding['photo'] ? __('Profile details') : __('Profile details and photo') }}</a>
    <a class="btn btn-sm btn-outline-primary mt-2" href="{{ $providerOnboarding['documents_url'] }}">{{ $providerOnboarding['document'] ? __('Documents submitted') : __('Upload verification documents') }}</a>
    <div class="small mt-2">{{ __('Document submission completes this step. Professional verification is reviewed separately by Medfind Solutions.') }}</div>
</div>
@endif
@if(isset($errors) && $errors->any())
<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif

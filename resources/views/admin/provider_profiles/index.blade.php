@extends('admin.master_layout')
@section('title')<title>{{ $title }}</title>@endsection
@section('admin-content')
@php
    $licences = app(\App\Services\LicenceVerification::class);
    $onboarding = app(\App\Services\ProviderOnboardingService::class);
    $filterUrl = fn ($f) => route('admin.provider-profiles.index', array_filter(['type' => $type, 'filter' => $f, 'q' => $search ?: null]));
    $noun = $type === 'school' ? __('school') : ($type === 'supplier' ? __('supplier') : __('provider'));
@endphp
<style>
.mf-pp-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:999px;border:1px solid #dee2e6;color:#495057;font-weight:600;font-size:13px;text-decoration:none;background:#fff}
.mf-pp-pill.active{background:#6777ef;border-color:#6777ef;color:#fff}
.mf-pp-pill .count{background:rgba(0,0,0,.08);border-radius:999px;padding:0 7px;font-size:12px}
.mf-pp-row{border:1px solid #edf0f3;border-radius:10px;padding:16px;margin-bottom:12px;background:#fff}
.mf-pp-avatar{width:48px;height:48px;border-radius:10px;background:#eef1ff;color:#6777ef;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;overflow:hidden;flex-shrink:0}
.mf-pp-avatar img{width:100%;height:100%;object-fit:cover}
.mf-pp-steps{list-style:none;padding:0;margin:0;font-size:13px}
.mf-pp-steps li{display:flex;align-items:flex-start;gap:8px;margin-bottom:6px}
.mf-pp-steps .ok{color:#47c363}.mf-pp-steps .no{color:#fc544b}.mf-pp-steps .wait{color:#ffa426}
.mf-pp-actions form{display:inline}
.mf-pp-more summary{list-style:none;cursor:pointer}
.mf-pp-more summary::-webkit-details-marker{display:none}
</style>
<div class="main-content"><section class="section"><x-admin.breadcrumb title="{{ $title }}" />
<div class="section-body">
<div class="card"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ $title }}</h4>
            <p class="text-muted small mb-0">{{ __('A :noun appears on the website when its licence is approved, it has a paid plan, and it is published.', ['noun' => $noun]) }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($type) @if(checkAdminHasPermission($type.'.create'))<a class="btn btn-primary" href="{{ route('admin.clients.create', ['type' => $type]) }}"><i class="fas fa-plus"></i> {{ $type === 'school' ? __('Add school') : __('Add supplier') }}</a>@endif @endif
            @if($type === 'supplier') @if(checkAdminHasPermission('supplier.view'))<a class="btn btn-outline-primary" href="{{ route('admin.supplier-listings.index') }}">{{ __('Product reviews') }}</a> <a class="btn btn-outline-primary" href="{{ route('admin.supplier-categories.index') }}">{{ __('Categories') }}</a>@endif @endif
            @if($type === 'school') @if(checkAdminHasPermission('school.view'))<a class="btn btn-outline-primary" href="{{ route('admin.school-courses.index') }}">{{ __('Course reviews') }}</a>@endif @endif
        </div>
    </div>

    @if(session('message'))<div class="alert alert-{{ ['error' => 'danger', 'warning' => 'warning'][session('alert-type')] ?? 'success' }}">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex flex-wrap gap-2">
            <a class="mf-pp-pill {{ !$filter ? 'active' : '' }}" href="{{ $filterUrl(null) }}">{{ __('All') }} <span class="count">{{ $counts['all'] }}</span></a>
            <a class="mf-pp-pill {{ $filter === 'listed' ? 'active' : '' }}" href="{{ $filterUrl('listed') }}"><i class="fas fa-globe-africa"></i> {{ __('On the website') }} <span class="count">{{ $counts['listed'] }}</span></a>
            <a class="mf-pp-pill {{ $filter === 'hidden' ? 'active' : '' }}" href="{{ $filterUrl('hidden') }}"><i class="fas fa-eye-slash"></i> {{ __('Hidden') }} <span class="count">{{ $counts['all'] - $counts['listed'] }}</span></a>
            <a class="mf-pp-pill {{ $filter === 'review' ? 'active' : '' }}" href="{{ $filterUrl('review') }}"><i class="fas fa-clipboard-check"></i> {{ __('Needs review') }} <span class="count">{{ $counts['review'] }}</span></a>
            @if($counts['suspended'])<a class="mf-pp-pill {{ $filter === 'suspended' ? 'active' : '' }}" href="{{ $filterUrl('suspended') }}">{{ __('Suspended') }} <span class="count">{{ $counts['suspended'] }}</span></a>@endif
        </div>
        <form method="GET" class="d-flex gap-2">
            @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
            @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
            <input class="form-control" name="q" value="{{ $search }}" placeholder="{{ __('Search name or email') }}" style="min-width:220px">
            <button class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div></div>

@forelse($profiles as $profile)
    @php
        $client = $profile->provider;
        $steps = $onboarding->status($client, $profile->provider_type);
        $profileIncomplete = !$steps['photo'] || !empty($steps['missing']);
        $docs = $client->documents ?? collect();
        $validLicence = $licences->hasValidLicence($profile->provider_type, $client->id);
        $pendingDocs = $docs->where('status', 'pending')->count();
        $listing = app(\App\Services\PublicDirectory::class)->status($profile->provider_type, $client);
        $published = $profile->status === 'published' && $profile->published_at;
        $logo = $client->logo ? (str_starts_with($client->logo, 'partner-') ? 'uploads/store/'.$client->logo : $client->logo) : null;
    @endphp
    <div class="mf-pp-row">
        <div class="row g-3 align-items-start">
            <div class="col-lg-4 d-flex gap-3">
                <div class="mf-pp-avatar">@if($logo)<img src="{{ asset($logo) }}" alt="">@else{{ mb_strtoupper(mb_substr($client->name, 0, 1)) }}@endif</div>
                <div class="min-w-0">
                    <div class="fw-bold" style="font-size:15px">{{ $client->name }}</div>
                    <div class="small text-muted">{{ $profile->provider_type === 'school' ? __('Healthcare school') : __('Medical supplier') }} · {{ __('joined :date', ['date' => $profile->created_at?->format('d M Y')]) }}</div>
                    <div class="small"><i class="far fa-envelope text-muted"></i> {{ $client->official_email }}@if($client->phone) · <i class="fas fa-phone text-muted"></i> {{ $client->phone }}@endif</div>
                    @if($profile->provider_type === 'school')
                        <div class="small text-muted mt-1">{{ collect([$client->institution_type, $client->ownership, $client->location])->filter()->implode(' · ') }}</div>
                    @else
                        <div class="small text-muted mt-1">{{ trans_choice(':count product|:count products', $client->products()->count()) }}@if($client->address) · {{ \Illuminate\Support\Str::limit($client->address, 40) }}@endif</div>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">
                <ul class="mf-pp-steps">
                    <li><i class="fas {{ $steps['complete'] || (!$steps['missing'] && $steps['photo']) ? 'fa-check-circle ok' : 'fa-times-circle no' }}"></i>
                        <span>{{ __('Profile') }}: @if(!$steps['missing'] && $steps['photo']){{ __('complete') }}@else<span class="text-muted">{{ __('missing') }} {{ implode(', ', array_merge($steps['missing'], $steps['photo'] ? [] : [__('logo')])) }}</span>@endif</span></li>
                    <li><i class="fas {{ $validLicence ? 'fa-check-circle ok' : ($pendingDocs ? 'fa-hourglass-half wait' : 'fa-times-circle no') }}"></i>
                        <span>{{ __('Licence') }}: @if($validLicence){{ __('approved') }}@elseif($pendingDocs)<strong>{{ trans_choice(':count document waiting for review|:count documents waiting for review', $pendingDocs) }}</strong>@else<span class="text-muted">{{ __('not uploaded') }}</span>@endif</span></li>
                    <li><i class="fas {{ $listing['plan'] && !$listing['expired'] ? 'fa-check-circle ok' : 'fa-times-circle no' }}"></i>
                        <span>{{ __('Plan') }}: @if($listing['plan']){{ $listing['plan'] }} · {{ $listing['expired'] ? __('ended') : __('until') }} {{ $listing['expires']?->format('d M Y') ?? __('no end date') }}@else<span class="text-muted">{{ __('none paid yet') }}</span>@endif</span></li>
                    <li><i class="fas {{ $published ? 'fa-check-circle ok' : ($profile->status === 'suspended' ? 'fa-ban no' : 'fa-circle text-muted') }}"></i>
                        <span>{{ __('Published') }}: @if($published){{ __('yes, since :date', ['date' => $profile->published_at->format('d M Y')]) }}@elseif($profile->status === 'suspended'){{ __('suspended') }}@else<span class="text-muted">{{ __('not yet') }}</span>@endif</span></li>
                </ul>
            </div>
            <div class="col-lg-4 mf-pp-actions">
                <div class="mb-2">
                    @if($listing['listed'])
                        <span class="badge bg-success badge-success"><i class="fas fa-globe-africa"></i> {{ __('On the website') }}</span>
                        <a class="small ms-1 ml-1" href="{{ $listing['url'] }}" target="_blank" rel="noopener">{{ __('View public page') }}</a>
                    @else
                        <span class="badge bg-secondary badge-secondary"><i class="fas fa-eye-slash"></i> {{ __('Hidden') }}</span>
                        <span class="small text-muted ms-1 ml-1">{{ implode(' · ', $listing['reasons']) }}</span>
                    @endif
                    @if($profileIncomplete)
                        <div class="small text-muted mt-1">{{ __('Profile not completed') }}</div>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-1">
                    @if(checkAdminHasPermission($profile->provider_type.'.update'))
                        @if(!$validLicence)
                            <a class="btn btn-sm btn-warning" href="{{ route('admin.provider-documents.index', ['provider_type' => $profile->provider_type, 'provider_id' => $profile->provider_id]) }}"><i class="fas fa-id-card"></i> {{ $pendingDocs ? __('Review licence') : __('View documents') }}</a>
                        @endif
                        @if(!$published && $profile->status !== 'suspended' && $profileIncomplete && $validLicence && $steps['document'] && $listing['plan'] && !$listing['expired'])
                            <form method="POST" action="{{ route('admin.provider-profiles.publish', $profile) }}">
                                @csrf
                                <button type="submit" name="allow_incomplete_listing" value="1" class="btn btn-sm btn-primary">{{ __('Show listing') }}</button>
                            </form>
                        @elseif(!$published && $profile->status !== 'suspended')
                            <form method="POST" action="{{ route('admin.provider-profiles.publish', $profile) }}">@csrf<button class="btn btn-sm btn-primary" @if(!$validLicence) title="{{ __('Approve the licence first') }}" @endif><i class="fas fa-upload"></i> {{ __('Publish') }}</button></form>
                        @elseif($published)
                            <form method="POST" action="{{ route('admin.provider-profiles.unpublish', $profile) }}" onsubmit="return confirm('{{ __('Take this listing off the website?') }}')">@csrf<button class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye-slash"></i> {{ __('Unpublish') }}</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.provider-profiles.publish', $profile) }}">@csrf<button class="btn btn-sm btn-outline-primary">{{ __('Lift suspension and publish') }}</button></form>
                        @endif
                    @endif
                </div>
                @include('admin.partials.activate-plan', ['type' => $profile->provider_type, 'client' => $client])
                <details class="mf-pp-more mt-2">
                    <summary class="small text-primary"><i class="fas fa-ellipsis-h"></i> {{ __('More actions') }}</summary>
                    <div class="border rounded p-2 mt-2 bg-light">
                        @if(checkAdminHasPermission($profile->provider_type.'.update'))
                        <form method="POST" action="{{ route('admin.provider-profiles.changes', $profile) }}" class="mb-2">@csrf
                            <label class="small mb-1">{{ __('Ask the provider to fix something') }}</label>
                            <div class="d-flex gap-1"><input name="admin_note" class="form-control form-control-sm" placeholder="{{ __('e.g. Upload a clearer licence') }}" required><button class="btn btn-sm btn-outline-secondary">{{ __('Send') }}</button></div>
                        </form>
                        @if($profile->status !== 'suspended')
                        <form method="POST" action="{{ route('admin.provider-profiles.suspend', $profile) }}" onsubmit="return confirm('{{ __('Suspend this provider? Their listing is hidden until you publish it again.') }}')">@csrf<button class="btn btn-sm btn-outline-danger mb-2">{{ __('Suspend') }}</button></form>
                        @endif
                        @endif
                        <a class="btn btn-sm btn-outline-secondary mb-2" href="{{ route('admin.provider-payments.index') }}">{{ __('Payment history') }}</a>
                        @if(checkAdminHasPermission($profile->provider_type.'.delete'))
                        <form method="POST" action="{{ route('admin.provider-profiles.destroy', $profile) }}" onsubmit="return confirm('{{ __('Delete this account and remove its listing? Login access will be revoked. Payment and creation history will remain.') }}')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> {{ __('Delete account') }}</button></form>
                        @endif
                    </div>
                </details>
            </div>
        </div>
    </div>
@empty
    <div class="card"><div class="card-body text-center text-muted py-5">
        @if($search || $filter){{ __('No :noun matches this filter.', ['noun' => $noun]) }} <a href="{{ $filterUrl(null) }}">{{ __('Show all') }}</a>@else{{ __('No :noun has signed up yet.', ['noun' => $noun]) }}@endif
    </div></div>
@endforelse
{{ $profiles->links() }}
</div></section></div>
@endsection

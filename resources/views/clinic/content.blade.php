@extends('clinic.master_layout')

@section('title')<title>{{ __('Clinic Content') }}</title>@endsection

@section('clinic-content')
<div class="main-content"><section class="section">
    <x-admin.breadcrumb title="{{ __('Clinic Content') }}" :list="[__('Dashboard') => route('clinic.dashboard'), __('Clinic Content') => '#']" />
    <div class="section-body">
        @if($subscription?->hasEntitlement('gallery_limit'))
        <div class="card"><div class="card-header"><h4>{{ __('Gallery') }} ({{ $clinic->galleryImages->count() }}/{{ $subscription->entitlement('gallery_limit') }})</h4></div><div class="card-body">
            <form class="row g-2 mb-4" action="{{ route('clinic.gallery.store') }}" method="POST" enctype="multipart/form-data">@csrf
                <div class="col-md-5"><input class="form-control" type="file" name="image" accept="image/*" required></div>
                <div class="col-md-5"><input class="form-control" name="caption" placeholder="{{ __('Optional image caption') }}"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('Upload') }}</button></div>
            </form>
            <div class="row">@forelse($clinic->galleryImages as $image)<div class="col-md-3 mb-3"><div class="card"><img src="{{ asset('storage/' . $image->image_path) }}" class="card-img-top" alt="{{ $image->caption }}" style="height:150px;object-fit:cover"><div class="card-body p-2"><small>{{ $image->caption }}</small><form action="{{ route('clinic.gallery.delete', $image) }}" method="POST" class="mt-2">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('Remove') }}</button></form></div></div></div>@empty <p class="text-muted">{{ __('No gallery images yet.') }}</p>@endforelse</div>
        </div></div>
        @endif
        @if($subscription?->hasEntitlement('branches'))
        <div class="card"><div class="card-header"><h4>{{ __('Branch Locations') }}</h4></div><div class="card-body">
            <form class="row g-2 mb-4" action="{{ route('clinic.branches.store') }}" method="POST">@csrf
                <div class="col-md-3"><input class="form-control" name="name" placeholder="{{ __('Branch name') }}" required></div><div class="col-md-5"><input class="form-control" name="address" placeholder="{{ __('Address') }}" required></div><div class="col-md-2"><input class="form-control" name="phone" placeholder="{{ __('Phone') }}"></div><div class="col-md-2"><button class="btn btn-primary w-100">{{ __('Add branch') }}</button></div>
            </form>
            @forelse($clinic->branches as $branch)<div class="d-flex justify-content-between border-top py-2"><span><strong>{{ $branch->name }}</strong> — {{ $branch->address }} {{ $branch->phone ? '(' . $branch->phone . ')' : '' }}</span><form action="{{ route('clinic.branches.delete', $branch) }}" method="POST">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('Remove') }}</button></form></div>@empty <p class="text-muted">{{ __('No additional branches yet.') }}</p>@endforelse
        </div></div>
        @endif
        @unless($subscription?->hasEntitlement('gallery_limit') || $subscription?->hasEntitlement('branches'))<div class="alert alert-info">{{ __('Gallery and branch locations are available on the Executive package.') }}</div>@endunless
    </div>
</section></div>
@endsection

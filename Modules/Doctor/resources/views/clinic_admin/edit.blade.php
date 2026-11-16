@extends('admin.master_layout')
@section('title')<title>{{ __('Edit Facility Listing') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section">
    <x-admin.breadcrumb title="{{ __('Edit Facility Listing') }}" :list="[__('Facilities') => route('admin.clinic-listing.index'), __('Edit') => '#']" />
    <div class="section-body"><div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.clinic-listing.update', $clinic) }}">@csrf @method('PUT')
        <div class="row">
            <div class="col-md-6 form-group"><label>{{ __('Name') }}</label><input class="form-control" name="name" value="{{ old('name', $clinic->name) }}" required></div>
            <div class="col-md-6 form-group"><label>{{ __('Specialty') }}</label><input class="form-control" name="specialty" value="{{ old('specialty', $clinic->specialty) }}"></div>
            <div class="col-md-6 form-group"><label>{{ __('Email') }}</label><input type="email" class="form-control" name="email" value="{{ old('email', $clinic->email) }}" required></div>
            <div class="col-md-6 form-group"><label>{{ __('Phone') }}</label><input class="form-control" name="phone" value="{{ old('phone', $clinic->phone) }}" required></div>
            <div class="col-md-6 form-group"><label>{{ __('Consultation Fee (KSh)') }}</label><input type="number" min="0" class="form-control" name="consultation_fee" value="{{ old('consultation_fee', $clinic->consultation_fee) }}"></div>
            <div class="col-md-12 form-group"><label>{{ __('Location') }}</label><input class="form-control" name="location" value="{{ old('location', $clinic->location) }}" placeholder="Nairobi"></div>
            <div class="col-md-12 form-group"><label>{{ __('Description') }}</label><textarea class="form-control" rows="4" name="description">{{ old('description', $clinic->description) }}</textarea></div>
            <div class="col-md-6 form-group"><label>{{ __('Insurance Accepted') }}</label><textarea class="form-control" name="insurance_accepted">{{ old('insurance_accepted', $clinic->insurance_accepted) }}</textarea></div>
            <div class="col-md-6 form-group"><label>{{ __('Operating Hours') }}</label><textarea class="form-control" name="operating_hours">{{ old('operating_hours', $clinic->operating_hours) }}</textarea></div>
        </div>
        <label><input type="checkbox" name="status" value="1" @checked(old('status', $clinic->status))> {{ __('Active') }}</label>
        <div class="mt-4"><button class="btn btn-primary">{{ __('Save Changes') }}</button><a class="btn btn-secondary" href="{{ route('admin.clinic-listing.index') }}">{{ __('Cancel') }}</a></div>
    </form></div></div></div>
</section></div>
@endsection

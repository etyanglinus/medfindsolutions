@extends('admin.master_layout')
@section('title')<title>{{ __('Create client account') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section">
    <x-admin.breadcrumb title="{{ __('Create client account') }}" />
    <div class="section-body"><div class="card"><div class="card-body">
        <div class="mb-4">
            @foreach(['doctor' => 'Doctor / Specialist', 'clinic' => 'Facility', 'school' => 'School', 'supplier' => 'Supplier'] as $key => $label)
                @adminCan(($key === 'clinic' ? 'facility' : $key).'.create')
                <a class="btn {{ $type === $key ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.clients.create', ['type' => $key]) }}">{{ __($label) }}</a>
                @endadminCan
            @endforeach
        </div>
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        @if(session('paystack_checkout_url'))<p><a class="btn btn-outline-primary" href="{{ session('paystack_checkout_url') }}" target="_blank" rel="noopener">{{ __('Open client Paystack checkout') }}</a></p>@endif
        <form method="POST" action="{{ route('admin.clients.store') }}">
            @csrf<input type="hidden" name="type" value="{{ $type }}">
            <div class="form-group"><label>{{ __('Client name') }}</label><input class="form-control" name="name" value="{{ old('name') }}" required maxlength="255"></div>
            <div class="form-group"><label>{{ __('Email') }}</label><input class="form-control" type="email" name="email" value="{{ old('email') }}" required></div>
            <div class="form-group"><label>{{ __('Phone') }}</label><input class="form-control" name="phone" value="{{ old('phone') }}" required maxlength="40"></div>
            @if($type === 'doctor')
            <div class="form-group"><label>{{ __('Department') }}</label><select class="form-control" name="department_id" required><option value="">{{ __('Select department') }}</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
            <div class="form-group"><label>{{ __('Location') }}</label><select class="form-control" name="location_id" required><option value="">{{ __('Select location') }}</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->name }}</option>@endforeach</select></div>
            @endif
            <div class="form-group"><label>{{ __('Subscription plan') }}</label>
                @foreach($plans as $plan)
                <div class="card border"><div class="card-body">
                    <label><input type="radio" name="package" value="{{ $plan['id'] }}" @checked(old('package') === $plan['id']) required> <strong>{{ $plan['name'] }}</strong> — KES {{ number_format($plan['price'], 2) }} / {{ $plan['duration'] }}</label>
                    <ul>@foreach($plan['features'] as $feature)<li>{{ $feature }}</li>@endforeach</ul>
                </div></div>
                @endforeach
            </div>
            <p>{{ __('The client receives a Paystack payment link. The account activates and login details are emailed only after Paystack confirms payment.') }}</p>
            <button class="btn btn-primary">{{ __('Create client account') }}</button>
        </form>
    </div></div></div>
</section></div>
@endsection

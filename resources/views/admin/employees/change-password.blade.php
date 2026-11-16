@extends('backend_layouts.guest')
@section('title')<title>{{ __('Choose your employee password') }}</title>@endsection
@section('content')
<div class="container py-5"><div class="card"><div class="card-body"><h3>{{ __('Choose your employee password') }}</h3><p>{{ __('Change the temporary password from your invitation before continuing.') }}</p>
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="POST" action="{{ route('admin.employee-password.update') }}">@csrf @method('PUT')
<div class="form-group"><label>{{ __('Temporary password') }}</label><input name="current_password" type="password" autocomplete="current-password" required class="form-control"></div>
<div class="form-group"><label>{{ __('New password (at least 12 characters)') }}</label><input name="password" type="password" minlength="12" autocomplete="new-password" required class="form-control"></div>
<div class="form-group"><label>{{ __('Confirm new password') }}</label><input name="password_confirmation" type="password" autocomplete="new-password" required class="form-control"></div>
<button class="btn btn-primary">{{ __('Save password and continue') }}</button></form>
<form method="POST" action="{{ route('admin.logout') }}" class="mt-3">@csrf<button class="btn btn-outline-secondary">{{ __('Sign out') }}</button></form></div></div></div>
@endsection

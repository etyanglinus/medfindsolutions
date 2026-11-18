@extends('doctor.master_layout')
@section('title')
    <title>{{ __('Dashboard') }} — {{ $doctor->name }}</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-body px-1 pt-2">
      @include('provider.dashboard.overview')
    </div>
  </section>
</div>
@endsection

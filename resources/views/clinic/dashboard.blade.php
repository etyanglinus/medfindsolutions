@extends('clinic.master_layout')
@section('title')
    <title>{{ __('Facility Dashboard') }} — {{ $clinic->name }}</title>
@endsection

@section('clinic-content')
<div class="main-content">
  <section class="section">
    <div class="section-body px-1 pt-2">
      @include('provider.dashboard.overview')
    </div>
  </section>
</div>
@endsection

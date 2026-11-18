@extends('admin.master_layout')
@section('title')<title>{{ __('Tafuta assistant') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section"><x-admin.breadcrumb title="{{ __('Tafuta assistant') }}" />
<div class="section-body"><div class="row"><div class="col-lg-7">
<div class="card"><div class="card-header"><h4>{{ __('Tafuta on the website') }}</h4></div><div class="card-body">
@if(session('message'))<div class="alert alert-{{ session('alert-type') === 'warning' ? 'warning' : 'success' }}">{{ session('message') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('admin.tafuta.update') }}">@csrf @method('PUT')
    <div class="form-group">
        <input type="hidden" name="tafuta_status" value="0">
        <div class="form-check form-switch custom-control custom-switch">
            <input class="form-check-input custom-control-input" type="checkbox" role="switch" id="tafuta-on" name="tafuta_status" value="1" @checked($on)>
            <label class="form-check-label custom-control-label fw-bold" for="tafuta-on">{{ __('Show "Ask Tafuta" on the public website') }}</label>
        </div>
    </div>
    <div class="form-group">
        <label>{{ __('Answers per day (all visitors together)') }}</label>
        <input type="number" min="0" class="form-control" name="tafuta_daily_limit" value="{{ old('tafuta_daily_limit', $dailyLimit) }}" style="max-width:200px">
        <small class="text-muted">{{ __('A spending cap. When it is reached, Tafuta points visitors to the directory until midnight. 0 means no limit.') }}</small>
    </div>
    <button class="btn btn-primary">{{ __('Save') }}</button>
</form>
</div></div>
</div><div class="col-lg-5">
<div class="card"><div class="card-header"><h4>{{ __('Status') }}</h4></div><div class="card-body">
<p class="mb-2">{{ __('API key on the server') }}: @if($keySet)<span class="badge bg-success badge-success">{{ __('configured') }}</span>@else<span class="badge bg-danger badge-danger">{{ __('missing') }}</span>@endif</p>
@unless($keySet)<p class="small text-muted">{{ __('Add ANTHROPIC_API_KEY=... to the .env file on the server (cPanel File Manager), then run: php artisan config:clear. The key is never stored in the database or shown here.') }}</p>@endunless
<p class="mb-2">{{ __('Model') }}: <code>{{ $model }}</code></p>
<p class="mb-0">{{ __('Answered today') }}: <strong>{{ $answeredToday }}</strong>@if($dailyLimit) / {{ $dailyLimit }}@endif</p>
</div></div>
<div class="card"><div class="card-body small text-muted">
{{ __('Tafuta only recommends providers that are listed on the website, never gives medical advice, and shows emergency numbers first when a message sounds urgent. Chats are deleted 7 days after the last message.') }}
</div></div>
</div></div></div>
</section></div>
@endsection

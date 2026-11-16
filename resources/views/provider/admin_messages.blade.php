@extends($layout)

@section('title')
    <title>{{ __('Admin Notifications') }}</title>
@endsection

@section($contentSection)
<div class="main-content">
    <section class="section">
        <x-admin.breadcrumb title="{{ __('Admin Notifications') }}" :list="[__('Dashboard') => route($dashboardRoute, $dashboardRouteParameters), __('Admin Notifications') => '#']" />
        <div class="section-body">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{ __('Messages from MedFind administration') }}</h4></div>
                <div class="card-body" style="max-height:480px;overflow-y:auto">
                    @forelse($messages as $message)
                        <div class="mb-3 {{ $message->sender_type === 'provider' ? 'text-end' : '' }}">
                            <span class="d-inline-block p-3 rounded {{ $message->sender_type === 'provider' ? 'bg-primary text-white' : 'bg-light' }}" style="max-width:82%">{{ $message->message }}</span>
                            <small class="d-block text-muted mt-1">{{ $message->sender_type === 'provider' ? __('You') : __('MedFind Admin') }} ? {{ $message->created_at->format('d M Y H:i') }}</small>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ __('There are no administration messages yet.') }}</p>
                    @endforelse
                </div>
                <div class="card-footer">
                    <form method="POST" action="{{ route($storeRoute, $storeRouteParameters) }}">
                        @csrf
                        <label for="provider-message" class="form-label">{{ __('Reply to administration') }}</label>
                        <textarea id="provider-message" name="message" class="form-control @error('message') is-invalid @enderror" rows="3" required maxlength="5000"></textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <button class="btn btn-primary mt-2"><i class="fas fa-paper-plane mr-1"></i>{{ __('Send reply') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

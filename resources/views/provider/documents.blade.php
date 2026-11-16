@extends($layout)

@section($contentSection)

<div class="main-content">
@include('provider.onboarding-notice')<section class="section">
    <x-admin.breadcrumb title="{{ __('License Verification') }}" :list="[__('Dashboard') => route($type . '.dashboard'), __('License Verification') => '#']" />
    <div class="section-body"><div class="row"><div class="col-lg-6"><div class="card"><div class="card-header"><h4>{{ __('Submit Current License') }}</h4></div><div class="card-body"><form action="{{ route($type . '.documents.store') }}" method="POST" enctype="multipart/form-data">@csrf
        <div class="form-group"><label>{{ __('Licensing Body') }}</label><select class="form-control select2" name="licensing_body" required>@foreach($licensingBodies as $body)<option value="{{ $body->name }}">{{ $body->name }}</option>@endforeach</select></div>
        <div class="form-group"><label>{{ __('Verification Body (if different)') }}</label><input class="form-control" name="verification_body" maxlength="255"></div>
        <div class="form-group"><label>{{ __('Registration / License Number') }}</label><input class="form-control" name="registration_number" required></div>
        <div class="form-group"><label>{{ __('Accreditation Expiry') }}</label><input class="form-control" type="date" name="accreditation_expires_at" min="{{ today()->toDateString() }}"></div>
        <div class="form-group"><label>{{ __('License Document') }}</label><input class="form-control" type="file" name="license" accept=".pdf,.jpg,.jpeg,.png" required><small class="text-muted">PDF, JPG, or PNG; maximum 10 MB.</small></div>
        <button class="btn btn-primary">{{ __('Submit for Review') }}</button>
    </form></div></div></div><div class="col-lg-6"><div class="card"><div class="card-header"><h4>{{ __('Submission History') }}</h4></div><div class="card-body">@forelse($documents as $document)<div class="border-bottom py-2"><strong>{{ $document->licensing_body }}</strong><div class="small">{{ $document->registration_number }} · <span class="badge badge-{{ $document->status === 'approved' ? 'success' : ($document->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($document->status) }}</span></div>@if($document->admin_note)<div class="small text-muted">{{ $document->admin_note }}</div>@endif @foreach($document->reviews as $review)<div class="small text-muted">{{ $review->created_at->format('d M Y H:i') }}: {{ ucfirst($review->status) }} @if($review->note)� {{ $review->note }}@endif</div>@endforeach</div>@empty <p class="text-muted">{{ __('No license submitted yet.') }}</p>@endforelse</div></div></div></div></div>
</section></div>
@endsection

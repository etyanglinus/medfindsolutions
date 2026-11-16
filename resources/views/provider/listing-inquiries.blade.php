@extends($layout)
@section($contentSection)
<div class="{{ in_array($type, ['doctor', 'clinic']) ? 'main-content' : '' }}"><section class="section"><div class="section-header"><h1>{{ __('Listing inquiries') }}</h1></div>
<div class="card"><div class="card-body">
@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
<form method="GET" class="mb-3"><label>{{ __('Filter by status') }}</label><select class="form-control" name="status" onchange="this.form.submit()"><option value="">{{ __('All statuses') }}</option>@foreach(['new','contacted','closed','quoted','reviewed','replied'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></form>
<div class="table-responsive"><table class="table"><thead><tr><th>{{ __('Contact') }}</th><th>{{ __('Message') }}</th><th>{{ __('Received') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
@forelse($inquiries as $inquiry)<tr><td>{{ $inquiry->patient_name ?? $inquiry->name }}<br>{{ $inquiry->phone }}<br>{{ $inquiry->email }}</td><td>{{ $inquiry->reason ?? $inquiry->message }}</td><td>{{ $inquiry->created_at->format('d M Y H:i') }}</td><td><p>{{ ucfirst($inquiry->status) }}</p><form method="POST" action="{{ route('listing.inbox.update', ['type' => $type, 'inquiry' => $inquiry->id]) }}">@csrf @method('PATCH')<select name="status" class="form-control">@foreach(['new','contacted','closed'] as $status)<option value="{{ $status }}" @selected($inquiry->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select><button class="btn btn-primary btn-sm">{{ __('Update status') }}</button></form></td></tr>@empty<tr><td colspan="4">{{ __('No matching inquiries.') }}</td></tr>@endforelse
</tbody></table></div>{{ $inquiries->links() }}</div></div></section></div>
@endsection

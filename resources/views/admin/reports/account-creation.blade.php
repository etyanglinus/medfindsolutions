@extends('admin.master_layout')
@section('title')<title>{{ __('Account creation report') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section">
<x-admin.breadcrumb title="{{ __('Account creation report') }}" />
<div class="section-body"><div class="card"><div class="card-body">
<form method="GET" class="mb-4"><div class="row">
<div class="col-md-2 form-group"><label>{{ __('Account type') }}</label><select name="type" class="form-control"><option value="">{{ __('All types') }}</option>@foreach(['doctor' => 'Specialist', 'clinic' => 'Facility', 'school' => 'School', 'supplier' => 'Supplier'] as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ __($label) }}</option>@endforeach</select></div>
<div class="col-md-2 form-group"><label>{{ __('Created by') }}</label><select name="source" class="form-control"><option value="">{{ __('All sources') }}</option>@foreach(['admin' => 'Admin', 'employee' => 'Employee', 'self' => 'Self-registration', 'system' => 'System', 'unknown' => 'Unknown (historical)'] as $key => $label)<option value="{{ $key }}" @selected(request('source') === $key)>{{ __($label) }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>{{ __('Admin / Employee') }}</label><select name="creator_id" class="form-control"><option value="">{{ __('All creators') }}</option>@foreach($creators as $creator)<option value="{{ $creator->creator_admin_id }}" @selected(request('creator_id') == $creator->creator_admin_id)>{{ $creator->creator_name }} (#{{ $creator->creator_admin_id }})</option>@endforeach</select></div>
<div class="col-md-2 form-group"><label>{{ __('From') }}</label><input type="date" class="form-control" name="from" value="{{ request('from') }}"></div>
<div class="col-md-2 form-group"><label>{{ __('To') }}</label><input type="date" class="form-control" name="to" value="{{ request('to') }}"></div>
</div><button class="btn btn-primary">{{ __('Generate report') }}</button> <button class="btn btn-outline-primary" name="export" value="csv">{{ __('Download CSV') }}</button> <a class="btn btn-outline-secondary" href="{{ route('admin.reports.account-creation') }}">{{ __('Clear') }}</a></form>
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="mb-3">@foreach($summary as $source => $total)<span class="badge badge-light mr-2">{{ ucfirst($source) }}: {{ $total }}</span>@endforeach</div>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>{{ __('Account type') }}</th><th>{{ __('Client') }}</th><th>{{ __('Source') }}</th><th>{{ __('Creator') }}</th><th>{{ __('Created at') }}</th></tr></thead><tbody>
@forelse($accounts as $account)<tr><td>{{ $account->provider_type === 'clinic' ? __('Facility') : ucfirst($account->provider_type) }}</td><td>{{ $account->client_name }}<br><small>{{ $account->client_email }}</small></td><td>{{ $account->source === 'self' ? __('Self-registration') : ucfirst($account->source) }}</td><td>{{ $account->creator_name ?: '—' }}@if($account->creator_admin_id)<br><small>#{{ $account->creator_admin_id }} · {{ $account->creator_email }}</small>@endif</td><td>{{ $account->created_at?->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="5">{{ __('No matching accounts.') }}</td></tr>@endforelse
</tbody></table></div>{{ $accounts->links() }}
<p class="text-muted">{{ __('Historical accounts without a recorded creator are marked Unknown. New accounts record their creator automatically.') }}</p>
</div></div></div></section></div>
@endsection

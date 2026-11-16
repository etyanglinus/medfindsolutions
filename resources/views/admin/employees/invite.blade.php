@extends('admin.master_layout')
@section('title')<title>{{ __('Invite Sales employees') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section"><div class="section-header"><h1>{{ __('Invite Sales employees') }}</h1></div><div class="section-body">
@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card"><div class="card-body"><p>{{ __('Invited employees receive the Sales role automatically. They can onboard clients and choose subscription plans. Website settings, payments, verification, deletion and employee administration are restricted to the owner.') }}</p>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.employees.invite.store') }}">@csrf
<div class="form-group"><label for="emails">{{ __('Employee email addresses') }}</label><textarea id="emails" name="emails" class="form-control" rows="5" placeholder="employee@example.com">{{ old('emails') }}</textarea><small>{{ __('One email per line, or separated by commas. Maximum 100 employees per invitation batch.') }}</small></div>
<div class="form-group"><label for="csv">{{ __('Or upload a CSV') }}</label><input id="csv" type="file" name="csv" accept=".csv,.txt" class="form-control"><small>{{ __('CSV columns: email,name. Name is optional. Existing email addresses are rejected without changing those accounts.') }}</small></div>
<button class="btn btn-primary">{{ __('Create Sales accounts and email invitations') }}</button></form></div></div>
<div class="card"><div class="card-header"><h4>{{ __('Employees and invitation status') }}</h4></div><div class="card-body table-responsive"><table class="table"><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Status') }}</th><th>{{ __('Invitation') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
@forelse($employees as $employee)<tr><td>{{ $employee->name }}</td><td>{{ $employee->email }}</td><td>{{ $employee->status }}</td><td>{{ !$employee->invited_at ? __('Existing account') : (!$employee->must_change_password ? __('Password changed') : ($employee->invitation_failed_at ? __('Delivery failed') : ($employee->invitation_sent_at ? __('Sent — awaiting password change') : __('Queued'))) ) }}</td><td>@if($employee->must_change_password)<form method="POST" action="{{ route('admin.employees.invite.resend', $employee) }}">@csrf<button class="btn btn-sm btn-outline-primary">{{ __('Send new temporary password') }}</button></form>@endif</td></tr>@empty<tr><td colspan="5">{{ __('No employees yet.') }}</td></tr>@endforelse
</tbody></table>{{ $employees->links() }}</div></div></div></section></div>
@endsection

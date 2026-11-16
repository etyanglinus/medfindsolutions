@extends('admin.master_layout')
@section('title')<title>{{ __('Subscription Plans') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section">
    <x-admin.breadcrumb title="{{ __('Subscription Plans') }}" :list="[__('Dashboard') => route('admin.dashboard'), __('Subscription Plans') => '#']" />
    <div class="section-body"><div class="card"><div class="card-header d-flex justify-content-between"><h4>{{ __('Subscription Plans') }}</h4><div><a class="btn btn-primary" href="{{ route('admin.subscriptions.packages.create') }}">{{ __('Add Plan') }}</a></div></div>
    <div class="card-body table-responsive"><table class="table table-striped"><thead><tr><th>{{ __('Provider') }}</th><th>{{ __('Plan') }}</th><th>{{ __('Code') }}</th><th>{{ __('Price') }}</th><th>{{ __('Duration') }}</th><th>{{ __('Status') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
    @forelse($packages as $package)<tr><td>{{ ucfirst($package->provider_type) }}</td><td>{{ $package->name }}</td><td><code>{{ $package->code }}</code></td><td>KSh {{ number_format($package->price) }}</td><td>{{ $package->duration_months }} {{ __('months') }}</td><td><span class="badge bg-{{ $package->is_active ? 'success' : 'secondary' }}">{{ $package->is_active ? __('Active') : __('Inactive') }}</span></td><td><a class="btn btn-sm btn-warning" href="{{ route('admin.subscriptions.packages.edit', $package) }}"><i class="fa fa-edit"></i></a><form class="d-inline" method="POST" action="{{ route('admin.subscriptions.packages.destroy', $package) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('{{ __('Delete this plan?') }}')"><i class="fa fa-trash"></i></button></form></td></tr>
    @empty<tr><td colspan="7" class="text-center">{{ __('No plans found.') }}</td></tr>@endforelse
    </tbody></table></div></div></div>
</section></div>
@endsection

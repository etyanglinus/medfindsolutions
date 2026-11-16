@extends('admin.master_layout')
@section('title')
    <title>{{ __('Doctor Subscription Requests') }}</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <x-admin.breadcrumb title="{{ __('Doctor Subscriptions') }}" :list="[
            __('Dashboard') => route('admin.dashboard'),
            __('Doctor Subscriptions') => '#',
        ]" />

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body p-0">
                            <form action="{{ route('admin.subscriptions.doctor.index') }}" method="GET"
                                  onchange="$(this).trigger('submit')" class="card-body">
                                <div class="row">
                                    <div class="col-md-5 form-group mb-3 mb-md-0">
                                        <div class="input-group">
                                            <x-admin.form-input name="keyword" placeholder="{{ __('Search by name or email') }}"
                                                value="{{ request('keyword') }}" />
                                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-3 form-group mb-3 mb-md-0">
                                        <x-admin.form-select name="status" class="form-select">
                                            <x-admin.select-option value="" text="{{ __('All Statuses') }}" />
                                            <x-admin.select-option :selected="request('status')=='pending'"   value="pending"   text="{{ __('Pending') }}" />
                                            <x-admin.select-option :selected="request('status')=='approved'"  value="approved"  text="{{ __('Approved') }}" />
                                            <x-admin.select-option :selected="request('status')=='rejected'"  value="rejected"  text="{{ __('Rejected') }}" />
                                        </x-admin.form-select>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>{{ __('Doctor Subscription Requests') }}</h4>
                            <a href="{{ route('admin.subscriptions.clinic.index') }}" class="btn btn-outline-primary btn-sm">
                                {{ __('View Clinic Subscriptions') }}
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Doctor') }}</th>
                                            <th>{{ __('Package') }}</th>
                                            <th>{{ __('Amount (KSh)') }}</th>
                                            <th>{{ __('Duration') }}</th>
                                            <th>{{ __('Payment Method') }}</th>
                                            <th>{{ __('Transaction Code') }}</th>
                                            <th>{{ __('Submitted') }}</th>
                                            <th>{{ __('Expiry') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($subscriptions as $sub)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <div>{{ $sub->doctor?->name }}</div>
                                                <small class="text-muted">{{ $sub->doctor?->email }}</small>
                                            </td>
                                            <td>{{ $sub->package_name }}</td>
                                            <td>{{ number_format($sub->package_price) }}</td>
                                            <td>{{ $sub->plan_duration }} {{ __('mo') }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $sub->payment_method)) }}</td>
                                            <td><code>{{ $sub->transaction_code }}</code></td>
                                            <td>{{ $sub->created_at->format('d M Y') }}</td>
                                            <td>{{ $sub->expires_at?->format('d M Y') ?? '—' }}</td>
                                            <td>
                                                @php($expired = $sub->status === 'approved' && $sub->expires_at?->isPast())
                                                <span class="badge bg-{{ $expired ? 'secondary' : ($sub->status === 'approved' ? 'success' : ($sub->status === 'rejected' ? 'danger' : 'warning')) }}">
                                                    {{ $expired ? __('Expired') : __(ucfirst($sub->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.subscriptions.doctor.show', $sub->id) }}"
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @empty
                                            <tr><td colspan="11" class="text-center text-muted py-4">{{ __('No subscription requests found.') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="float-right mt-2">
                                {{ $subscriptions->onEachSide(0)->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

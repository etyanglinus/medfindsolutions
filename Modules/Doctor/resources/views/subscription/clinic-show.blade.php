@extends('admin.master_layout')
@section('title')
    <title>{{ __('Clinic Subscription Detail') }}</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <x-admin.breadcrumb title="{{ __('Subscription Detail') }}" :list="[
            __('Dashboard') => route('admin.dashboard'),
            __('Clinic Subscriptions') => route('admin.subscriptions.clinic.index'),
            __('Detail') => '#',
        ]" />

        <div class="section-body">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>{{ __('Clinic Subscription Request') }}</h4>
                            <x-admin.back-button :href="route('admin.subscriptions.clinic.index')" />
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <tr><td class="fw-bold" width="35%">{{ __('Clinic Name') }}</td>
                                    <td>{{ $subscription->clinic?->name }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Specialty') }}</td>
                                    <td>{{ $subscription->clinic?->specialty }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Email') }}</td>
                                    <td>{{ $subscription->clinic?->email }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Phone') }}</td>
                                    <td>{{ $subscription->clinic?->phone }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Package') }}</td>
                                    <td>{{ $subscription->package_name }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Amount') }}</td>
                                    <td>KSh {{ number_format($subscription->package_price) }}</td></tr>
                                @if($subscription->coupon_code)<tr><td class="fw-bold">{{ __('Price before discount') }}</td><td>KSh {{ number_format($subscription->original_price, 2) }}</td></tr><tr><td class="fw-bold">{{ __('Coupon / savings') }}</td><td>{{ $subscription->coupon_code }} · KSh {{ number_format($subscription->discount_amount, 2) }}</td></tr>@endif
                                <tr><td class="fw-bold">{{ __('Duration') }}</td>
                                    <td>{{ $subscription->plan_duration }} {{ __('month(s)') }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Payment Method') }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $subscription->payment_method)) }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Bank / Payment') }}</td>
                                    <td>{{ $subscription->bank_name }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Account / Phone') }}</td>
                                    <td>{{ $subscription->account_number }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Branch / Paybill') }}</td>
                                    <td>{{ $subscription->routing_number }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Branch / Town') }}</td>
                                    <td>{{ $subscription->branch }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Transaction Code') }}</td>
                                    <td><code>{{ $subscription->transaction_code }}</code></td></tr>
                                <tr><td class="fw-bold">{{ __('Submitted At') }}</td>
                                    <td>{{ $subscription->created_at->format('d M Y, H:i') }}</td></tr>
                                <tr><td class="fw-bold">{{ __('Status') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $subscription->status === 'approved' ? 'success' : ($subscription->status === 'rejected' ? 'danger' : 'warning') }} fs-6">
                                            {{ __(ucfirst($subscription->status)) }}
                                        </span>
                                    </td></tr>
                                @if($subscription->admin_note)
                                <tr><td class="fw-bold">{{ __('Admin Note') }}</td>
                                    <td>{{ $subscription->admin_note }}</td></tr>
                                @endif
                                @if($subscription->approved_at)
                                <tr><td class="fw-bold">{{ __('Approved At') }}</td>
                                    <td>{{ $subscription->approved_at->format('d M Y, H:i') }}</td></tr>
                                @endif
                            </table>

                            @if($subscription->status === 'pending')
                            <hr>
                            <div class="row g-3 mt-1">
                                {{-- Approve --}}
                                <div class="col-md-6">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">{{ __('Approve Subscription') }}</div>
                                        <div class="card-body">
                                            <form action="{{ route('admin.subscriptions.clinic.approve', $subscription->id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="mb-3">
                                                    <label>{{ __('Note (optional)') }}</label>
                                                    <textarea name="admin_note" rows="3" class="form-control"
                                                        placeholder="{{ __('e.g. Payment confirmed via M-Pesa') }}"></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-success w-100"
                                                    onclick="return confirm('{{ __('Approve this subscription and activate the clinic?') }}')">
                                                    <i class="fas fa-check me-1"></i> {{ __('Approve & Activate Clinic') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                {{-- Reject --}}
                                <div class="col-md-6">
                                    <div class="card border-danger">
                                        <div class="card-header bg-danger text-white">{{ __('Reject Subscription') }}</div>
                                        <div class="card-body">
                                            <form action="{{ route('admin.subscriptions.clinic.reject', $subscription->id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="mb-3">
                                                    <label>{{ __('Rejection Reason') }} <span class="text-danger">*</span></label>
                                                    <textarea name="admin_note" rows="3" class="form-control @error('admin_note') is-invalid @enderror"
                                                        placeholder="{{ __('e.g. Transaction code not found') }}" required></textarea>
                                                    @error('admin_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <button type="submit" class="btn btn-danger w-100"
                                                    onclick="return confirm('{{ __('Reject this subscription request?') }}')">
                                                    <i class="fas fa-times me-1"></i> {{ __('Reject') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

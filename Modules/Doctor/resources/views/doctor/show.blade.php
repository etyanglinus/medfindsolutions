@extends('admin.master_layout')
@section('title')
    <title>{{ __('Doctor Details') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Doctor Details') }}" :list="[
                __('Dashboard') => route('admin.dashboard'),
                __('Doctor Details') => '#',
            ]" />

            <div class="section-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4>{{ __('Doctor Details') }}</h4>
                                <x-admin.back-button :href="route('admin.doctor.index')" />
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tr>
                                            <td>{{ __('Name') }}</td>
                                            <td>{{ $doctor->name }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Email') }}</td>
                                            <td>{{ $doctor->email }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Phone') }}</td>
                                            <td>{{ $doctor->phone }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Specialty') }}</td>
                                            <td>{{ $doctor->department?->name }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Services') }}</td>
                                            <td>{{ $doctor->services->pluck('name')->join(', ') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Licensing Body') }}</td>
                                            <td>{{ $doctor->designations }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Accepted Insurance') }}</td>
                                            <td>{{ $doctor->accepted_insurance ?? __('N/A') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Show on homepage') }}</td>
                                            <td>
                                                @if ($doctor->show_homepage == 1)
                                                    <span class="badge bg-success">{{ __('Yes') }}</span>
                                                @else
                                                    <span class="badge bg-danger">{{ __('No') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Status') }}</td>
                                            <td>
                                                @if ($doctor->status == 1)
                                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Email verified') }}</td>
                                            <td>
                                                @if ($doctor->email_verified_at)
                                                    <span class="badge bg-success">{{ __('Yes') }}</span>
                                                @else
                                                    <span class="badge bg-danger">{{ __('No') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if ($doctor->latestSubscription)
                                            <tr>
                                                <td>{{ __('Requested Package') }}</td>
                                                <td>{{ $doctor->latestSubscription->package_name }}</td>
                                            </tr>
                                            <tr>
                                                <td>{{ __('Package Amount') }}</td>
                                                <td>{{ currency($doctor->latestSubscription->package_price) }}</td>
                                            </tr>
                                            <tr>
                                                <td>{{ __('Payment Status') }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $doctor->latestSubscription->status == 'approved' ? 'success' : ($doctor->latestSubscription->status == 'rejected' ? 'danger' : 'warning') }}">
                                                        {{ __(ucfirst($doctor->latestSubscription->status)) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td>{{ __('Created At') }}</td>
                                            <td>{{ formattedDateTime($doctor->created_at) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@extends('admin.master_layout')
@section('title')
    <title>{{ __('Facility Details') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Facility Details') }}" :list="[
                __('Dashboard') => route('admin.dashboard'),
                __('Facility Details') => '#',
            ]" />

            <div class="section-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4>{{ __('Facility Details') }}</h4>
                                <x-admin.back-button :href="route('admin.facility.index')" />
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tr>
                                            <td>{{ __('Name') }}</td>
                                            <td>{{ $facility->name }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Email') }}</td>
                                            <td>{{ $facility->email }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Phone') }}</td>
                                            <td>{{ $facility->phone }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Specialty') }}</td>
                                            <td>{{ $facility->department?->name }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Services') }}</td>
                                            <td>{{ $facility->services->pluck('name')->join(', ') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Licensing Body') }}</td>
                                            <td>{{ $facility->licensing_body }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Accepted Insurance') }}</td>
                                            <td>{{ $facility->accepted_insurance ?? __('N/A') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Show on homepage') }}</td>
                                            <td>
                                                @if ($facility->show_homepage == 1)
                                                    <span class="badge bg-success">{{ __('Yes') }}</span>
                                                @else
                                                    <span class="badge bg-danger">{{ __('No') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Status') }}</td>
                                            <td>
                                                @if ($facility->status == 1)
                                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Created At') }}</td>
                                            <td>{{ formattedDateTime($facility->created_at) }}</td>
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

@extends('admin.master_layout')
@section('title')
    <title>{{ __('Doctor List') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            {{-- Breadcrumb --}}
            <x-admin.breadcrumb title="{{ __('Doctor List') }}" :list="[
                __('Dashboard') => route('admin.dashboard'),
                __('Doctor List') => '#',
            ]" />
            <div class="section-body">
                <div class="mt-4 row">
                    {{-- Search filter --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-0">
                                <form action="{{ route('admin.doctor.index') }}" method="GET"
                                    onchange="$(this).trigger('submit')" class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 form-group mb-3 mb-md-0">
                                            <div class="input-group">
                                                <x-admin.form-input name="keyword" placeholder="{{ __('Name, email, or registration details') }}"
                                                    value="{{ request()->get('keyword') }}" />
                                                <button class="btn btn-primary" type="submit"><i
                                                        class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-md-2 form-group mb-3 mb-md-0"><x-admin.form-select name="department_id" class="form-select"><x-admin.select-option value="" text="{{ __('All Specialties') }}" />@foreach($departments as $department)<x-admin.select-option :selected="request('department_id') == $department->id" value="{{ $department->id }}" text="{{ $department->name }}" />@endforeach</x-admin.form-select></div>
                                        <div class="col-md-2 form-group mb-3 mb-md-0"><x-admin.form-select name="plan_code" class="form-select"><x-admin.select-option value="" text="{{ __('All Subscription Plans') }}" />@foreach($plans as $plan)<x-admin.select-option :selected="request('plan_code') == $plan->code" value="{{ $plan->code }}" text="{{ $plan->name }}" />@endforeach</x-admin.form-select></div>
                                        <div class="col-md-1 form-group mb-3 mb-md-0"><a class="btn btn-outline-secondary w-100" href="{{ route('admin.doctor.index') }}">{{ __('Clear') }}</a></div>
                                        <div class="col-md-2 form-group mb-3 mb-md-0">
                                            <x-admin.form-select name="show_homepage" id="show_homepage"
                                                class="form-select">
                                                <x-admin.select-option value="" text="{{ __('Show Homepage') }}" />
                                                <x-admin.select-option :selected="request('show_homepage') == '1'" value="1"
                                                    text="{{ __('Yes') }}" />
                                                <x-admin.select-option :selected="request('show_homepage') == '0'" value="0"
                                                    text="{{ __('No') }}" />
                                            </x-admin.form-select>
                                        </div>
                                        <div class="col-md-2 form-group mb-3 mb-md-0">
                                            <x-admin.form-select name="status" id="status" class="form-select">
                                                <x-admin.select-option value="" text="{{ __('Select Status') }}" />
                                                <x-admin.select-option :selected="request('status') == '1'" value="1"
                                                    text="{{ __('Yes') }}" />
                                                <x-admin.select-option :selected="request('status') == '0'" value="0"
                                                    text="{{ __('No') }}" />
                                            </x-admin.form-select>
                                        </div>
                                        <div class="col-md-2 form-group mb-3 mb-md-0">
                                            <x-admin.form-select name="order_by" id="order_by" class="form-select">
                                                <x-admin.select-option value="" text="{{ __('Order By') }}" />
                                                <x-admin.select-option :selected="request('order_by') == '1'" value="1"
                                                    text="{{ __('ASC') }}" />
                                                <x-admin.select-option :selected="request('order_by') == '0'" value="0"
                                                    text="{{ __('DESC') }}" />
                                            </x-admin.form-select>
                                        </div>
                                        <div class="col-md-2 form-group mb-3 mb-md-0">
                                            <x-admin.form-select name="par-page" id="par-page" class="form-select">
                                                <x-admin.select-option value="" text="{{ __('Per Page') }}" />
                                                <x-admin.select-option :selected="request('par-page') == '5'" value="5"
                                                    text="{{ __('5') }}" />
                                                <x-admin.select-option :selected="request('par-page') == '10'" value="10"
                                                    text="{{ __('10') }}" />
                                                <x-admin.select-option :selected="request('par-page') == '25'" value="25"
                                                    text="{{ __('25') }}" />
                                                <x-admin.select-option :selected="request('par-page') == '50'" value="50"
                                                    text="{{ __('50') }}" />
                                                <x-admin.select-option :selected="request('par-page') == '100'" value="100"
                                                    text="{{ __('100') }}" />
                                                <x-admin.select-option :selected="request('par-page') == 'all'" value="all"
                                                    text="{{ __('All') }}" />
                                            </x-admin.form-select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <div>
                                    @adminCan('doctor.update')
                                        @if ($doctors->where('email_verified_at', null)->count())
                                            <x-admin.button variant="success" class="me-2" data-bs-toggle="modal"
                                                data-bs-target="#verifyModal" :text="__('Send Verify Link to All')" />
                                        @endif
                                    @endadminCan
                                    <x-admin.add-button :href="route('admin.clients.create', ['type' => 'doctor'])" />
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive max-h-400">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th width="5%">{{ __('SN') }}</th>
                                                <th width="15%">{{ __('Name') }}</th>
                                                <th width="10%">{{ __('Specialty') }}</th>
                                                <th width="10%">{{ __('Services') }}</th>
                                                <th width="15%">{{ __('Subscription Payment') }}</th>
                                                <th width="15%">{{ __('Public listing') }}</th>
                                                <th width="10%">{{ __('Show Homepage') }}</th>
                                                <th width="15%">{{ __('Status') }}</th>
                                                <th width="15%">{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($doctors as $doctor)
                                                <tr>
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td>{{ $doctor->name }}</td>
                                                    <td>{{ $doctor?->department?->name }}</td>
                                                    <td>{{ $doctor->services->pluck('name')->join(', ') }}</td>
                                                    <td>
                                                        @php($subscription = $doctor->latestSubscription)
                                                        @if (! $subscription)
                                                            <span class="badge bg-secondary">{{ __('No payment') }}</span>
                                                        @else
                                                            @php($expired = $subscription->status === 'approved' && $subscription->expires_at?->isPast())
                                                            <a href="{{ route('admin.subscriptions.doctor.show', $subscription->id) }}" class="text-decoration-none">
                                                                <span class="badge bg-{{ $expired ? 'secondary' : ($subscription->status === 'approved' ? 'success' : ($subscription->status === 'rejected' ? 'danger' : 'warning')) }}">
                                                                    {{ $expired ? __('Expired') : __(ucfirst($subscription->status)) }}
                                                                </span>
                                                            </a>
                                                            <small class="d-block text-muted mt-1">
                                                                {{ $subscription->package_name }} · KSh {{ number_format($subscription->package_price) }}
                                                            </small>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @include('admin.partials.listing-status', ['type' => 'doctor', 'client' => $doctor])
                                                        @include('admin.partials.activate-plan', ['type' => 'doctor', 'client' => $doctor])
                                                    </td>
                                                    <td>
                                                        @if ($doctor->show_homepage == 1)
                                                            <span class="badge bg-success">{{ __('Yes') }}</span>
                                                        @else
                                                            <span class="badge bg-danger">{{ __('No') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <input onchange="changeStatus({{ $doctor->id }})"
                                                            id="status_toggle" type="checkbox"
                                                            {{ $doctor->status ? 'checked' : '' }} data-toggle="toggle"
                                                            data-onlabel="{{ __('Active') }}"
                                                            data-offlabel="{{ __('Inactive') }}" data-onstyle="success"
                                                            data-offstyle="danger">
                                                        @unless ($doctor?->email_verified_at)
                                                            <small class="d-block text-muted mt-1">{{ __('Email not confirmed yet (needed to sign in, not to be listed)') }}</small>
                                                        @endunless
                                                    </td>
                                                    <td>
                                                        <x-admin.view-button :href="route('admin.doctor.show', ['doctor' => $doctor->id])" />
                                                        <x-admin.edit-button :href="route('admin.doctor.edit', [
                                                            'doctor' => $doctor->id,
                                                            'code' => getSessionLanguage(),
                                                        ])" />
                                                        <x-admin.delete-button :id="$doctor->id" onclick="deleteData" />
                                                    </td>
                                                </tr>
                                            @empty
                                                <x-empty-table :name="__('Doctor')" route="admin.doctor.create" create="no"
                                                    :message="__('No data found!')" colspan="9" />
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if (request()->get('par-page') !== 'all')
                                    <div class="float-right">
                                        {{ $doctors->onEachSide(0)->links() }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <x-admin.delete-modal />
    @adminCan('doctor.update')
        @if ($doctors->where('email_verified_at', null)->count())
            <!-- Start Verify modal -->
            <div class="modal fade" id="verifyModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Send verify link to doctor mail') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="container-fluid">
                                <p>{{ __('Are you sure want to send verify link to doctor mail?') }}</p>

                                <form action="{{ route('admin.doctor-send-verify-mail-to-all', $doctor->id) }}"
                                    method="POST">
                                    @csrf

                            </div>
                        </div>
                        <div class="modal-footer">
                            <x-admin.button variant="danger" data-bs-dismiss="modal" text="{{ __('Close') }}" />
                            <x-admin.button type="submit" text="{{ __('Send Mail') }}" />
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Verify modal -->
        @endif
    @endadminCan
@endsection

@push('js')
    <script>
        "use strict"

        function deleteData(id) {
            $("#deleteForm").attr("action", '{{ url('/admin/doctor/') }}' + "/" + id)
        }
        "use strict"

        function changeStatus(id) {
            var isDemo = "{{ env('APP_MODE') ?? 'LIVE' }}"
            if (isDemo == 'DEMO') {
                toastr.error("{{ __('This Is Demo Version. You Can Not Change Anything') }}");
                return;
            }
            $.ajax({
                type: "put",
                data: {
                    _token: '{{ csrf_token() }}',
                },
                url: "{{ url('/admin/doctor/status-update') }}" + "/" + id,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.warning(response.message);
                    }
                },
                error: function(err) {
                    console.log(err);
                }
            });
        }
    </script>
@endpush

@push('css')
    <style>
        .dd-custom-css {
            position: absolute;
            will-change: transform;
            top: 0px;
            left: 0px;
            transform: translate3d(0px, -131px, 0px);
        }

        .max-h-400 {
            min-height: 400px;
        }
    </style>
@endpush

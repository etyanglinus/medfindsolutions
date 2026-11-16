@extends('doctor.master_layout')
@section('title')
    <title>{{ __('Create Meeting') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Create Meeting') }}" :list="[
                __('Dashboard') => route('doctor.dashboard'),
                __('Meeting') => route('doctor.zoom-meetings'),
                __('Create Meeting') => '#',
            ]" />
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <div>
                                    <x-admin.back-button :href="route('doctor.zoom-meetings')" />
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('doctor.store-zoom-meeting') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="form-group col-12">
                                            <x-admin.form-input id="topic" name="topic" label="{{ __('Topic') }}"
                                                placeholder="{{ __('Enter Topic') }}" value="{{ old('topic') }}"
                                                required="true" autocomplete="off" />
                                        </div>
                                        <div class="form-group col-md-4">
                                            <x-admin.form-input id="start_time" name="start_time"
                                                label="{{ __('Start Time') }}" placeholder="{{ __('Enter Start Time') }}"
                                                value="{{ old('start_time') }}" required="true" autocomplete="off" />
                                        </div>
                                        <div class="form-group col-md-4">
                                            <x-admin.form-input type="number" id="duration" name="duration" label="{{ __('Duration') }}"
                                                placeholder="{{ __('Enter Duration') }}" value="{{ old('duration') }}"
                                                required="true" autocomplete="off" />
                                        </div>
                                        <div class="form-group col-md-4">
                                            <x-admin.form-select name="patient" id="patient" class="select2"
                                                label="{{ __('Patient') }}" required="true">
                                                <x-admin.select-option value="" text="{{ __('Select Patient') }}" />
                                                <option value="-1">{{ __('All Patient') }}</option>
                                                @foreach ($users as $user)
                                                    <x-admin.select-option :selected="$user?->id == old('patient',request('user'))" value="{{ $user?->id }}"
                                                        text="{{ $user?->name }} ({{ $user?->email }})" />
                                                @endforeach
                                            </x-admin.form-select>
                                        </div>
                                        <div class="col-12">
                                            <x-admin.save-button :text="__('Save')" />
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('js')
    <script>
        $(document).ready(function() {
            $("#start_time").datetimepicker({
                format: 'Y-m-d H:i:s',
                formatTime: 'H:i:s',
                formatDate: 'Y-m-d',
                step: 5,
                minDate: 0,
            });
        });
    </script>
@endpush

@extends('doctor.master_layout')
@section('title')
    <title>{{ __('Edit Meeting') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Edit Meeting') }}" :list="[
                __('Dashboard') => route('doctor.dashboard'),
                __('Meeting') => route('doctor.zoom-meetings'),
                __('Edit Meeting') => '#',
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
                                <form action="{{ route('doctor.update-zoom-meeting', $meeting->meeting_id) }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="form-group col-12">
                                            <x-admin.form-input id="topic" name="topic" label="{{ __('Topic') }}"
                                                placeholder="{{ __('Enter Topic') }}" value="{{ $meeting->topic }}"
                                                required="true" autocomplete="off" />
                                        </div>
                                        <div class="form-group col-md-4">
                                            @php
                                                $date = date('Y-m-d h:i:s', strtotime($meeting->start_time));
                                            @endphp
                                            <x-admin.form-input id="start_time" name="start_time"
                                                label="{{ __('Start Time') }}" placeholder="{{ __('Enter Start Time') }}"
                                                value="{{ $date }}" required="true" autocomplete="off" />
                                        </div>
                                        <div class="form-group col-md-4">
                                            <x-admin.form-input type="number" id="duration" name="duration" label="{{ __('Duration') }}"
                                                placeholder="{{ __('Enter Duration') }}" value="{{ $meeting->duration }}"
                                                required="true" autocomplete="off" />
                                        </div>
                                        <div class="form-group col-md-4">
                                            <x-admin.form-select name="patient" id="patient" class="select2"
                                                label="{{ __('Patient') }}" required="true">
                                                <x-admin.select-option value="" text="{{ __('Select Patient') }}" />
                                                <option :selected="!$meeting_user_id" value="-1">{{ __('All Patient') }}</option>
                                                @foreach ($users as $user)
                                                    <x-admin.select-option :selected="$user?->id == $meeting_user_id" value="{{ $user?->id }}"
                                                        text="{{ $user?->name }} ({{ $user?->email }})" />
                                                @endforeach
                                            </x-admin.form-select>
                                        </div>
                                        <div class="col-12">
                                            <x-admin.update-button :text="__('Update')" />
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

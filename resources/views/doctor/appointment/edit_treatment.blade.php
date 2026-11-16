@extends('doctor.master_layout')
@section('title')
    <title>{{ __('Treatment') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            {{-- Breadcrumb --}}
            <x-admin.breadcrumb title="{{ __('Treatment') }}" :list="[
                __('Dashboard') => route('doctor.dashboard'),
                __('Treatment') => '#',
            ]" />
            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <x-admin.form-title :text="__('Patient Information')" />
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tr>
                                            <td>{{ __('Name') }}</td>
                                            <td>{{ ucwords($appointment?->user?->name) }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Email') }}</td>
                                            <td>{{ $appointment?->user?->email }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Age') }}</td>
                                            <td>{{ $appointment?->user?->details?->age }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Gender') }}</td>
                                            <td>{{ $appointment?->user?->details?->gender }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Old Appointment History') }}</td>
                                            <td><a
                                                    href="{{ route('doctor.old.appointment', $appointment?->user_id) }}">{{ __('History Here') }}</a>
                                            </td>
                                        </tr>

                                        @if ($appointment?->user?->disabilities)
                                            <tr>
                                                <td>{{ __('Disabilities') }}</td>
                                                <td>{{ $appointment?->user?->disabilities }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <x-admin.form-title :text="__('Appointment Information')" />
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tr>
                                            <td>{{ __('Date') }}</td>
                                            <td>{{ formattedDate($appointment?->date) }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Schedule') }}</td>
                                            <td>{{ strtoupper($appointment?->schedule?->start_time) . '-' . strtoupper($appointment?->schedule?->end_time) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Fee') }}</td>
                                            <td>{{ specific_currency_with_icon($appointment?->payable_currency, $appointment->appointment_fee) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Treated') }}</td>
                                            <td>
                                                @if ($appointment?->already_treated == 1)
                                                    <span class="badge bg-success">{{ __('Yes') }}</span>
                                                @else
                                                    <span class="badge bg-danger">{{ __('No') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <form action="{{ route('doctor.treatment.update', $appointment->id) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <x-admin.form-title :text="__('Patient Physical Info')" />
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <x-admin.form-input id="weight" name="weight" label="{{ __('Weight') }}"
                                                placeholder="{{ __('Enter Weight') }}"
                                                value="{{ $appointment?->user?->details?->weight }}" />
                                        </div>

                                        <div class="form-group col-md-3">
                                            <x-admin.form-input id="blood_pressure" name="blood_pressure"
                                                label="{{ __('Blood Pressure') }}"
                                                placeholder="{{ __('Enter Blood Pressure') }}"
                                                value="{{ $appointment?->blood_pressure }}" />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <x-admin.form-input id="temperature" name="temperature"
                                                label="{{ __('Temperature') }}"
                                                placeholder="{{ __('Enter Temperature') }}"
                                                value="{{ $appointment?->temperature }}"/>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <x-admin.form-input id="pulse_rate" name="pulse_rate"
                                                label="{{ __('Pulse Rate') }}" placeholder="{{ __('Enter Pulse Rate') }}"
                                                value="{{ $appointment?->pulse_rate }}" />
                                        </div>

                                        <div class="form-group col-md-12">
                                            <x-admin.form-textarea id="problem_description" name="problem_description"
                                                label="{{ __('Problem Description') }}"
                                                placeholder="{{ __('Enter Problem Description') }}"
                                                value="{{ $appointment?->problem_description }}" maxlength="2000" />
                                        </div>
                                        <div class="form-group col-md-12">
                                            <p class="mb-0">{{ __('Select Habit') }}:</p>
                                            @if ($appointment?->habits)
                                                @php
                                                    $edit_habits = $appointment->habits;
                                                    $edit_habits = json_decode($edit_habits);

                                                @endphp
                                                @foreach ($habits as $index => $habit)
                                                    @php
                                                        $isSelected = false;
                                                    @endphp
                                                    @foreach ($edit_habits as $edit_habit)
                                                        @if ($edit_habit == $habit->id)
                                                            @php
                                                                $isSelected = true;
                                                            @endphp
                                                        @endif
                                                    @endforeach
                                                    <input type="checkbox" name="habit[]" id="habit-{{ $habit->id }}"
                                                        class="@if ($index > 0) ms-3 @endif"
                                                        value="{{ $habit->id }}" {{ $isSelected ? 'checked' : '' }}>
                                                    <label for="habit-{{ $habit->id }}">{{ $habit->name }}</label>
                                                @endforeach
                                            @else
                                                @foreach ($habits as $index => $habit)
                                                    <input type="checkbox" name="habit[]" id="habit-{{ $habit->id }}"
                                                        class="@if ($index > 0) ms-3 @endif"
                                                        value="{{ $habit->id }}"> <label
                                                        for="habit-{{ $habit->id }}">{{ $habit->name }}</label>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <x-admin.form-title :text="__('Prescribe')" />
                                </div>
                                <div class="card-body" id="medicineRow">
                                    @foreach ($appointment?->prescribes as $index => $prescribe)
                                        <div class="row old-prescribe-{{ $prescribe->id }}">
                                            <div class="form-group col-2">
                                                <x-admin.form-select name="medicine_type[]" class="select2"
                                                    label="{{ __('Medicine Type') }} ">
                                                    @foreach ($medicineTypes as $type)
                                                        <x-admin.select-option :selected="$prescribe?->medicine_type == $type->type"
                                                            value="{{ $type->type }}" text="{{ $type->type }}" />
                                                    @endforeach
                                                </x-admin.form-select>
                                            </div>
                                            <div class="form-group col-3">
                                                <x-admin.form-select name="medicine_name[]" class="select2"
                                                    label="{{ __('Medicine') }} ">
                                                    @foreach ($medicines as $medicine)
                                                        <x-admin.select-option :selected="$prescribe?->medicine_name == $medicine?->name"
                                                            value="{{ $medicine?->name }}"
                                                            text="{{ $medicine?->name }}" />
                                                    @endforeach
                                                </x-admin.form-select>
                                            </div>
                                            <div class="form-group col-2">
                                                <x-admin.form-select name="dosage[]" class="select2"
                                                    label="{{ __('Dosage') }} ">
                                                    @foreach ($dosages as $dosage)
                                                        <x-admin.select-option :selected="$prescribe?->dosage == $dosage"
                                                            value="{{ $dosage }}" text="{{ $dosage }}" />
                                                    @endforeach
                                                </x-admin.form-select>
                                            </div>
                                            <div class="form-group col-2">
                                                <x-admin.form-select name="day[]" class="select2"
                                                    label="{{ __('Days') }} ">
                                                    @for ($i = 1; $i <= 90; $i++)
                                                        <x-admin.select-option :selected="$prescribe?->number_of_day == $i"
                                                            value="{{ $i }}" text="{{ $i }}" />
                                                    @endfor
                                                </x-admin.form-select>
                                            </div>
                                            <div class="form-group col-3">
                                                <label>{{ __('Time') }}</label>
                                                <div class="input-group">
                                                    <select name="time[]"class="form-select wsus-select">
                                                        <option value="After Meal" @selected($prescribe?->time == 'After Meal')>{{ __('After Meal') }}</option>
                                                        <option value="Before Meal" @selected($prescribe?->time == 'Before Meal')>{{ __('Before Meal') }}</option>
                                                    </select>
                                                    <div class="input-group-append">
                                                        @if ($index == 0)
                                                        <div class="input-group-text wsus-toggle-medicine-row bg-primary text-white" id="addMedicineRow">
                                                            <i class="fas fa-plus"></i>
                                                        </div>
                                                        @endif
                                                        @if ($index != 0)
                                                        <div class="input-group-text wsus-toggle-medicine-row bg-danger text-white" onclick="oldPrescribeDelete({{ $prescribe->id }})">
                                                            <i class="fas fa-trash"></i>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <x-admin.form-title :text="__('Advice')" />
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <x-admin.form-textarea id="test_description" name="test_description"
                                                label="{{ __('Test Description') }}"
                                                placeholder="{{ __('Enter Test Description') }}"
                                                value="{{ $appointment?->advice?->test_description }}"
                                                maxlength="2000" />
                                        </div>
                                        <div class="form-group col-md-12">
                                            <x-admin.form-textarea id="advice" name="advice"
                                                label="{{ __('Advice') }}" placeholder="{{ __('Enter Advice') }}"
                                                value="{{ $appointment?->advice?->advice }}" maxlength="2000" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <x-admin.update-button :text="__('Update')" />
                        </div>
                    </div>
                </form>
                {{-- add prescribe row --}}
                @include('doctor.appointment.prescribe_add_row')
            </div>
        </section>
    </div>
@endsection
@push('js')
<script src="{{ asset('doctor/js/dev.js') }}"></script>
    <script>
        "use strict";

        function oldPrescribeDelete(id) {
            var isDemo = "{{ env('APP_MODE') ?? 'LIVE' }}"
            if (isDemo == 'DEMO') {
                toastr.error("{{ __('This Is Demo Version. You Can Not Change Anything') }}");
                return;
            }
            var old_prescirbe_row = "old-prescribe-" + id;
            $.ajax({
                type: 'GET',
                url: "{{ url('doctor/delete-appointment-prescribe/') }}" + "/" + id,
                success: function(response) {
                    if (response.success) {
                        $("." + old_prescirbe_row).remove()
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

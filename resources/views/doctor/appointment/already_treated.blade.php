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
                <div class="row">
                    <div class="prescribe">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="prescription">
                                    <div class="top">
                                        <div class="table-responsive">
                                            <table class="table table-no-border">
                                                <tbody>
                                                    <tr>
                                                        <td class="w-50"><div class="prescription-logo"><img width="200" height="auto" src="{{ asset($setting?->logo) }}"
                                                            alt="{{ $setting?->app_name }}"></div>
                                                            <div class="address">
                                                                {{ $contactInfo?->address }}
                                                            </div>
                                                            <div class="phone">
                                                                {{ $setting?->prescription_phone }}
                                                            </div>
                                                            <div class="email">
                                                                {{ $setting?->prescription_email }}
                                                            </div></td>
                                                        <td class="w-50"><div class="right">
                                                            <h2 class="text-end">{{ $appointment?->doctor?->name }}</h2>
                                                            <p class="text-end">
                                                                {{ $appointment?->doctor?->designations }}
                                                            </p>
                                                </div></td>
                                                    </tr>
                                                </tbody>
        
                                            </table>
                                        </div>
                                        
                                    </div>

                                    <div class="patient-info">
                                        <div class="row">
                                            <div class="table-responsive">
                                                <table class="table table-no-border">
                                                    <tbody>
                                                        <tr>
                                                            <td>{{ __('Patient Name') }}: {{ $appointment?->user?->name }}</td>
                                                            <td>{{ __('Age') }}: {{ $appointment?->user?->details?->age }} {{ __('Years') }}</td>
                                                            <td>{{ __('Date') }}: {{ formattedDate($appointment?->date) }}</td>
                                                        </tr>
                                                    </tbody>

                                                </table>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="main-section">
                                        <div class="row">
                                            <div class="table-responsive">
                                                <table class="table table-no-border">
                                                    <tbody>
                                                        <tr>
                                                            <td class="w-25">
                                                                <div class="problem">
                                                                    <h2>{{ __('Problems') }}
                                                                    </h2>
                                                                    <p>
                                                                        {!! nl2br(e($appointment?->problem_description)) !!}
                                                                    </p>
                                                                </div>
                                                                @if ($appointment->advice)
                                                                    @if ($appointment?->advice?->test_description != null)
                                                                        <div class="test">
                                                                            <h2>{{ __('Test Problem') }}
                                                                            </h2>
                                                                            <p>
                                                                                {!! nl2br(e($appointment?->advice?->test_description)) !!}
                                                                            </p>
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            </td>
                                                            <td class="w-75">
                                                                <div class="medicine">
                                                                    <div class="rx">R<span>x</span></div>
                                                                    <div class="list">
                                                                        <ol>
                                                                            @foreach ($appointment?->prescribes as $prescribe)
                                                                                <li>
                                                                                    <div class="full">
                                                                                        <div class="l">
                                                                                            {{ $prescribe?->medicine_name }}
                                                                                            <br>
                                                                                            {{ $prescribe?->dosage }}
                                                                                            ({{ $prescribe->time }})
                                                                                        </div>
                                                                                        <div class="r">
                                                                                            {{ $prescribe?->number_of_day }}
                                                                                            {{ __('Days') }}
                                                                                        </div>
                                                                                    </div>
                                                                                </li>
                                                                            @endforeach

                                                                        </ol>
                                                                    </div>
                                                                    @if ($appointment?->advice)
                                                                        @if ($appointment?->advice?->advice != null)
                                                                            <div class="advice">
                                                                                <h2>{{ __('Advice') }}
                                                                                </h2>
                                                                                <p>
                                                                                    {!! nl2br(e($appointment?->advice?->advice)) !!}
                                                                                </p>
                                                                            </div>
                                                                        @endif
                                                                    @endif

                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>

                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="footer mt-5">
                                        <h2>{{ __('Signature') }}</h2>
                                        <p>
                                            {{ $appointment?->doctor?->name }}<br>
                                            {{ $appointment?->doctor?->designations }}
                                        </p>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col">
                        <a target="_blank" class="btn btn-success me-3 print-btn" href="{{route('doctor.print.prescription',$appointment->id)}}"><i class="fas fa-print"
                            aria-hidden="true"></i> {{ __('Print') }}</a>
                        @if (doctorAuth()->id == $appointment?->doctor_id)
                            <a href="{{ route('doctor.treatment.edit', $appointment->id) }}"
                                class="btn btn-primary edit-btn">{{ __('Edit') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('css')
    <link rel="stylesheet" href="{{ asset('patient/css/prescription.css') }}">
    <style>
        table{
            margin-bottom: 0!important;
        }
        td,th{
            padding: 0!important;
            height: auto!important;
        }
    </style>
@endpush

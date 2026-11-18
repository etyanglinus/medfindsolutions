@extends('doctor.master_layout')
@section('title')
    <title>{{ __('Manage Portfolio') }}</title>
@endsection
@section('admin-content')

    <div class="main-content">
@include('provider.onboarding-notice')
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Manage Portfolio') }}" :list="[
                __('Dashboard') => route('doctor.dashboard'),
                __('Manage Portfolio') => '#',
            ]" />

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card profile-widget">
                            <div class="profile-widget-description">
                                <form action="{{ route('doctor.profile-update') }}"
                                    enctype="multipart/form-data" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div
                                            class="form-group col-md-12">
                                            <x-admin.form-image-preview recommended="300X270" name="doctor_image" :image="!empty($doctor->image)
                                                ? $doctor->image
                                                : $setting->default_avatar"
                                                label="{{ __('Existing Image') }}"
                                                button_label="{{ __('Update Image') }}" />
                                        </div>
                                        <div
                                            class="form-group col-md-4">
                                            <x-admin.form-input id="name" name="name"
                                                label="{{ __('Name') }}" placeholder="{{ __('Enter Name') }}"
                                                value="{{ $doctor->name }}" required="true" />
                                        </div>
                                        <div
                                            class="form-group col-md-4">
                                            <x-admin.form-input id="phone" name="phone" label="{{ __('Phone') }}"
                                                placeholder="{{ __('Enter Phone') }}" value="{{ $doctor->phone }}"
                                                required="true" />
                                        </div>
                                        <div
                                            class="form-group col-md-4">
                                            <x-admin.form-input type="email" id="email" name="email"
                                                label="{{ __('Email') }}" placeholder="{{ __('Enter Email') }}"
                                                value="{{ $doctor->email }}" required="true" />
                                        </div>
                                        @if($subscription?->hasEntitlement('services') && $services->isNotEmpty())
                                            <div class="form-group col-12">
                                                <label for="service_ids">{{ __('Services') }} <span class="text-danger">*</span></label>
                                                <select id="service_ids" name="service_ids[]" class="form-control select2" multiple required>
                                                    @foreach($services as $service)
                                                        <option value="{{ $service->id }}" @selected(in_array($service->id, old('service_ids', $doctor->services->pluck('id')->all())))>{{ $service->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('service_ids')<div class="text-danger">{{ $message }}</div>@enderror
                                            </div>
                                        @endif
                                        @if($subscription?->hasEntitlement('insurance'))
                                            <div class="form-group col-12">
                                                <x-admin.form-input id="accepted_insurance" name="accepted_insurance"
                                                    label="{{ __('Accepted insurance') }}" required="true"
                                                    value="{{ old('accepted_insurance', $doctor->getTranslation($code)?->accepted_insurance) }}" />
                                            </div>
                                        @endif
                                        @if($subscription?->hasEntitlement('whatsapp'))
                                            <div class="form-group col-md-4">
                                                <x-admin.form-input id="whatsapp" name="whatsapp" label="{{ __('WhatsApp') }}"
                                                    placeholder="{{ __('Enter WhatsApp number') }}" value="{{ old('whatsapp', $doctor->whatsapp) }}" required="true" />
                                            </div>
                                        @endif
                                        @if($subscription?->hasEntitlement('consultation_fee'))
                                            <div class="form-group col-md-4">
                                                <x-admin.form-input type="number" id="fee" name="fee" label="{{ __('Consultation fee (KES)') }}"
                                                    min="0.01" step="0.01" value="{{ old('fee', $doctor->fee) }}" required="true" />
                                            </div>
                                        @endif
                                        @foreach(['telemedicine' => ['telemedicine_available', __('Telemedicine availability')], 'emergency' => ['emergency_available', __('Emergency availability')], 'reviews' => ['reviews_enabled', __('Allow patient reviews')]] as $capability => [$field, $label])
                                            @if($subscription?->hasEntitlement($capability))
                                                <div class="form-group col-md-4">
                                                    <input type="hidden" name="{{ $field }}" value="0">
                                                    <label for="{{ $field }}">
                                                        <input type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $doctor->{$field} ?? ($field === 'reviews_enabled')))>
                                                        {{ $label }}
                                                    </label>
                                                    @error($field)<div class="text-danger">{{ $message }}</div>@enderror
                                                </div>
                                            @endif
                                        @endforeach
                                        <div
                                            class="form-group col-md-4">
                                            <x-admin.form-input type="number" id="years_of_experience" name="years_of_experience"
                                                min="0" step="1"
                                                label="{{ __('Years of experience') }}"
                                                placeholder="{{ __('Enter Years of experience') }}"
                                                value="{{ old('years_of_experience', $doctor->years_of_experience) }}" required="true" />
                                        </div>
                                        <div
                                            class="form-group col-lg-4 col-md-6">
                                            <x-admin.form-input id="licensing_body" name="licensing_body"
                                                label="{{ __('Licensing Body') }}"
                                                placeholder="{{ __('Enter Licensing Body') }}"
                                                value="{{ $doctor->getTranslation($code)?->licensing_body ?? '' }}"
                                                required="true" />
                                        </div>
                                        <div
                                            class="form-group col-lg-4  col-md-6">
                                            <x-admin.form-select name="department_id" id="department_id" class="select2"
                                                label="{{ __('Specialty') }}" required="true">
                                                <x-admin.select-option value=""
                                                    text="{{ __('Select Specialty') }}" />
                                                @foreach ($departments as $department)
                                                    <x-admin.select-option :selected="$department->id == $doctor->department_id" value="{{ $department->id }}"
                                                        text="{{ $department->name }}" />
                                                @endforeach
                                            </x-admin.form-select>
                                        </div>
                                        <div
                                            class="form-group col-lg-4  col-md-6">
                                            <x-admin.form-select name="location_id" id="location_id" class="select2"
                                                label="{{ __('Location') }}" required="true">
                                                <x-admin.select-option value="" text="{{ __('Select Location') }}" />
                                                @foreach ($locations as $location)
                                                    <x-admin.select-option :selected="$location->id == $doctor->location_id" value="{{ $location->id }}"
                                                        text="{{ $location->name }}" />
                                                @endforeach
                                            </x-admin.form-select>
                                        </div>
                                        <div class="form-group col-md-12">
                                            <x-admin.form-editor id="about" name="about"
                                                label="{{ __('About') }}" value="{!! $doctor->getTranslation($code)?->about ?? '' !!}"
                                                required="true" maxlength="2000" />
                                        </div>

                                        <div class="form-group col-md-12">
                                            <x-admin.form-editor id="address" name="address"
                                                label="{{ __('Address') }}" value="{!! $doctor->getTranslation($code)?->address ?? '' !!}"
                                                required="true" />
                                        </div>
                                        <div class="form-group col-md-12">
                                            <x-admin.form-editor id="educations" name="educations"
                                                label="{{ __('Educations') }}" value="{!! $doctor->getTranslation($code)?->educations ?? '' !!}"
                                                required="true" />
                                        </div>
                                        <div class="form-group col-md-12">
                                            <x-admin.form-editor id="experience" name="experience"
                                                label="{{ __('Experience') }}" value="{!! $doctor->getTranslation($code)?->experience ?? '' !!}"
                                                required="true" />
                                        </div>
                                        <div class="form-group col-md-12">
                                            <x-admin.form-editor id="qualifications" name="qualifications"
                                                label="{{ __('Qualifications') }}"
                                                value="{!! $doctor->getTranslation($code)?->qualifications ?? '' !!}" required="true" />
                                        </div>

                                        <div class="form-group col-md-12">
                                            <x-admin.form-input id="seo_title" name="seo_title"
                                                label="{{ __('SEO Title') }}" placeholder="{{ __('Enter SEO Title') }}"
                                               
                                                value="{{ $doctor->getTranslation($code)?->seo_title ?? '' }}" />
                                        </div>

                                        <div class="form-group col-md-12">
                                            <x-admin.form-textarea id="seo_description" name="seo_description"
                                                label="{{ __('SEO Description') }}"
                                                placeholder="{{ __('Enter SEO Description') }}"
                                                value="{{ $doctor->getTranslation($code)?->seo_description ?? '' }}"
                                                maxlength="2000" />
                                        </div>

                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <x-admin.update-button :text="__('Update')" />
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            {{-- edit profile area  --}}

        </section>
    </div>
@endsection
@push('js')
        <script src="{{ asset('backend/js/jquery.uploadPreview.min.js') }}"></script>
        <script>
            $.uploadPreview({
                input_field: "#image-upload",
                preview_box: "#image-preview",
                label_field: "#image-label",
                label_default: "{{ __('Choose Image') }}",
                label_selected: "{{ __('Change Image') }}",
                no_label: false,
                success_callback: null
            });
        </script>
@endpush

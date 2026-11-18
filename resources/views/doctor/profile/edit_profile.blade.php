@extends('doctor.master_layout')
@section('title')
    <title>{{ __('Manage Portfolio') }}</title>
@endsection
@section('admin-content')

    @php
        $allLanguages = allLanguages();
        $defaultLanguage = $allLanguages->first();
        $isDefaultLanguage = $defaultLanguage && $code === $defaultLanguage->code;
    @endphp
    <div class="main-content">
@include('provider.onboarding-notice')
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Manage Portfolio') }}" :list="[
                __('Dashboard') => route('doctor.dashboard'),
                __('Manage Portfolio') => '#',
            ]" />

            <div class="section-body row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header gap-3 justify-content-between align-items-center">
                            <h5 class="m-0 service_card">{{ __('Available Translations') }}</h5>
                            @if (! $isDefaultLanguage)
                                <x-admin.button onclick="translateAll()" id="translate-btn" text="{{ __('Translate') }}" />
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="lang_list_top">
                                <ul class="lang_list">
                                    @foreach ($allLanguages as $language)
                                        <li><a id="{{ request('code') == $language->code ? 'selected-language' : '' }}"
                                                href="{{ route('doctor.edit-profile', ['doctor' => $doctor->id, 'code' => $language->code]) }}"><i
                                                    class="fas {{ request('code') == $language->code ? 'fa-eye' : 'fa-edit' }}"></i>
                                                {{ $language->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="mt-2 alert alert-danger" role="alert">
                                @php
                                    $current_language = $allLanguages->where('code', request()->get('code'))->first();
                                @endphp
                                <p>{{ __('Your editing mode') }}:<b> {{ $current_language?->name }}</b></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- edit profile area  --}}
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card profile-widget">
                            <div class="profile-widget-description">
                                <form action="{{ route('doctor.profile-update', ['code' => $code]) }}"
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
                                            class="form-group col-md-4 {{ $isDefaultLanguage ? '' : 'd-none' }}">
                                            <x-admin.form-input id="name" name="name" data-translate="true"
                                                label="{{ __('Name') }}" placeholder="{{ __('Enter Name') }}"
                                                value="{{ $doctor->name }}" required="true" />
                                        </div>
                                        <div
                                            class="form-group col-md-4 {{ $isDefaultLanguage ? '' : 'd-none' }}">
                                            <x-admin.form-input id="phone" name="phone" label="{{ __('Phone') }}"
                                                placeholder="{{ __('Enter Phone') }}" value="{{ $doctor->phone }}"
                                                required="true" />
                                        </div>
                                        <div
                                            class="form-group col-md-4 {{ $isDefaultLanguage ? '' : 'd-none' }}">
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
                                            class="form-group col-md-4 {{ $isDefaultLanguage ? '' : 'd-none' }}">
                                            <x-admin.form-input id="years_of_experience" name="years_of_experience"
                                                label="{{ __('Years of experience') }}"
                                                placeholder="{{ __('Enter Years of experience') }}"
                                                value="{{ $doctor->years_of_experience }}" required="true" />
                                        </div>
                                        <div
                                            class="form-group @if ($isDefaultLanguage) col-lg-4 col-md-6 @else col-12 @endif">
                                            <x-admin.form-input id="licensing_body" name="licensing_body" data-translate="true"
                                                label="{{ __('Licensing Body') }}"
                                                placeholder="{{ __('Enter Licensing Body') }}"
                                                value="{{ $doctor->getTranslation($code)?->licensing_body ?? '' }}"
                                                required="true" />
                                        </div>
                                        <div
                                            class="form-group col-lg-4  col-md-6 {{ $isDefaultLanguage ? '' : 'd-none' }}">
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
                                            class="form-group col-lg-4  col-md-6 {{ $isDefaultLanguage ? '' : 'd-none' }}">
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
                                            <x-admin.form-editor id="address" name="address" data-translate="true"
                                                label="{{ __('Address') }}" value="{!! $doctor->getTranslation($code)?->address ?? '' !!}"
                                                required="true" />
                                        </div>
                                        <div class="form-group col-md-12">
                                            <x-admin.form-editor id="educations" name="educations" data-translate="true"
                                                label="{{ __('Educations') }}" value="{!! $doctor->getTranslation($code)?->educations ?? '' !!}"
                                                required="true" />
                                        </div>
                                        <div class="form-group col-md-12">
                                            <x-admin.form-editor id="experience" name="experience" data-translate="true"
                                                label="{{ __('Experience') }}" value="{!! $doctor->getTranslation($code)?->experience ?? '' !!}"
                                                required="true" />
                                        </div>
                                        <div class="form-group col-md-12">
                                            <x-admin.form-editor id="qualifications" name="qualifications"
                                                data-translate="true" label="{{ __('Qualifications') }}"
                                                value="{!! $doctor->getTranslation($code)?->qualifications ?? '' !!}" required="true" />
                                        </div>

                                        <div class="form-group col-md-12">
                                            <x-admin.form-input id="seo_title" name="seo_title"
                                                label="{{ __('SEO Title') }}" placeholder="{{ __('Enter SEO Title') }}"
                                                data-translate="true"
                                                value="{{ $doctor->getTranslation($code)?->seo_title ?? '' }}" />
                                        </div>

                                        <div class="form-group col-md-12">
                                            <x-admin.form-textarea id="seo_description" name="seo_description"
                                                label="{{ __('SEO Description') }}"
                                                placeholder="{{ __('Enter SEO Description') }}" data-translate="true"
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
    @if ($isDefaultLanguage)
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
    @else
        <script>
            var isTranslatingInputs = true;

            function translateOneByOne(inputs, index = 0) {
                if (index >= inputs.length) {
                    if (isTranslatingInputs) {
                        isTranslatingInputs = false;
                        translateAllTextarea();
                    }
                    $('#translate-btn').prop('disabled', false);
                    $('#update-btn').prop('disabled', false);
                    return;
                }

                var $input = $(inputs[index]);
                var inputValue = $input.val();

                if (inputValue) {
                    $.ajax({
                        url: "{{ route('admin.languages.update.single') }}",
                        type: "POST",
                        data: {
                            lang: '{{ $code }}',
                            text: inputValue,
                            _token: '{{ csrf_token() }}'
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            $input.prop('disabled', true);
                            iziToast.show({
                                timeout: false,
                                close: true,
                                theme: 'dark',
                                icon: 'loader',
                                iconUrl: 'https://hub.izmirnic.com/Files/Images/loading.gif',
                                title: "{{ __('Translation Processing, please wait...') }}",
                                position: 'center',
                            });
                        },
                        success: function(response) {
                            $input.val(response);
                            $input.prop('disabled', false);
                            iziToast.destroy();
                            toastr.success("{{ __('Translated Successfully!') }}");
                            translateOneByOne(inputs, index + 1);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error(textStatus, errorThrown);
                            iziToast.destroy();
                            toastr.error('Error', 'Error');
                        }
                    });
                } else {
                    translateOneByOne(inputs, index + 1);
                }
            }

            function translateAll() {
                iziToast.question({
                    timeout: 20000,
                    close: false,
                    overlay: true,
                    displayMode: 'once',
                    id: 'question',
                    zindex: 999,
                    title: "{{ __('This will take a while!') }}",
                    message: "{{ __('Are you sure?') }}",
                    position: 'center',
                    buttons: [
                        ["<button><b>{{ __('YES') }}</b></button>", function(instance, toast) {
                            var isDemo = "{{ env('APP_MODE') ?? 'LIVE' }}";

                            if (isDemo == 'DEMO') {
                                instance.hide({
                                    transitionOut: 'fadeOut'
                                }, toast, 'button');
                                toastr.error("{{ __('This Is Demo Version. You Can Not Change Anything') }}");
                                return;
                            }

                            $('#translate-btn').prop('disabled', true);
                            $('#update-btn').prop('disabled', true);

                            instance.hide({
                                transitionOut: 'fadeOut'
                            }, toast, 'button');

                            var inputs = $('input[data-translate="true"]').toArray();
                            translateOneByOne(inputs);

                        }, true],
                        ["<button>{{ __('NO') }}</button>", function(instance, toast) {

                            instance.hide({
                                transitionOut: 'fadeOut'
                            }, toast, 'button');

                        }],
                    ],
                    onClosing: function(instance, toast, closedBy) {},
                    onClosed: function(instance, toast, closedBy) {}
                });
            };

            function translateAllTextarea() {
                var inputs = $('textarea[data-translate="true"]').toArray();
                if (inputs.length === 0) {
                    return;
                }
                translateOneByOne(inputs);
            }

            $(document).ready(function() {
                var selectedTranslation = $('#selected-language').text();
                var btnText = "{{ __('Translate to') }} " + selectedTranslation;
                $('#translate-btn').text(btnText);
            });
        </script>
    @endif
@endpush

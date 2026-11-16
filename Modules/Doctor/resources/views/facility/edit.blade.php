@extends('admin.master_layout')
@section('title')
    <title>{{ __('Edit Facility') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Edit Facility') }}" :list="[
                __('Dashboard') => route('admin.dashboard'),
                __('Facility List') => route('admin.facility.index'),
                __('Edit Facility') => '#',
            ]" />
            <div class="section-body row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header gap-3 justify-content-between align-items-center">
                            <h5 class="m-0 service_card">{{ __('Available Translations') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="lang_list_top">
                                <ul class="lang_list">
                                    @foreach ($languages = allLanguages() as $language)
                                        <li><a id="{{ request('code') == $language->code ? 'selected-language' : '' }}"
                                                href="{{ route('admin.facility.edit', ['facility' => $facility->id, 'code' => $language->code]) }}"><i
                                                    class="fas {{ request('code') == $language->code ? 'fa-eye' : 'fa-edit' }}"></i>
                                                {{ $language->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="mt-2 alert alert-danger" role="alert">
                                @php
                                    $current_language = $languages->where('code', request()->get('code'))->first();
                                @endphp
                                <p>{{ __('Your editing mode') }}:<b> {{ $current_language?->name }}</b></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <div>
                                    <x-admin.back-button :href="route('admin.facility.index')" />
                                </div>
                            </div>
                            <div class="card-body">
                                <form
                                    action="{{ route('admin.facility.update', ['facility' => $facility->id, 'code' => $code]) }}"
                                    method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')

                                    <div class="row">
                                        <div class="form-group col-lg-4  col-md-6 {{ $code == $languages->first()->code ? '' : 'd-none' }}">
                                            <x-admin.form-input id="name" name="name" data-translate="true"
                                                label="{{ __('Name') }}" placeholder="{{ __('Enter Name') }}"
                                                value="{{ $facility->name }}" required="true" />
                                        </div>
                                        <div class="form-group col-lg-4  col-md-6 {{ $code == $languages->first()->code ? '' : 'd-none' }}">
                                            <x-admin.form-input id="phone" name="phone" label="{{ __('Phone') }}"
                                                placeholder="{{ __('Enter Phone') }}" value="{{ $facility->phone }}"
                                                required="true" />
                                        </div>
                                        <div class="form-group col-lg-4  col-md-6 {{ $code == $languages->first()->code ? '' : 'd-none' }}">
                                            <x-admin.form-input type="email" id="email" name="email"
                                                label="{{ __('Email') }}" placeholder="{{ __('Enter Email') }}"
                                                value="{{ $facility->email }}" required="true" />
                                        </div>
                                        <div class="form-group col-lg-4  col-md-6 {{ $code == $languages->first()->code ? '' : 'd-none' }}">
                                            <x-admin.form-input id="years_of_experience" name="years_of_experience" label="{{ __('Years of experience') }}"
                                                placeholder="{{ __('Enter Years of experience') }}" value="{{ $facility->years_of_experience }}"
                                                required="true" />
                                        </div>
                                        <div class="form-group col-lg-4  col-md-6 {{ $code == $languages->first()->code ? '' : 'd-none' }}">
                                            <x-admin.form-input id="fee" name="fee" label="{{ __('Fee') }}"
                                                placeholder="{{ __('Enter Fee') }}" value="{{ $facility->fee }}"
                                                required="true" />
                                        </div>
                                        <div class="form-group @if ($code == $languages->first()->code) col-lg-4 col-md-6 @else col-12 @endif">
                                            @if ($code == $languages->first()->code)
                                                <x-admin.form-select name="licensing_body_id" id="licensing_body_id" class="select2"
                                                    label="{{ __('Licensing Body') }}" required="true">
                                                    <x-admin.select-option value="" text="{{ __('Select Licensing Body') }}" />
                                                    @foreach($licensingBodies as $body)
                                                        <x-admin.select-option :selected="$body->name == $facility->getTranslation($code)->licensing_body" value="{{ $body->id }}" text="{{ $body->name }}" />
                                                    @endforeach
                                                </x-admin.form-select>

                                                <label for="facility_insurance_provider_ids" class="form-label fw-semibold small text-secondary">{{ __('Insurance Providers') }}</label>
                                                <select name="insurance_provider_ids[]" id="facility_insurance_provider_ids" class="form-select select2" multiple>
                                                    @php $selectedInsurance = $facility->getTranslation($code)->accepted_insurance ? explode(', ', $facility->getTranslation($code)->accepted_insurance) : []; @endphp
                                                    @foreach($insuranceProviders as $provider)
                                                        <option value="{{ $provider->id }}" @selected(in_array($provider->name, $selectedInsurance))>{{ $provider->name }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <x-admin.form-input id="licensing_body" name="licensing_body" data-translate="true"
                                                    label="{{ __('Licensing Body') }}"
                                                    placeholder="{{ __('Enter Licensing Body') }}"
                                                    value="{{ $facility->getTranslation($code)->licensing_body }}"
                                                    required="true" />
                                            @endif
                                        </div>
                                        <div class="form-group @if ($code == $languages->first()->code) col-lg-4 col-md-6 d-none @else col-12 @endif">
                                            <x-admin.form-textarea id="accepted_insurance" name="accepted_insurance" data-translate="true"
                                                label="{{ __('Accepted Insurance') }}"
                                                placeholder="{{ __('Enter Accepted Insurance') }}"
                                                value="{{ $facility->getTranslation($code)->accepted_insurance }}"
                                                maxlength="2000" />
                                        </div>
                                        <div class="form-group col-lg-4  col-md-6 {{ $code == $languages->first()->code ? '' : 'd-none' }}">
                                            <x-admin.form-select name="department_id" id="department_id" class="select2"
                                                label="{{ __('Specialty') }}" required="true">
                                                <x-admin.select-option value=""
                                                    text="{{ __('Select Specialty') }}" />
                                                @foreach ($departments as $department)
                                                    <x-admin.select-option :selected="$department->id == $facility->department_id" value="{{ $department->id }}"
                                                        text="{{ $department->name }}" />
                                                @endforeach
                                            </x-admin.form-select>
                                        </div>
                                        <div class="form-group col-lg-4  col-md-6 {{ $code == $languages->first()->code ? '' : 'd-none' }}">
                                            <x-admin.form-select name="service_ids[]" id="service_ids" class="select2" label="{{ __('Services') }}" multiple>
                                                @foreach ($services as $service)
                                                    <x-admin.select-option :selected="in_array($service->id, $facility->services->pluck('id')->toArray())" value="{{ $service->id }}" text="{{ $service->name }}" />
                                                @endforeach
                                            </x-admin.form-select>
                                        </div>
                                        <div class="form-group col-md-12">
                                            <x-admin.form-textarea id="about" name="about" data-translate="true"
                                                label="{{ __('About') }}"
                                                placeholder="{{ __('Enter About Information') }}"
                                                value="{{ $facility->getTranslation($code)->about }}" maxlength="2000"
                                                required="true" />
                                        </div>

                                        <div class="form-group col-md-12">
                                            <x-admin.form-editor id="address" name="address" data-translate="true"
                                                label="{{ __('Address') }}" value="{!! $facility->getTranslation($code)->address !!}"
                                                required="true" />
                                        </div>
                                        <div class="form-group col-md-12">
                                            <x-admin.form-editor id="experience" name="experience" data-translate="true"
                                                label="{{ __('Experience') }}" value="{!! $facility->getTranslation($code)->experience !!}"
                                                required="true" />
                                        </div>

                                        <div class="form-group col-md-12">
                                            <x-admin.form-input id="seo_title" name="seo_title"
                                                label="{{ __('SEO Title') }}" placeholder="{{ __('Enter SEO Title') }}"
                                                data-translate="true"
                                                value="{{ $facility->getTranslation($code)->seo_title }}" />
                                        </div>

                                        <div class="form-group col-md-12">
                                            <x-admin.form-textarea id="seo_description" name="seo_description"
                                                label="{{ __('SEO Description') }}"
                                                placeholder="{{ __('Enter SEO Description') }}" data-translate="true"
                                                value="{{ $facility->getTranslation($code)->seo_description }}"
                                                maxlength="2000" />
                                        </div>
                                        <div class="form-group col-md-12 {{ $code == $languages->first()->code ? '' : 'd-none' }}">
                                            <x-admin.form-image-preview recommended="300X270" name="facility_image" :image="$facility->image" label="{{__('Image')}}" />
                                        </div>

                                    </div>
                                    <div class="row">
                                        @if ($code == $languages->first()->code)
                                            <div class="form-group col-md-12">
                                                <x-admin.form-switch name="show_homepage"
                                                    label="{{ __('Show on homepage') }}" :checked="$facility->show_homepage == 1" />
                                            </div>

                                            <div class="form-group col-md-12">
                                                <x-admin.form-switch name="status" label="{{ __('Status') }}"
                                                    :checked="$facility->status == 1" />
                                            </div>
                                        @endif
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
        </section>
    </div>
@endsection

@push('js')
    @if ($code == $languages->first()->code)
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
    @endif
    <script>
        (function($) {
            "use strict";
            $(document).ready(function() {
                $("#name").on("keyup", function(e) {
                    $("#slug").val(convertToSlug($(this).val()));
                })
            });
        })(jQuery);

        function convertToSlug(Text) {
            return Text
                .toLowerCase()
                .replace(/[^\w ]+/g, '')
                .replace(/ +/g, '-');
        }
    </script>
@endpush

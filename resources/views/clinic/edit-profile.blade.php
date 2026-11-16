@extends('clinic.master_layout')
@section('title')
    <title>{{ __('Manage Portfolio') }}</title>
@endsection

@section('clinic-content')

<div class="main-content">
@include('provider.onboarding-notice')
    <section class="section">
        <x-admin.breadcrumb title="{{ __('Manage Portfolio') }}" :list="[
            __('Dashboard') => route('clinic.dashboard'),
            __('Manage Portfolio') => '#',
        ]" />

        <div class="section-body">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card">
                        <div class="card-header"><h4>{{ __('Manage Portfolio') }}</h4></div>
                        <div class="card-body">
                            <form action="{{ route('clinic.profile.update') }}" method="POST" enctype="multipart/form-data">
                                @csrf @method('PUT')
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('Clinic Name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('name', $clinic->name) }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('Specialty / Type') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="specialty" class="form-control @error('specialty') is-invalid @enderror"
                                            value="{{ old('specialty', $clinic->specialty) }}"
                                            placeholder="{{ __('e.g. Dialysis, Dental, Eye Care') }}" required>
                                        @error('specialty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('Years of experience') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="years_of_experience" class="form-control @error('years_of_experience') is-invalid @enderror"
                                            value="{{ old('years_of_experience', $clinic->years_of_experience) }}" placeholder="{{ __('Enter Years of experience') }}" required>
                                        @error('years_of_experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('Fee') }} <span class="text-danger">*</span></label>
                                        <input type="number" min="0" step="0.01" name="consultation_fee" class="form-control @error('consultation_fee') is-invalid @enderror"
                                            value="{{ old('consultation_fee', $clinic->consultation_fee) }}" placeholder="{{ __('Enter Fee') }}" required>
                                        @error('consultation_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('Licensing Body') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="licensing_body" class="form-control @error('licensing_body') is-invalid @enderror"
                                            value="{{ old('licensing_body', $clinic->licensing_body) }}" placeholder="{{ __('Enter Licensing Body') }}" required>
                                        @error('licensing_body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                            value="{{ old('phone', $clinic->phone) }}" required>
                                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    @if($subscription?->hasEntitlement('whatsapp'))
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('WhatsApp Number') }}</label>
                                        <input type="text" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror"
                                            value="{{ old('whatsapp', $clinic->whatsapp) }}"
                                            placeholder="+254 700 000 000">
                                        @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    @endif
                                    @if($subscription?->hasEntitlement('services_limit'))<div class="col-12"><label class="form-label fw-bold">{{ __('Services') }} ({{ __('limit: :count', ['count' => $subscription->entitlement('services_limit')]) }})</label><select name="services[]" class="form-control select2" multiple>@foreach($services as $service)<option value="{{ $service->id }}" @selected($clinic->services->contains('id', $service->id))>{{ $service->name }}</option>@endforeach</select><small class="text-muted">{{ __('Your package limit is enforced when saved.') }}</small></div>@endif
                                    @if($subscription?->hasEntitlement('emergency'))<div class="col-md-6"><label><input type="checkbox" name="emergency_available" value="1" @checked(old('emergency_available', $clinic->emergency_available))> {{ __('Emergency availability') }}</label></div>@endif
                                    @if($subscription?->hasEntitlement('reviews'))<div class="col-md-6"><label><input type="checkbox" name="reviews_enabled" value="1" @checked(old('reviews_enabled', $clinic->reviews_enabled))> {{ __('Allow provider reviews') }}</label></div>@endif
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('Location') }}</label>
                                        <input type="text" name="location" class="form-control" value="Nairobi" readonly>
                                        @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    @if($subscription?->hasEntitlement('operating_hours'))
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('Operating Hours') }}</label>
                                        <input type="text" name="operating_hours" class="form-control @error('operating_hours') is-invalid @enderror"
                                            value="{{ old('operating_hours', $clinic->operating_hours) }}"
                                            placeholder="{{ __('e.g. Mon–Fri 8am–6pm') }}">
                                        @error('operating_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    @endif

                                    @if($subscription?->hasEntitlement('insurance'))
                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ __('Insurance Accepted') }}</label>
                                        <input type="text" name="insurance_accepted" class="form-control @error('insurance_accepted') is-invalid @enderror"
                                            value="{{ old('insurance_accepted', $clinic->insurance_accepted) }}"
                                            placeholder="{{ __('e.g. NHIF, AAR, Jubilee') }}">
                                        @error('insurance_accepted')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    @endif

                                    @if($subscription?->hasEntitlement('maps'))
                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ __('Google Maps Link') }}</label>
                                        <input type="url" name="google_maps_link" class="form-control @error('google_maps_link') is-invalid @enderror"
                                            value="{{ old('google_maps_link', $clinic->google_maps_link) }}"
                                            placeholder="https://maps.google.com/...">
                                        @error('google_maps_link')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    @endif

                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ __('About') }} <span class="text-danger">*</span></label>
                                        <textarea name="description" rows="4"
                                            class="form-control @error('description') is-invalid @enderror"
                                            placeholder="{{ __('Enter About Information') }}" required>{{ old('description', $clinic->description) }}</textarea>
                                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    @foreach(['address' => __('Address'), 'educations' => __('Educations'), 'experience' => __('Experience'), 'qualifications' => __('Qualifications')] as $field => $label)
                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ $label }} <span class="text-danger">*</span></label>
                                        <textarea name="{{ $field }}" rows="4" class="form-control @error($field) is-invalid @enderror" required>{{ old($field, $clinic->{$field}) }}</textarea>
                                        @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    @endforeach

                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ __('SEO Title') }}</label>
                                        <input type="text" name="seo_title" class="form-control @error('seo_title') is-invalid @enderror" value="{{ old('seo_title', $clinic->seo_title) }}" placeholder="{{ __('Enter SEO Title') }}">
                                        @error('seo_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ __('SEO Description') }}</label>
                                        <textarea name="seo_description" rows="3" maxlength="2000" class="form-control @error('seo_description') is-invalid @enderror" placeholder="{{ __('Enter SEO Description') }}">{{ old('seo_description', $clinic->seo_description) }}</textarea>
                                        @error('seo_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    @if($subscription?->hasEntitlement('equipment'))
                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ __('Equipment & Technology') }}</label>
                                        <textarea name="equipment" rows="3" class="form-control">{{ old('equipment', $clinic->equipment) }}</textarea>
                                    </div>
                                    @endif

                                    @if($subscription?->hasEntitlement('featured_services'))
                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ __('Featured Services') }}</label>
                                        <textarea name="featured_services" rows="3" class="form-control">{{ old('featured_services', $clinic->featured_services) }}</textarea>
                                    </div>
                                    @endif

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('Image') }} <small class="text-muted">({{ __('Recommended: 300X270 PX') }})</small></label>
                                        @if($clinic->logo)
                                            <div class="mb-2">
                                                <img src="{{ asset($clinic->logo) }}" alt="logo"
                                                    style="height:60px;border-radius:6px;">
                                            </div>
                                        @endif
                                        <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12 mt-2">
                                        <button type="submit" class="btn btn-primary px-5">
                                            {{ __('Save Changes') }}
                                        </button>
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

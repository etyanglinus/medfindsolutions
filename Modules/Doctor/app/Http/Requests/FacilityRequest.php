<?php

namespace Modules\Doctor\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FacilityRequest extends FormRequest {
    public function authorize(): bool {
        return Auth::guard('admin')->check() ? true : false;
    }

    public function rules(): array {
        $rules = [
            'department_id'       => 'required|exists:departments,id',
            'location_id'         => 'nullable|exists:locations,id',
            'service_ids'         => 'nullable|array',
            'service_ids.*'       => 'nullable|exists:services,id',
            'seo_title'           => 'nullable|string|max:1000',
            'seo_description'     => 'nullable|string|max:2000',
            'status'              => 'nullable',
            'show_homepage'       => 'nullable',
            'fee'                 => 'required|numeric',
            'years_of_experience' => 'required',
            'licensing_body_id'        => ['required', 'integer', 'exists:licensing_bodies,id'],
            'accepted_insurance'       => 'nullable|string',
            'insurance_provider_ids'   => 'nullable|array',
            'insurance_provider_ids.*' => ['integer', 'exists:insurance_providers,id'],
            'about'               => 'required',
            'address'             => 'required',
            'experience'          => 'required',
        ];

        if ($this->isMethod('put')) {
            $rules['code'] = 'required|exists:languages,code';
            $rules['name'] = 'sometimes|string|max:255';
            $rules['email'] = 'required|max:255|unique:facilities,email,' . $this->facility;
            $rules['phone'] = 'required';
            $rules['facility_image'] = 'sometimes|image|max:2048';
        }
        if ($this->isMethod('post')) {
            $rules['facility_image'] = 'required|image|max:2048';
            $rules['email'] = 'required|max:255|unique:facilities,email';
            $rules['name'] = 'required|string|max:255';
            $rules['phone'] = 'required';
        }

        return $rules;
    }

    public function messages(): array {
        return [
            'department_id.required'       => __('The department is required.'),
            'department_id.exists'         => __('The selected department is invalid.'),
            'location_id.required'         => __('The location is required.'),
            'location_id.exists'           => __('The selected location is invalid.'),
            'fee.required'                 => __('Fee is required.'),
            'fee.numeric'                  => __('Fee must be a numeric.'),
            'years_of_experience.required' => __('Years of experience is required.'),
            'code.required'                => __('Language is required and must be a string.'),
            'code.exists'                  => __('The selected language is invalid.'),
            'seo_title.max'                => __('SEO title may not be greater than 1000 characters.'),
            'seo_title.string'             => __('SEO title must be a string.'),
            'seo_description.max'          => __('SEO description may not be greater than 2000 characters.'),
            'seo_description.string'       => __('SEO description must be a string.'),
            'facility_image.required'      => __('The image is required.'),
            'facility_image.image'         => __('The image must be an image.'),
            'facility_image.max'           => __('The image may not be greater than 2048 kilobytes.'),
            'name.required'                => __('Name is required'),
            'name.max'                     => __('The name may not be greater than 255 characters.'),
            'name.string'                  => __('The name must be a string.'),
            'slug.required'                => __('Slug is required.'),
            'slug.max'                     => __('The slug may not be greater than 255 characters.'),
            'slug.unique'                  => __('Slug must be unique.'),
            'phone.required'               => __('Phone number is required.'),
            'email.required'               => __('Email is required.'),
            'email.unique'                 => __('Email already exist'),
            'licensing_body_id.required'      => __('Licensing body is required.'),
            'licensing_body_id.exists'        => __('The selected licensing body is invalid.'),
            'insurance_provider_ids.array'    => __('The selected insurance providers are invalid.'),
            'insurance_provider_ids.*.exists' => __('The selected insurance provider is invalid.'),
            'accepted_insurance.string'    => __('Accepted insurance must be a string.'),
            'about.required'               => __('About information is required.'),
            'address.required'             => __('Address is required.'),
            'experience.required'          => __('Experience is required.'),
        ];
    }
}

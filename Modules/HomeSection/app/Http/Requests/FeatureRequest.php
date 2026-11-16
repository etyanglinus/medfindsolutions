<?php

namespace Modules\HomeSection\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FeatureRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     */
    public function authorize(): bool {
        return Auth::guard('admin')->check() ? true : false;
    }

    public function rules(): array {
        $rules = [
            'status'      => 'nullable',
            'code'        => 'required|string|exists:languages,code',
            'title'       => 'required|string|max:190',
            'description' => 'required',
            'image'       => 'required|image|mimes:jpeg,jpg,png,gif,webp,svg|max:2048',
            'icon'        => 'required|image|mimes:jpeg,jpg,png,gif,webp,svg|max:512',
        ];
        if ($this->isMethod('post') && !empty($this->route('id'))) {
            $rules['image'] = 'nullable|image|mimes:jpeg,jpg,png,gif,webp,svg|max:2048';
            $rules['icon'] = 'nullable|image|mimes:jpeg,jpg,png,gif,webp,svg|max:512';
        }

        return $rules;
    }

    public function messages(): array {
        return [
            'code.required'        => __('Language is required and must be a string.'),
            'code.exists'          => __('The selected language is invalid.'),

            'code.string'          => __('The language code must be a string.'),

            'title.required'       => __('The title is required.'),
            'title.string'         => __('The title must be a string.'),
            'title.max'            => __('The title must not exceed 190 characters.'),

            'icon.required'        => __('Icon is required.'),
            'icon.image'           => __('Icon is must be an image.'),
            'icon.max'             => __('Icon may not be greater than 512 kilobytes.'),

            'image.required'       => __('Image is required'),
            'image.image'          => __('The image must be an image.'),
            'image.max'            => __('The image may not be greater than 2048 kilobytes.'),

            'description.required' => __('Description is required.'),
        ];
    }
}

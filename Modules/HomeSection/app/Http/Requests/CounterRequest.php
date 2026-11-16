<?php

namespace Modules\HomeSection\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CounterRequest extends FormRequest {
    public function authorize(): bool {
        return Auth::guard('admin')->check() ? true : false;
    }

    public function rules(): array {
        $rules = [
            'status' => 'nullable',
            'code'   => 'required|string|exists:languages,code',
            'title'  => 'required|string|max:190',
            'qty'    => 'required',
            'icon'  => 'sometimes|image|max:512',
        ];

        return $rules;
    }

    public function messages(): array {
        return [
            'name.required'  => __('Name is required'),

            'code.required'  => __('Language is required and must be a string.'),
            'code.exists'    => __('The selected language is invalid.'),

            'code.string'    => __('The language code must be a string.'),

            'title.required' => __('The title is required.'),
            'title.string'   => __('The title must be a string.'),
            'title.max'      => __('The title must not exceed 190 characters.'),

            'icon.required' => __('Icon is required.'),
            'icon.image' => __('Icon is must be an image.'),
            'icon.max' => __('Icon may not be greater than 512 kilobytes.'),

            'qty.required'   => __('Counter Quantity is required.'),
        ];
    }
}

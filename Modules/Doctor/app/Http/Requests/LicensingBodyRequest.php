<?php

namespace Modules\Doctor\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LicensingBodyRequest extends FormRequest {
    public function authorize(): bool {
        return Auth::guard('admin')->check();
    }

    public function rules(): array {
        return [
            'name'   => 'required|string|max:255',
            'type'   => 'required|in:doctor,facility',
            'status' => 'nullable|boolean',
        ];
    }

    public function messages(): array {
        return [
            'name.required' => __('The name is required.'),
            'type.required' => __('The type is required.'),
            'type.in'       => __('The selected type is invalid.'),
        ];
    }
}

<?php

namespace Modules\Doctor\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class InsuranceProviderRequest extends FormRequest {
    public function authorize(): bool {
        return Auth::guard('admin')->check();
    }

    public function rules(): array {
        return [
            'name'   => 'required|string|max:255',
            'status' => 'nullable|boolean',
        ];
    }

    public function messages(): array {
        return [
            'name.required' => __('The name is required.'),
        ];
    }
}

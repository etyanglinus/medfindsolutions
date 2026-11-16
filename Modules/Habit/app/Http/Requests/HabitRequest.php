<?php

namespace Modules\Habit\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class HabitRequest extends FormRequest {
    public function authorize(): bool {
        return Auth::guard('admin')->check() ? true : false;
    }

    public function rules(): array {
        $rules = [
            'status' => 'nullable',
        ];

        if ($this->route('id')) {
            $rules['code'] = 'required|exists:languages,code';
            $rules['name'] = 'required|string|max:255|unique:habit_translations,name,' . $this->route('id') . ',habit_id';
        } else {
            $rules['name'] = 'required|string|max:255|unique:habit_translations,name';
        }

        return $rules;
    }

    public function messages(): array {
        return [
            'code.required' => __('Language is required and must be a string.'),
            'code.exists'   => __('The selected language is invalid.'),

            'name.required' => __('Name is required'),
            'name.unique'   => __('Name must be unique.'),
            'name.max'      => __('The name may not be greater than 255 characters.'),
            'name.string'   => __('The name must be a string.'),
        ];
    }
}

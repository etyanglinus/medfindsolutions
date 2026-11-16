<?php

namespace Modules\Medicine\app\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class MedicineTypeRequest extends FormRequest {
    public function authorize(): bool {
        return Auth::guard('admin')->check() ? true : false;
    }

    public function rules(): array {
        $rules = [
            'status' => 'nullable',
            'code'   => 'required|exists:languages,code',
            'type'   => 'required|string|max:255',
        ];
        if (isRoute('admin.medicine.type.update')) {
            $rules['code'] = 'required|exists:languages,code';
            $rules['type'] = ['required','string','max:255',Rule::unique('medicine_type_translations', 'type')->ignore($this->route('id'), 'medicine_type_id')];
        }

        if (isRoute('admin.medicine.type.store')) {
            $rules['type'] = 'required|string|max:255|unique:medicine_type_translations,type';
        }

        return $rules;
    }

    public function messages(): array {
        return [
            'code.required' => __('Language is required and must be a string.'),
            'code.exists'   => __('The selected language is invalid.'),

            'type.required' => __('Type is required.'),
            'type.unique'   => __('Type must be unique.'),
            'type.max'      => __('Type maximum length of 255 characters.'),
            'type.string'   => __('Type is must be a string.'),
        ];
    }
}

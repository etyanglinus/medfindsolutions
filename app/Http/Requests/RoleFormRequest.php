<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];
        $rules['permissions'] = ['nullable', 'array'];
        $rules['permissions.*'] = [\Illuminate\Validation\Rule::exists('permissions', 'name')->where('guard_name', 'admin')];
        if ($this->isMethod('put')) {
            $rules['name'] = 'required|unique:roles,name,'.$this->role;
        }
        if ($this->isMethod('post')) {
            $rules['name'] = 'required|unique:roles,name';
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => __('The role name field is required!'),
            'name.unique' => __('This role has already been taken!'),
        ];
    }
}

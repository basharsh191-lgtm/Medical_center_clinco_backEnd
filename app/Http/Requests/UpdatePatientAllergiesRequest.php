<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientAllergiesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'blood_type'        => 'nullable|string|max:5',
            'allergies'         => 'nullable|string',
            'chronic_diseases'  => 'nullable|string', // الأمراض المزمنة
            'hereditary'  => 'nullable|string', // الأمراض الوراثية
            'weight'            => 'nullable|numeric|min:1',
            'height'            => 'nullable|numeric|min:1',
            'smoking_habits'    => 'nullable|string', // عادات التدخين
        ];
    }
}

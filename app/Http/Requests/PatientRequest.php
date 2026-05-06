<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatientRequest extends FormRequest
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
            'gender' => 'required|in:male,female,other',
            'image'=>'nullable|image|mimes:png,jpg',
            'birth_date' => 'required|date|before:today',
            'blood_type' => 'required',
            'allergies' => 'nullable|string|max:500',
            'hereditary' => 'nullable|string|max:500',
            'chronic_diseases' => 'nullable|string|max:500',
            'address' => 'required|string|max:255',
        ];
    }
}

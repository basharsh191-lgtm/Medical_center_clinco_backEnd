<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DoctorRequest extends FormRequest
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
            'name'              => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'email'             => 'required|email|unique:users,email',
            'phone'             => 'required|unique:users,phone',
            'password'          => 'required|min:8',

            // بيانات الدكتور (تُطلب فقط إذا كان الدور doctor)
            'specialization_id' => 'required_if:role,doctor|exists:specializations,id',
            'clinic_id'         => 'required_if:role,doctor|exists:clinics,id',
            'experience_years'  => 'nullable|integer',
            'number_operations'  => 'nullable|integer',
            'bio'               => 'nullable|string',
            'image'             => 'required_if:role,doctor|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}

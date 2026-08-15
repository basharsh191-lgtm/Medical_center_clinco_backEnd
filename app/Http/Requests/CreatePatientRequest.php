<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePatientRequest extends FormRequest
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
            // User Data
            'name'              => ['required', 'string', 'max:255'],
            'last_name'         => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'             => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password'          => ['required', 'string', 'min:8'],

            // Patient Data
            'gender'            => ['required', 'in:male,female,other'],
            'address'           => ['required', 'string', 'max:500'],
            'birth_date'        => ['required', 'date', 'before:today'],
            'allergies'         => ['nullable', 'string'],
            'hereditary'        => ['nullable', 'string'],
            'chronic_diseases'  => ['nullable', 'string'],
            'blood_type'        => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'taller'            => ['nullable', 'integer', 'min:30', 'max:250'],
            'weight'            => ['nullable', 'integer', 'min:1', 'max:300'],
        ];
    }
    }


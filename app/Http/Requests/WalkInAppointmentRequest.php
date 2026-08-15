<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalkInAppointmentRequest extends FormRequest
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
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id'  => ['required', 'exists:doctors,id'],
            'clinic_id'  => ['required', 'exists:clinics,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ];
    }
}

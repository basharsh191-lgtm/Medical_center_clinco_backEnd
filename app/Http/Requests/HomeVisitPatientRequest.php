<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HomeVisitPatientRequest extends FormRequest
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
            'doctor_id'         => 'required|exists:doctors,id',
            'visit_date'        => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time'       => 'required|date_format:H:i',
            'end_time'         => 'required|date_format:H:i',
            'location_lat'      => 'required|numeric|between:-90,90',
            'location_lng'      => 'required|numeric|between:-180,180',
        ];
    }
}

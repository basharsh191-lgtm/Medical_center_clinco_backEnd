<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomeVisitRequest extends FormRequest
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
            'clinic_id'    => 'sometimes|required|exists:clinics,id',
            'visit_date'   => 'sometimes|required|date|after_or_equal:today',
            'start_time'   => 'sometimes|required|date_format:H:i',
            'end_time'     => 'sometimes|required|date_format:H:i|after:start_time',
            'location_lat' => 'sometimes|required|numeric|between:-90,90',
            'location_lng' => 'sometimes|required|numeric|between:-180,180',
        ];
    }
}

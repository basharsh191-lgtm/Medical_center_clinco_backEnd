<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceptionRequest extends FormRequest
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
        'name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'phone' => 'required|string|max:20',
        'password' => 'required|min:6',

        'clinic_id' => 'required|exists:clinics,id',

        'salary' => 'nullable|numeric',
        'hiring_date' => 'nullable|date',

        'status' => 'nullable|in:active,inactive,on_leave',

        'shift_type' => 'required|in:morning,evening,night,full_day',

        'biography' => 'nullable|string',

    ];
    }
}

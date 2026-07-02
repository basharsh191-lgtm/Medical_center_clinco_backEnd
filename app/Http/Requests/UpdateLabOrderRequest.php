<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabOrderRequest extends FormRequest
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
                'doctor_notes'   => 'nullable|string',
                'tests'          => 'nullable|array|min:1', // يجب إرسال تحليل واحد على الأقل
                'tests.*'        => 'nullable|string|max:255',
        ];
    }
}

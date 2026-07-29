<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
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
            'appointment_id' => 'nullable|exists:appointments,id',
            'file'           => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'title'          => 'nullable|string|max:255',
            // هون ضفنا الفاليديشن للسيرفر
            'storage_disk'   => 'nullable|string|in:public,s3,ftp',
        ];
    }
}

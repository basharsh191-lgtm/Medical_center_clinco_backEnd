<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title ?? 'ملف طبي',
            'file_type'       => $this->file_type,
            'file_url'        => Storage::disk($this->disk ?? 'public')->url($this->file_path),
            'uploaded_date'   => $this->created_at->format('Y-m-d'),
            'linked_to_visit' => $this->appointment_id !== null,
        ];
    }
}

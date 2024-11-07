<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'certificate_number' => $this->certificate_number,
            'title' => $this->title,
            'description' => $this->description,
            'recipient' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'course' => [
                'id' => $this->course->id,
                'name' => $this->course->title,
            ],
            'completion_date' => $this->completion_date->format('Y-m-d'),
            'linkedin_url' => $this->linkedin_url,
            'verification_url' => route('certificates.verify', $this->certificate_number),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

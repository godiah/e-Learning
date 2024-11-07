<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);
        return [
            'id' => $this->id,
            'course' => [
                'id' => $this->course->id,
                'name' => $this->course->title,                
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,                
            ],
            'enrollment_date' => $this->enrollment_date,
            'completion_date' => $this->completion_date,            
            'created_at' => $this->created_at,
        ];
    }
}

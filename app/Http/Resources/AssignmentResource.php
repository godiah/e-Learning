<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id' => $this->id,
            'instructor_id' => $this->instructor_id,
            'lesson_id' => $this->lesson_id,
            'title' => $this->title,           
            'description' => $this->description,           
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $course = $this->courses;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'courses' => CoursesResource::collection($this->whenLoaded('courses')),
            'created_at' => $this->created_at,
        ];
    }
}

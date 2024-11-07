<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDiscountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //  return [
        //     'id' => $this->id,
        //     'course_id' => $this->course_id,
        //     'discount_rate' => $this->discount_rate,
        //     'start_date' => $this->start_date ? $this->start_date->toDateString() : null,
        //     'end_date' => $this->end_date ? $this->end_date->toDateString() : null,
        //     'is_active' => $this->is_active,
        //     'is_currently_active' => $this->isCurrentlyActive(),
        // ];
        return [
            'id' => $this->id,
            'course' => $this->whenLoaded('course', function () {
                return [
                    'id' => $this->course->id,
                    'title' => $this->course->title,
                    'price' => $this->course->price,
                    'instructor' => [
                        'id' => $this->course->instructor->id,
                        'name' => $this->course->instructor->name,
                    ],
                ];
            }),
            'discount_rate' => $this->discount_rate,
            'start_date' => $this->start_date ? $this->start_date->toDateString() : null,
            'end_date' => $this->end_date ? $this->end_date->toDateString() : null,
            'is_active' => (bool) $this->is_active,
            'is_currently_active' => $this->isCurrentlyActive(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}

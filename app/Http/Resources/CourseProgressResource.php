<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseProgressResource extends JsonResource
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
            'course_id' => $this->course_id,
            'user_id' => $this->user_id,
            'quiz_average' => $this->quiz_average,
            'assignment_average' => $this->assignment_average,
            'total_grade' => $this->total_grade,
            'completed_items_count' => $this->completed_items_count,
            'total_items_count' => $this->total_items_count,
             'completion_percentage' => $this->total_items_count > 0 
            ? (int)round(($this->completed_items_count / $this->total_items_count) * 100)
            : 0,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'has_course_content' => $this->total_items_count > 0,
            'started' => $this->completed_items_count > 0
        ];
    }
}

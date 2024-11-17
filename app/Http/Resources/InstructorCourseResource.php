<?php

namespace App\Http\Resources;

use App\Traits\HasCourseStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class InstructorCourseResource extends JsonResource
{
    use HasCourseStatistics;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $courseStats = $this->getCachedCourseStatistics();
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'course_image' => $this->course_image,
            'total_content' => $courseStats['total_content'],
            'video_length_formatted' => $this->formattedVideoLength,
            'average_rating' => $this->reviews()->avg('rating') ?? 0,
            'total_ratings' => $this->reviews()->count(),
            'total_enrollments' => $this->enrollments()->count(),
        ];
    }
}

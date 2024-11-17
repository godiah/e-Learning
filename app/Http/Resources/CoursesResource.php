<?php

namespace App\Http\Resources;

use App\Traits\HasCourseStatistics;
use App\Traits\HasInstructorStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CoursesResource extends JsonResource
{
    use HasInstructorStatistics, HasCourseStatistics;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $instructor = $this->instructor;
        $stats = $this->getCachedInstructorStatistics($instructor);
        $courseStats = $this->getCachedCourseStatistics();

        return [
            'id' => $this->id,
            'instructor_id' => $this->instructor_id,
            'instructor' => [
                'name' => $instructor->name,
                'profile_pic' => $instructor->profile_pic,
                'bio' => $instructor->bio,
                'total_courses' => $stats['courses'],
                'average_rating' => $stats['rating'],
                'total_reviews' => $stats['reviews'],
                'Students' => $stats['enrollments'],
            ],
            'category_id' => $this->category_id,
            'category_name' => $this->category->name,
            'title' => $this->title,
            'description' => $this->description,
            'detailed_description' => $this->detailed_description,
            'course_image' => $this->course_image,
            'price' => $this->price,
            'level' => $this->level,
            'language' => $this->language,
            'objectives' => $this->objectives,
            'requirements' => $this->requirements,
            'who_is_for' => $this->who_is_for,
            'video_length' => $courseStats['video_length'],
            'video_length_formatted' => $courseStats['formatted_video_length'],
            'total_lessons' => $courseStats['total_lessons'],
            'total_quizzes' => $courseStats['total_quizzes'],
            'total_assignments' => $courseStats['total_assignments'],
            'total_content' => $courseStats['total_content'],
            'average_rating' => $courseStats['average_rating'],
            'total_ratings' => $courseStats['total_reviews'],
            'total_enrollments' => $courseStats['total_enrollments'],
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->format('d-m-Y'),
            'last_updated' => $this->last_updated->format('d-m-Y'),
        ];
    }
}

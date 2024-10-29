<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class CoursesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $instructor = $this->instructor;
        return [
            'id' => $this->id,
            'instructor_id' => $this->instructor_id,
            'instructor' => [
                'name' => $instructor->name,
                'profile_pic' => $instructor->profile_pic,
                'bio' => $instructor->bio,
                'total_courses' => $instructor->courses()->count(),
                'average_rating' => $this->getInstructorAverageRating($instructor->id),
                'total_reviews' => $this->getInstructorTotalReviews($instructor->id),
                'Students' => $this->getInstructorTotalEnrollments($instructor->id),
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
            'video_length' => $this->video_length,
            'video_length_formatted' => $this->formattedVideoLength,
            'total_lessons' => $this->totalLessons,
            'total_quizzes' => $this->totalQuizzes,
            'total_assignments' => $this->totalAssignments,
            'total_content' => $this->totalContent,
            'average_rating' => $this->reviews()->avg('rating') ?? 0,
            'total_ratings' => $this->reviews()->count(),
            'total_enrollments' => $this->enrollments()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'last_updated' => $this->last_updated,
        ];
    }

    private function getInstructorAverageRating($instructorId)
    {
        return DB::table('courses')
            ->join('reviews', 'courses.id', '=', 'reviews.course_id')
            ->where('courses.instructor_id', $instructorId)
            ->avg('reviews.rating') ?? 0;
    }

    private function getInstructorTotalReviews($instructorId)
    {
        return DB::table('courses')
            ->join('reviews', 'courses.id', '=', 'reviews.course_id')
            ->where('courses.instructor_id', $instructorId)
            ->count();
    }

    private function getInstructorTotalEnrollments($instructorId)
    {
        return DB::table('courses')
            ->join('enrollments', 'courses.id', '=', 'enrollments.course_id')
            ->where('courses.instructor_id', $instructorId)
            ->count();
    }
}

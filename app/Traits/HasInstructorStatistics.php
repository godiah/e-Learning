<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

trait HasInstructorStatistics
{
    private function getCachedInstructorStatistics($instructor)
    {
        return [
            'rating' => Cache::remember(
                "instructor:{$instructor->id}:avg_rating",
                3600,
                fn() => $this->getInstructorAverageRating($instructor->id)
            ),
            'reviews' => Cache::remember(
                "instructor:{$instructor->id}:total_reviews",
                3600,
                fn() => $this->getInstructorTotalReviews($instructor->id)
            ),
            'enrollments' => Cache::remember(
                "instructor:{$instructor->id}:total_enrollments",
                3600,
                fn() => $this->getInstructorTotalEnrollments($instructor->id)
            ),
            'courses' => Cache::remember(
                "instructor:{$instructor->id}:total_courses",
                3600,
                fn() => $instructor->courses()->count()
            ),
        ];
    }

    private function getInstructorAverageRating($instructorId)
    {
        return DB::table('courses')
            ->join('reviews', 'courses.id', '=', 'reviews.course_id')
            ->where('courses.instructor_id', $instructorId)
            ->select(DB::raw('ROUND(AVG(reviews.rating), 1) as average'))
            ->value('average') ?? 0;
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
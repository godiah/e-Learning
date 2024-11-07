<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * Clear all caches related to an instructor
     */
    public static function clearInstructorCaches($instructorId)
    {
        Cache::forget("instructor:{$instructorId}:avg_rating");
        Cache::forget("instructor:{$instructorId}:total_reviews");
        Cache::forget("instructor:{$instructorId}:total_enrollments");
        Cache::forget("instructor:{$instructorId}:total_courses");
    }

    /**
     * Clear all caches related to a course
     */
    public static function clearCourseCaches($courseId)
    {
        $cacheKeys = [
            'avg_rating',
            'total_reviews',
            'total_enrollments',
            'total_lessons',
            'total_quizzes',
            'total_assignments',
            'total_content',
            'video_length',
            'formattedVideoLength'
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget("course:{$courseId}:{$key}");
        }
    }

     /**
     * Clear all related caches when a review is added/updated/deleted
     */
    public static function clearReviewRelatedCaches($review)
    {
        $courseId = $review->course_id;
        $instructorId = $review->course->instructor_id;

        self::clearCourseCaches($courseId);
        self::clearInstructorCaches($instructorId);
    }

    /**
     * Clear all related caches when an enrollment changes
     */
    public static function clearEnrollmentRelatedCaches($enrollment)
    {
        $courseId = $enrollment->course_id;
        $instructorId = $enrollment->course->instructor_id;

        self::clearCourseCaches($courseId);
        self::clearInstructorCaches($instructorId);
    }
}
<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait HasCourseStatistics
{
    /**
     * Cache duration in seconds
     */
    protected const CACHE_DURATION = 3600;

    /**
     * Get all cached statistics for a course
     */
    public function getCachedCourseStatistics(): array
    {
        return [
            'average_rating' => $this->getCachedValue('avg_rating', fn() => $this->calculateAverageRating()),
            'total_reviews' => $this->getCachedValue('total_reviews', fn() => $this->calculateTotalReviews()),
            'total_enrollments' => $this->getCachedValue('total_enrollments', fn() => $this->calculateTotalEnrollments()),
            'total_lessons' => $this->total_lessons,
            'total_quizzes' => $this->total_quizzes,
            'total_assignments' => $this->total_assignments,
            'total_content' => $this->total_content,
            'video_length' => $this->getCachedValue('video_length', fn() => $this->calculateVideoLength()),
            'formatted_video_length' => $this->getCachedValue('formatted_video_length', fn() => $this->formatVideoLength()),
        ];
    }

    /**
     * Get a cached value or calculate it if not cached
     */
    protected function getCachedValue(string $key, callable $callback)
    {
        $cacheKey = "course:{$this->id}:{$key}";
        return Cache::remember($cacheKey, static::CACHE_DURATION, $callback);
    }

    /**
     * Calculate average rating
     */
    protected function calculateAverageRating(): string
    {
        return number_format($this->reviews()->avg('rating') ?? 0, 1);
    }

    /**
     * Calculate total reviews
     */
    protected function calculateTotalReviews(): int
    {
        return $this->reviews()->count();
    }

    /**
     * Calculate total enrollments
     */
    protected function calculateTotalEnrollments(): int
    {
        return $this->enrollments()->count();
    }

    /**
     * Calculate and update total lessons
     */
    public function calculateTotalLessons(): int
    {
        $totalLessons = $this->lessons()->withCount('subcontents')->get()->sum('subcontents_count'); //i included subcontents()
        $this->total_lessons = $totalLessons;
        $this->save();

        return $totalLessons;
    }

    /**
     * Calculate and update total quizzes
     */
    public function calculateTotalQuizzes(): int
    {
        $totalQuizzes = $this->quizzes()->count();
        $this->total_quizzes = $totalQuizzes;
        $this->save();

        return $totalQuizzes;
    }

    /**
     * Calculate and update total assignments
     */
    public function calculateTotalAssignments(): int
    {
        $totalAssignments = $this->assignments()->count();
        $this->total_assignments = $totalAssignments;
        $this->save();

        return $totalAssignments;
    }

    /**
     * Calculate and update total content items
     */
    public function calculateTotalContent(): int
    {
        $totalContent = $this->calculateTotalLessons() +
                        $this->calculateTotalQuizzes() +
                        $this->calculateTotalAssignments();

        $this->total_content = $totalContent;
        $this->save();

        return $totalContent;
    }


    /**
     * Calculate total video length in minutes
     */
    public function calculateVideoLength(): int
    {
        return (int) $this->lessons()->sum('total_watch_time'); // changed from video_duration
    }

    /**
     * Update video length calculations
     */
    public function updateVideoLength(): void
    {
        $totalMinutes = $this->calculateVideoLength();
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        $this->update([
            'video_length' => $totalMinutes,
            'video_length_hours' => $hours,
            'video_length_minutes' => $minutes,
        ]);

        // Clear related caches
        Cache::forget("course:{$this->id}:video_length");
        Cache::forget("course:{$this->id}:formatted_video_length");
    }

    /**
     * Format video length for display
     */
    protected function formatVideoLength(): string
    {
        if ($this->video_length_hours > 0) {
            return "{$this->video_length_hours}h {$this->video_length_minutes}m";
        }
        return "{$this->video_length_minutes}m";
    }

    /**
     * Accessor for formatted video length
     */
    public function getFormattedVideoLengthAttribute(): string
    {
        return $this->getCachedValue('formatted_video_length', fn() => $this->formatVideoLength());
    }

    /**
     * Public methods for accessing statistics
     */
    public function averageRating(): string
    {
        return $this->getCachedValue('avg_rating', fn() => $this->calculateAverageRating());
    }

    public function totalReviews(): int
    {
        return $this->getCachedValue('total_reviews', fn() => $this->calculateTotalReviews());
    }

    public function totalEnrollments(): int
    {
        return $this->getCachedValue('total_enrollments', fn() => $this->calculateTotalEnrollments());
    }

    public function totalLessons(): int
    {
        return $this->getCachedValue('total_lessons', fn() => $this->calculateTotalLessons());
    }

    public function totalQuizzes(): int
    {
        return $this->getCachedValue('total_quizzes', fn() => $this->calculateTotalQuizzes());
    }

    public function totalAssignments(): int
    {
        return $this->getCachedValue('total_assignments', fn() => $this->calculateTotalAssignments());
    }

    public function totalContent(): int
    {
        return $this->getCachedValue('total_content', fn() => $this->calculateTotalContent());
    }
}
<?php

namespace App\Services;

use App\Models\Courses;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\AssignmentSubmission;
use App\Models\CourseProgress;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CourseProgressService
{
    const CACHE_TTL = 60;

    /**
     * Generate cache key for progress
     */
    private function getProgressCacheKey(int $courseId, int $userId): string
    {
        return "course_progress:{$courseId}:user:{$userId}";
    }

    /**
     * Generate cache key for quiz statistics
     */
    private function getQuizStatsCacheKey(int $courseId, int $userId): string
    {
        return "quiz_stats:{$courseId}:user:{$userId}";
    }

    /**
     * Generate cache key for assignment statistics
     */
    private function getAssignmentStatsCacheKey(int $courseId, int $userId): string
    {
        return "assignment_stats:{$courseId}:user:{$userId}";
    }

    /**
     * Clear all related cache for a user's course progress
     */
    private function clearProgressCache(int $courseId, int $userId): void
    {
        Cache::forget($this->getProgressCacheKey($courseId, $userId));
        Cache::forget($this->getQuizStatsCacheKey($courseId, $userId));
        Cache::forget($this->getAssignmentStatsCacheKey($courseId, $userId));
    }

    /**
     * Calculate and update course progress for a user
     *
     * @param int $courseId
     * @param int $userId
     * @return CourseProgress
     */
    public function calculateProgress($courseId, $userId)
    {
        $cacheKey = $this->getProgressCacheKey($courseId, $userId);

        // Try to get from cache first
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $userId) {
            try {
                DB::beginTransaction();

                $course = Courses::findOrFail($courseId);

                $enrollment = $course->enrollments()
                    ->where('user_id', $userId)
                    ->first();

                if (!$enrollment) {
                    throw new Exception('User is not enrolled in this course');
                }

                // Get total items count first
                $totalQuizzes = $course->lessons()
                    ->withCount('quizzes')
                    ->get()
                    ->sum('quizzes_count');

                

                // If course has no quizzes, return early
                if ($totalQuizzes === 0) {
                    $progress = $this->createEmptyProgress($courseId, $userId);
                    DB::commit();
                    return $progress;
                }

                // Calculate quiz and assignment statistics
                $quizStats = $this->calculateQuizStatistics($courseId, $userId);
                $assignmentStats = $this->calculateAssignmentStatistics($courseId, $userId);

                // Calculate overall progress
                $progress = $this->updateProgressRecord(
                    $course,
                    $userId,
                    $quizStats,
                    $assignmentStats
                );

                DB::commit();
                return $progress;
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error calculating course progress', [
                    'course_id' => $courseId,
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    private function createEmptyProgress($courseId, $userId)
    {
        return CourseProgress::updateOrCreate(
            [
                'course_id' => $courseId,
                'user_id' => $userId
            ],
            [
                'quiz_average' => 0,
                'assignment_average' => 0,
                'total_grade' => 0,
                'completed_items_count' => 0,
                'total_items_count' => 0,
                'status' => 'in_progress'
            ]
        );
    }

    /**
     * Calculate quiz statistics for the course
     *
     * @param int $courseId
     * @param int $userId
     * @return array
     */
    private function calculateQuizStatistics($courseId, $userId)
    {
        $cacheKey = $this->getQuizStatsCacheKey($courseId, $userId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $userId) {
            $quizAttempts = QuizAttempt::select(
                'quiz_id',
                DB::raw('MAX(score) as highest_score'),
                DB::raw('COUNT(*) as attempt_count')
            )
                ->where('user_id', $userId)
                ->whereHas('quiz', function ($query) use ($courseId) {
                    $query->whereHas('lesson', function ($q) use ($courseId) {
                        $q->where('course_id', $courseId);
                    });
                })
                ->groupBy('quiz_id')
                ->get();

            $totalQuizzes = Courses::findOrFail($courseId)
                ->lessons()
                ->withCount('quizzes')
                ->get()
                ->sum('quizzes_count');

            return [
                'attempts' => $quizAttempts,
                'average' => $quizAttempts->avg('highest_score') ?? 0,
                'total_quizzes' => $totalQuizzes,
                'completed_count' => $quizAttempts->count()
            ];
        });
    }

    /**
     * Calculate assignment statistics for the course
     *
     * @param int $courseId
     * @param int $userId
     * @return array
     */
    private function calculateAssignmentStatistics($courseId, $userId)
    {
        $cacheKey = $this->getAssignmentStatsCacheKey($courseId, $userId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $userId) {
            $assignmentSubmissions = AssignmentSubmission::select(
                'assignment_id',
                DB::raw('MAX(grade) as highest_grade'),
                DB::raw('COUNT(*) as submission_count')
            )
                ->where('user_id', $userId)
                ->whereHas('assignment', function ($query) use ($courseId) {
                    $query->whereHas('lesson', function ($q) use ($courseId) {
                        $q->where('course_id', $courseId);
                    });
                })
                ->groupBy('assignment_id')
                ->get();

            $totalAssignments = Courses::findOrFail($courseId)
                ->lessons()
                ->withCount('assignments')
                ->get()
                ->sum('assignments_count');

            return [
                'submissions' => $assignmentSubmissions,
                'average' => $assignmentSubmissions->avg('highest_grade') ?? 0,
                'total_assignments' => $totalAssignments,
                'completed_count' => $assignmentSubmissions->count()
            ];
        });
    }

    /**
     * Update or create progress record
     *
     * @param Course $course
     * @param int $userId
     * @param array $quizStats
     * @param array $assignmentStats
     * @return CourseProgress
     */
    private function updateProgressRecord($course, $userId, $quizStats, $assignmentStats)
    {
        $completedItemsCount = $quizStats['completed_count'];
        $totalItemsCount = $quizStats['total_quizzes'];

        // Calculate total grade
        $totalGrade = $quizStats['average'];

        // Determine status
        $status = $this->determineProgressStatus(
            $completedItemsCount,
            $totalItemsCount,
            $totalGrade,
            $course->pass_mark
        );

        // Update or create progress record
        $progress = CourseProgress::updateOrCreate(
            [
                'course_id' => $course->id,
                'user_id' => $userId
            ],
            [
                'quiz_average' => $quizStats['average'],
                'assignment_average' => $assignmentStats['average'],
                'total_grade' => $totalGrade,
                'completed_items_count' => $completedItemsCount,
                'total_items_count' => $totalItemsCount,
                'status' => $status
            ]
        );

        // If failed, reset progress
        if ($status === 'failed') {
            $this->resetCourseProgress($course->id, $userId);
            // Update status to in_progress after reset
            $progress->update(['status' => 'in_progress']);
        }

        return $progress;
    }

    /**
     * Determine the progress status based on completion and grades
     *
     * @param int $completedItems
     * @param int $totalItems
     * @param float $totalGrade
     * @param float $passMark
     * @return string
     */
    private function determineProgressStatus($completedItems, $totalItems, $totalGrade, $passMark)
    {
        if ($completedItems === $totalItems) {
            return $totalGrade >= $passMark ? 'completed' : 'failed';
        }
        return 'in_progress';
    }

    /**
     * Reset course progress by deleting all attempts and submissions
     *
     * @param int $courseId
     * @param int $userId
     * @return void
     */
    public function resetCourseProgress($courseId, $userId)
    {
        try {
            DB::beginTransaction();

            // Delete quiz attempts
            QuizAttempt::where('user_id', $userId)
                ->whereHas('quiz', function ($query) use ($courseId) {
                    $query->whereHas('lesson', function ($q) use ($courseId) {
                        $q->where('course_id', $courseId);
                    });
                })
                ->delete();

            // Delete assignment submissions
            AssignmentSubmission::where('user_id', $userId)
                ->whereHas('assignment', function ($query) use ($courseId) {
                    $query->whereHas('lesson', function ($q) use ($courseId) {
                        $q->where('course_id', $courseId);
                    });
                })
                ->delete();

            // Clear the cache
            $this->clearProgressCache($courseId, $userId);

            DB::commit();

            Log::info('Course progress reset successfully', [
                'course_id' => $courseId,
                'user_id' => $userId
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error resetting course progress', [
                'course_id' => $courseId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Clear progress cache when new quiz attempt or assignment submission is made
     */
    public function refreshProgress($courseId, $userId)
    {
        $this->clearProgressCache($courseId, $userId);
    }

    /**
     * Check if a certificate can be awarded
     *
     * @param int $courseId
     * @param int $userId
     * @return bool
     */
    public function canAwardCertificate($courseId, $userId)
    {
        $progress = CourseProgress::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->first();

        return $progress && $progress->status === 'completed';
    }
}
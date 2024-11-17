<?php

namespace App\Http\Controllers\Api;

use App\Enums\LessonProgressStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonProgressResource;
use App\Models\Courses;
use App\Models\LessonProgress;
use App\Models\Lessons;
use App\Models\LessonSubcontent;
use App\Models\QuizAttempt;
use App\Models\SubcontentProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VideoProgressController extends Controller
{
    /**
     * View user progress
     */
    public function viewProgress($courseId)
    {
        $user = Auth::user();
        
        // Get the course with all its lessons and subcontents
        $course = Courses::with(['lessons' => function ($query) {
            $query->orderBy('order_index');
        }, 'lessons.subcontents' => function ($query) {
            $query->orderBy('order_index');
        }, 'lessons.quizzes'])->findOrFail($courseId);
        
        $totalLessons = $course->lessons->count();
        $completedLessonsCount = 0;
        $lessons = [];
        
        // Track time-based metrics
        $totalWatchTime = 0;
        $totalVideoDuration = 0;
        $lastActivityDate = null;
        $firstActivityDate = null;
        
        // Track engagement metrics
        $totalQuizAttempts = 0;
        $passedQuizzes = 0;
        $averageQuizScore = 0;
        $quizScores = [];
        
        foreach ($course->lessons as $lesson) {
            $subcontents = [];
            $lessonCompletedCount = 0;
            $totalSubcontents = $lesson->subcontents->count();
            $lessonWatchTime = 0;
            $lessonVideoDuration = 0;
            
            // Track quiz performance for this lesson
            foreach ($lesson->quizzes as $quiz) {
                $attempts = QuizAttempt::where([
                    'user_id' => $user->id,
                    'quiz_id' => $quiz->id
                ])->get();
                
                $totalQuizAttempts += $attempts->count();
                $bestScore = $attempts->max('score');
                if ($bestScore >= $quiz->passing_score) {
                    $passedQuizzes++;
                }
                if ($bestScore) {
                    $quizScores[] = $bestScore;
                }
            }
            
            foreach ($lesson->subcontents as $subcontent) {
                $progress = SubcontentProgress::where([
                    'user_id' => $user->id,
                    'subcontent_id' => $subcontent->id,
                    'lesson_id' => $lesson->id
                ])->first();
                
                // Update time-based metrics
                if ($progress) {
                    $lessonWatchTime += $progress->watch_time;
                    $lastActivityDate = $lastActivityDate 
                        ? max($lastActivityDate, $progress->updated_at)
                        : $progress->updated_at;
                    $firstActivityDate = $firstActivityDate 
                        ? min($firstActivityDate, $progress->created_at)
                        : $progress->created_at;
                }
                
                $lessonVideoDuration += $subcontent->video_duration;
                
                $subcontents[] = [
                    'id' => $subcontent->id,
                    'name' => $subcontent->name,
                    'order_index' => $subcontent->order_index,
                    'video_duration' => $subcontent->video_duration,
                    'status' => [
                        'is_completed' => $progress ? $progress->is_completed : false,
                        'watch_time' => $progress ? $progress->watch_time : 0,
                        'last_position' => $progress ? $progress->last_position : 0,
                        'completed_at' => $progress ? $progress->completed_at : null,
                        'rewatch_count' => $progress ? floor($progress->watch_time / $subcontent->video_duration) : 0
                    ]
                ];
                
                if ($progress && $progress->is_completed) {
                    $lessonCompletedCount++;
                }
            }
            
            $totalWatchTime += $lessonWatchTime;
            $totalVideoDuration += $lessonVideoDuration;
            
            $lessonCompleted = ($totalSubcontents > 0 && $lessonCompletedCount === $totalSubcontents);
            if ($lessonCompleted) {
                $completedLessonsCount++;
            }
            
            // Calculate lesson completion percentage
            $lessonProgress = $totalSubcontents > 0 
                ? round(($lessonCompletedCount / $totalSubcontents) * 100, 1)
                : 0;
                
            $lessons[] = [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'order_index' => $lesson->order_index,
                'is_completed' => $lessonCompleted,
                'progress_percentage' => $lessonProgress,
                'subcontents' => $subcontents,
                'total_subcontents' => $totalSubcontents,
                'completed_subcontents' => $lessonCompletedCount,
                'watch_metrics' => [
                    'total_watch_time' => $lessonWatchTime,
                    'total_duration' => $lessonVideoDuration,
                    'engagement_ratio' => $lessonVideoDuration > 0 
                        ? round($lessonWatchTime / $lessonVideoDuration, 2)
                        : 0
                ]
            ];
        }
        
        // Calculate quiz performance metrics
        $averageQuizScore = count($quizScores) > 0 
            ? round(array_sum($quizScores) / count($quizScores), 1)
            : 0;
        
        // Calculate overall course completion percentage
        $courseProgress = $totalLessons > 0 
            ? round(($completedLessonsCount / $totalLessons) * 100, 1)
            : 0;
            
        // Calculate study pattern metrics
        $studyDuration = $firstActivityDate 
            ? $lastActivityDate->diffInDays($firstActivityDate) + 1
            : 0;
        
        return response()->json([
            'course' => [
                'id' => $course->id,
                'name' => $course->title,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessonsCount,
                'progress_percentage' => $courseProgress,
            ],
            'lessons' => collect($lessons)->groupBy('is_completed'),
            'summary' => [
                'completed' => $completedLessonsCount,
                'pending' => $totalLessons - $completedLessonsCount,
                'total' => $totalLessons
            ],
            'engagement_metrics' => [
                'total_watch_time' => $totalWatchTime,
                'total_course_duration' => $totalVideoDuration,
                'engagement_ratio' => $totalVideoDuration > 0 
                    ? round($totalWatchTime / $totalVideoDuration, 2)
                    : 0,
                'study_duration_days' => $studyDuration,
                'average_daily_time' => $studyDuration > 0 
                    ? round($totalWatchTime / $studyDuration, 2)
                    : 0,
                'last_activity' => $lastActivityDate,
                'first_activity' => $firstActivityDate
            ],
            'quiz_metrics' => [
                'total_attempts' => $totalQuizAttempts,
                'passed_quizzes' => $passedQuizzes,
                'average_score' => $averageQuizScore,
                'completion_rate' => $totalQuizAttempts > 0 
                    ? round(($passedQuizzes / $totalQuizAttempts) * 100, 1)
                    : 0
            ]
        ]);
    }

    /**
     * Method to update sub content progress of a lesson
     */
    public function updateProgress(Request $request, $subcontentId)
    {
        $user = Auth::user();

        $subcontent = LessonSubcontent::findOrFail($subcontentId);
        $lesson = $subcontent->lesson;

        if (!$this->checkEnrollment($subcontent)) {
            return response()->json(['message' => 'Not enrolled in this course'], 403);
        }

        $currentLessonOrder = $lesson->order_index;

        if ($currentLessonOrder > 1) {
            $previousLesson = Lessons::where('course_id', $lesson->course_id)
                ->where('order_index', $currentLessonOrder - 1)
                ->with('quizzes')
                ->first();

            $firstQuiz = $previousLesson->quizzes->first();

            if ($previousLesson) {                
                $quizCompleted = QuizAttempt::where('user_id', $user->id)
                    ->where('quiz_id', $firstQuiz->id)
                    ->where('status', 'completed')
                    ->exists();

                if (!$quizCompleted) {
                    return response()->json([
                        'error' => 'Quiz requirement not met',
                        'message' => "Please complete the quiz '{$firstQuiz->title}' for '{$previousLesson->title}' before starting this lesson.",
                    ], Response::HTTP_FORBIDDEN);
                }
            }
        }

        $request->validate([
            'watch_time' => 'required|integer', // accumulated watch time i.e. repeated views and engagement time
            'last_position' => 'required|integer' //position user stopped watching i.e. enable resume playback.
        ]);

        $lesson = $subcontent->lesson;

        // Check if this is the first subcontent
        if ($subcontent->order_index > 1) {
            // Get the previous subcontent
            $previousSubcontent = LessonSubcontent::where('lesson_id', $lesson->id)
                ->where('order_index', $subcontent->order_index - 1)
                ->first();

            if ($previousSubcontent) {
                // Check if previous subcontent is completed
                $previousProgress = SubcontentProgress::where('user_id', $user->id)
                    ->where('subcontent_id', $previousSubcontent->id)
                    ->where('is_completed', true)
                    ->first();

                if (!$previousProgress) {
                    return response()->json([
                        'error' => 'Sequential violation',
                        'message' => "Please complete '{$previousSubcontent->name}' before proceeding with this video.",
                        'previous_subcontent' => [
                            'id' => $previousSubcontent->id,
                            'name' => $previousSubcontent->name,
                            'order_index' => $previousSubcontent->order_index
                        ]
                    ], Response::HTTP_FORBIDDEN);
                }
            }
        }

        $tolerance = 1;
        $isCompleted = ($request->last_position >= ($subcontent->video_duration - $tolerance));

        $progress = SubcontentProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'subcontent_id' => $subcontentId,
                'lesson_id' => $lesson->id
            ],
            [
                'watch_time' => $request->watch_time,
                'last_position' => $request->last_position,
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null
            ]
        );

        $lessonCompleted = $this->checkLessonCompletion($lesson->id);

        if ($lessonCompleted) {
            $this->updateLessonProgress($lesson->id, true);
        }

        return response()->json([
            'progress' => $progress,
            'next_subcontent' => $this->getNextAvailableSubcontent($lesson->id)
        ]);
    }

    private function checkEnrollment(LessonSubcontent $subcontent): bool
    {
        $course = $subcontent->lesson->course ?? null;

        if (!$course) {
            throw new \Exception("Subcontent's lesson does not have an associated course");
        }

        return $course->enrollments()
            ->where('user_id', Auth::id())
            ->exists();
    }

    private function checkLessonCompletion($lessonId)
    {
        $lesson = Lessons::with(['subcontents' => function($query) {
            $query->whereNotNull('video_duration');
        }])->findOrFail($lessonId);

        $userProgress = SubcontentProgress::where('user_id', Auth::id())
            ->where('lesson_id', $lessonId)
            ->get();

        $videoSubcontentsCount = $lesson->subcontents->count();

        if ($userProgress->count() < $videoSubcontentsCount) {
            return false;
        }

        $tolerance = 1; // 1 minute tolerance

        // Check completion for each subcontent with video
        foreach ($lesson->subcontents as $subcontent) {
            $progress = $userProgress->firstWhere('subcontent_id', $subcontent->id);

            if (!$progress) {
                return false;
            }

            if ($progress->last_position < ($subcontent->video_duration - $tolerance)) {
                return false;
            }

            if (!$progress->is_completed) {
                return false;
            }
        }

        return true;
    }

    private function getNextAvailableSubcontent($lessonId)
    {
        $lesson = Lessons::with(['subcontents' => function($query) {
            $query->orderBy('order_index');
        }])->findOrFail($lessonId);

        $lastCompletedProgress = SubcontentProgress::where('user_id', Auth::id())
            ->where('lesson_id', $lessonId)
            ->where('is_completed', true)
            ->orderBy('subcontent_id', 'desc')
            ->first();

        if (!$lastCompletedProgress) {
            return $lesson->subcontents->first();
        }

        return $lesson->subcontents
            ->where('order_index', '>', $lastCompletedProgress->subcontent->order_index)
            ->first();
    }

    private function updateLessonProgress($lessonId, $completed)
    {
        try {
            $user = request()->user();

            $totalWatchTime = SubcontentProgress::where('user_id', $user->id)
                ->where('lesson_id', $lessonId)
                ->sum('watch_time');

            // Get all completed subcontent IDs for this lesson
            $completedSubcontentIds = SubcontentProgress::where('user_id', $user->id)
                ->where('lesson_id', $lessonId)
                ->where('is_completed', true)
                ->pluck('subcontent_id')
                ->toArray();

            // Verify all subcontents are actually completed
            $allSubcontents = LessonSubcontent::where('lesson_id', $lessonId)
                ->pluck('id')
                ->toArray();

            // Double check if all subcontents are really completed
            $allCompleted = count($completedSubcontentIds) === count($allSubcontents) &&
                empty(array_diff($allSubcontents, $completedSubcontentIds));

            if (!$allCompleted) {
                // If not all subcontents are completed, update progress but don't mark as completed
                return $this->createOrUpdateProgress($lessonId, $totalWatchTime, false);
            }

            return $this->createOrUpdateProgress($lessonId, $totalWatchTime, true);

        } catch (\Exception $e) {
            Log::error('Error updating lesson progress: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createOrUpdateProgress($lessonId, $totalWatchTime, $isCompleted)
    {
        return DB::transaction(function () use ($lessonId, $totalWatchTime, $isCompleted) {
            $progress = LessonProgress::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'lesson_id' => $lessonId,
                ],
                [
                    'time_watched' => $totalWatchTime,
                    'status' => $isCompleted ? 
                        LessonProgressStatus::COMPLETED : 
                        LessonProgressStatus::IN_PROGRESS,
                    'last_watched_at' => now(),
                    'completed_at' => $isCompleted ? now() : null,
                    'completed_subcontents' => SubcontentProgress::where('user_id', Auth::id())
                        ->where('lesson_id', $lessonId)
                        ->where('is_completed', true)
                        ->pluck('subcontent_id')
                        ->toArray()
                ]
            );
            return $progress;
        });
    }
}

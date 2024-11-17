<?php

namespace App\Http\Controllers\Api;

use App\Enums\LessonProgressStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonProgressResource;
use App\Models\Courses;
use App\Models\LessonProgress;
use App\Models\Lessons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonProgressController extends Controller
{
    // public function show(Lessons $lesson)
    // {
    //     $user = Auth::user();

    //     if (!$this->checkEnrollment($lesson)) {
    //         return response()->json(['message' => 'Not enrolled in this course'], 403);
    //     }        

    //     // Check if all prior lessons are completed
    //     if (!$this->hasCompletedPriorLessons($lesson, $user->id)) {
    //         return response()->json([
    //             'message' => 'Complete previous lessons before proceeding.'
    //         ], 403);
    //     }

    //     $progress = LessonProgress::firstOrCreate(
    //         [
    //             'user_id' => $user->id,
    //             'lesson_id' => $lesson->id
    //         ],
    //         [
    //             'time_watched' => 0,
    //             'status' => LessonProgressStatus::NOT_STARTED
    //         ]
    //     );

    //     return response()->json([
    //         'progress' => new LessonProgressResource($progress),
    //         'can_proceed' => $progress->isCompleted()
    //     ]);
    // }

    // public function update(Request $request, Lessons $lesson)
    // {
    //     $user = Auth::id();

    //     if (!$this->checkEnrollment($lesson)) {
    //         return response()->json(['message' => 'Not enrolled in this course'], 403);
    //     }
        
    //     $request->validate([
    //         'time_watched' => 'required|integer|min:0'
    //     ]);

    //     // Ensure all prior lessons are completed
    //     if (!$this->hasCompletedPriorLessons($lesson, $user)) {
    //         return response()->json([
    //             'message' => 'Complete previous lessons before proceeding.'
    //         ], 403);
    //     }

    //     $progress = LessonProgress::firstOrCreate(
    //         [
    //             'user_id' => Auth::id(),
    //             'lesson_id' => $lesson->id
    //         ],
    //         [
    //             'time_watched' => 0,
    //             'status' => LessonProgressStatus::NOT_STARTED
    //         ]
    //     );

    //     $progress->updateProgress($request->time_watched, $lesson);

    //     return response()->json([
    //         'progress' => new LessonProgressResource($progress),
    //         'can_proceed' => $progress->isCompleted()
    //     ]);
    // }

    public function getCourseProgress($courseId)
    {
        $user = Auth::user();

        $course = Courses::with(['lessons' => function ($query) {
            $query->orderBy('order_index');
        }])->findOrFail($courseId);

        if (!$course->enrollments()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Not enrolled in this course'], 403);
        }

        // Fetch lessons and attach user-specific progress
        $lessons = $course->lessons->map(function ($lesson) use ($user) {
            $progress = LessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->first();

            return [
                'lesson' => $lesson,
                'progress' => $progress ? new LessonProgressResource($progress) : null,
            ];
        });

        return response()->json(['lessons' => $lessons]);
    }

    // private function canProceedToLesson(Lessons $lesson): bool
    // {
    //     if (!$lesson) {
    //         return true; // No next lesson
    //     }

    //     // Check if previous lesson exists and is completed
    //     $previousLesson = Lessons::where('course_id', $lesson->course_id)
    //         ->where('order_index', '<', $lesson->order_index)
    //         ->orderBy('order_index', 'desc')
    //         ->first();

    //     if (!$previousLesson) {
    //         return true; // First lesson
    //     }

    //     return LessonProgress::where('user_id', Auth::id())
    //         ->where('lesson_id', $previousLesson->id)
    //         ->where('status', LessonProgressStatus::COMPLETED)
    //         ->exists();
    // }

    private function checkEnrollment(Lessons $lesson): bool
    {
        return $lesson->course->enrollments()
            ->where('user_id', Auth::id())
            ->exists();
    }

    private function hasCompletedPriorLessons(Lessons $lesson, int $userId): bool
    {
        $priorLessons = Lessons::where('course_id', $lesson->course_id)
            ->where('order_index', '<', $lesson->order_index)
            ->pluck('id');

        // Check if there are any prior lessons that are not completed
        $incompletePriorLessons = LessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $priorLessons)
            ->where('status', '!=', LessonProgressStatus::COMPLETED)
            ->exists();

        return !$incompletePriorLessons;
    }

}

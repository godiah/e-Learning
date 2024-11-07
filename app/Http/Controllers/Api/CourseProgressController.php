<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CourseProgressService;
use App\Models\Courses;
use App\Http\Resources\CourseProgressResource;
use Illuminate\Http\Request;
use Exception;

class CourseProgressController extends Controller
{
    protected $progressService;

    public function __construct(CourseProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

     /**
     * Get course progress for current user
     *
     * @param Courses $course
     * @return JsonResponse
     */
    public function show(Courses $course)
    {
        $user = request()->user();

        try {
            // Check if user is enrolled
            $isEnrolled = $course->enrollments()
                ->where('user_id', $user->id)
                ->exists();

            if (!$isEnrolled) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be enrolled in this course to view progress'
                ], 403);
            }

            $progress = $this->progressService->calculateProgress(
                $course->id,
                $user->id
            );

            return response()->json([
                'success' => true,
                'data' => new CourseProgressResource($progress),
                'can_get_certificate' => $this->progressService->canAwardCertificate(
                    $course->id,
                    $user->id
                )
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching course progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset course progress for current user
     *
     * @param Course $course
     * @return JsonResponse
     */
    public function reset(Courses $course)
    {
        $user = request()->user();
        try {
            // Check if user is enrolled
            $isEnrolled = $course->enrollments()
                ->where('user_id', $user->id)
                ->exists();

            if (!$isEnrolled) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be enrolled in this course to view progress'
                ], 403);
            }
            
            $this->progressService->resetCourseProgress(
                $course->id,
                $user->id
            );

            $progress = $this->progressService->calculateProgress(
                $course->id,
               $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Course progress reset successfully',
                'data' => new CourseProgressResource($progress)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error resetting course progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

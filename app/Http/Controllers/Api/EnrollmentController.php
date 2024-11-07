<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\AssignmentSubmission;
use App\Models\Courses;
use App\Models\Enrollment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EnrollmentController extends Controller
{
    use AuthorizesRequests;

    // Enroll a student in a course
    public function enroll(Courses $course)
    {
        $user = request()->user();

        $existingEnrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'message' => 'You are already enrolled in this course'
            ]);
        }

        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_date' => now(),
        ]);

        return response()->json([
            'message' => 'You have been enrolled in this course',
            'data' => new EnrollmentResource($enrollment)
        ]);        
    }

    // Withdraw from a course
    public function withdraw(Enrollment $enrollment)
    {
        try {
            $this->authorize('delete', $enrollment);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorised Action'], 403);
        }
        
        $submissions = AssignmentSubmission::where('user_id', $enrollment->user_id)
                                       ->whereHas('assignment.lesson', function ($query) use ($enrollment) {
                                           $query->where('course_id', $enrollment->course_id);
                                       })
                                       ->get();

        // Delete each submission and any associated files
        foreach ($submissions as $submission) {
            if ($submission->submission_file_path) {
                Storage::disk('public')->delete($submission->submission_file_path);
            }
            $submission->delete();
        }

        $enrollment->delete();

        return response()->json([
            'message' => 'Successfully withdrawn from the course'
        ]);
    }

    // Get all enrollments for a given course
    public function getCourseEnrollments(Request $request, Courses $course)
    {
        try {
            $user = $request->user();

            if (!$user->is_admin && $user->id !== $course->instructor_id) {
                return response()->json(['message' => 'Unauthorized access.'], 403);
            }

            $enrollments = Enrollment::where('course_id', $course->id)
                ->with(['user', 'course'])
                ->paginate(15);

            if ($enrollments->isEmpty()) {
                return response()->json(['message' => 'No enrollments found for this course.']);
            }

            return response()->json([
                'message' => 'Enrollments retrieved successfully',
                'data' => EnrollmentResource::collection($enrollments)
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching enrollments.'], 500);
        }
    }

    // Get all enrollments for a student
    public function getStudentEnrollments(Request $request)
    {
        $userId = $request->user()->id;

        $enrollments = Enrollment::where('user_id', $userId)
            ->with('course')
            ->paginate(10);

        if ($enrollments->isEmpty()) {
            return response()->json(['message' => 'No course enrollments found for this user.']);
        }

        return response()->json([
            'message' => 'User enrollments retrieved successfully',
            'data' => EnrollmentResource::collection($enrollments)
        ]);
    }

    // Mark a course as completed
    public function markAsCompleted(Enrollment $enrollment)
    {
        try {
            $this->authorize('update', $enrollment);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorised Action'], 403);
        }      

        $enrollment->update([
            'completion_date' => now()
        ]);

        return response()->json([
            'message' => 'Enrollment marked as completed successfully',
            'data' => new EnrollmentResource($enrollment)
        ]);
    }
}

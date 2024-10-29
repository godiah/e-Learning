<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstructorCourseResource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Courses;
use Illuminate\Http\Request;

class InstructorCourseController extends Controller
{
    // Get all courses where user is instructor    
    public function getCourses(Request $request)
    {
        $user = $request->user();

        // Ensure the user is an instructor
        if (!$user->is_instructor) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $courses = Courses::where('instructor_id', $user->id)          
            ->paginate(10);

        return response()->json([
            'message' => 'Instructor courses retrieved successfully',
            'data' => InstructorCourseResource::collection($courses)
        ]);
    }

    // Get assignments for a specific course
    public function getCourseAssignments(Request $request, Courses $course)
    {
        $user = $request->user();

        if ($course->instructor_id !== $user->id)
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $assignments = Assignment::whereIn('lesson_id', $course->lessons()->pluck('id'))->get();

        return response()->json([
            'message' => 'Course assignments retrieved successfully',
            'data' => $assignments
        ]);
    }

    // Get assignment submissions for a given assignment
    public function getSubmissionsForAssignment(Request $request, Assignment $assignment)
    {
        $user = request()->user();

        if ($assignment->instructor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $submissions = AssignmentSubmission::where('assignment_id', $assignment->id)->get();

        $gradedSubmissions = $submissions->whereNotNull('grade');
        $ungradedSubmissions = $submissions->whereNull('grade');

        return response()->json([
            'message' => 'Submissions retrieved successfully',
            'data' => [
                'graded' => $gradedSubmissions,
                'ungraded' => $ungradedSubmissions
            ]
        ]);
    }

    //
}

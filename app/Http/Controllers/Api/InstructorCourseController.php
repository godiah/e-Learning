<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstructorCourseResource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Courses;
use App\Models\Quiz;
use App\Models\QuizAttempt;
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

    // Get quizzes for a specific course
    public function getCourseQuizzes(Request $request, Courses $course)
    {
        $user = $request->user();

        if ($course->instructor_id !== $user->id)
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $quizzes = Quiz::whereIn('lesson_id', $course->lessons()->pluck('id'))->get();

        return response()->json([
            'message' => 'Course quizzes retrieved successfully',
            'data' => $quizzes
        ]);
    }

    public function getQuizAnalytics(Request $request, Courses $course, Quiz $quiz)
    {
        $user = $request->user();

        if ($course->instructor_id !== $user->id)
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

       $quizAttempts = QuizAttempt::whereHas('quiz', function ($q) use ($quiz) {
            $q->where('id', $quiz->id);
        })->with('user')->get();

        $studentBestScores = $quizAttempts->groupBy('user_id')->map(function ($attempts, $userId) {
            return $attempts->max('score');
        })->values()->toArray();

        $quizAnalytics = [
            'quiz_id' => $quiz->id,
            'quiz_name' => $quiz->name,
            'total_attempts' => $quizAttempts->count(),
            'average_score' => array_sum($studentBestScores) / count($studentBestScores),
            'highest_score' => max($studentBestScores),
            'lowest_score' => min($studentBestScores),
            'student_details' => $quizAttempts->groupBy('user_id')->map(function ($attempts, $userId) {
                $user = $attempts->first()->user;
                return [
                    'student_id' => $user->id,
                    'student_name' => $user->name,
                    'num_attempts' => $attempts->count(),
                    'highest_score' => $attempts->max('score')
                ];
            })
        ];

        return response()->json([
            'message' => 'Quiz analytics retrieved successfully',
            'data' => $quizAnalytics
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentSubmissionResource;
use App\Http\Resources\CourseProgressResource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Courses;
use App\Models\Enrollment;
use App\Services\CourseProgressService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AssignmentSubmissionController extends Controller
{
    use AuthorizesRequests;

    // Submit an assignment
    public function submit(Request $request, Assignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'submission_text' => 'sometimes|nullable|string',
            'submission_file' => 'sometimes|nullable|file|mimes:pdf,doc,docx,txt,ppt,pptx|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        // Check if user is enrolled in the course associated with the assignment
        $courseId = $assignment->lesson->course_id;
        $isEnrolled = Enrollment::where('course_id', $courseId)
                                ->where('user_id', $request->user()->id)
                                ->exists();

        if (!$isEnrolled) {
            return response()->json(['message' => 'You must be enrolled in the course to submit this assignment'], 403);
        }

        $existingSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
                                          ->where('user_id', $request->user()->id)
                                          ->first();

        if ($existingSubmission && !$existingSubmission->is_resubmission_allowed) {
            return response()->json(['message' => 'Assignment already submitted and resubmissions are not allowed'], 400);
        }

        $submissionData = [
            'assignment_id' => $assignment->id,
            'user_id' => $request->user()->id,
            'submission_text' => $request->submission_text,
        ];

        if ($request->hasFile('submission_file')) {
            if ($existingSubmission && $existingSubmission->submission_file_path) {
                Storage::disk('public')->delete($existingSubmission->submission_file_path);
            }
            $submissionData['submission_file_path'] = $request->file('submission_file')->store('submissions-assignments','public');
        }

        if ($existingSubmission) {
            // Update the existing submission
             $submissionData['is_resubmission_allowed'] = false;
            $existingSubmission->update($submissionData);
            $existingSubmission->increment('resubmission_count');
        } else {
            // Create a new submission
            $submissionData['submission_date'] = now();
            $submissionData['resubmission_count'] = 0;
            $existingSubmission = AssignmentSubmission::create($submissionData);
        }

        if ($existingSubmission) {
        $course = $assignment->lesson->course;
        $existingSubmission->progress_status = $existingSubmission->grade >= $course->pass_mark ? 'passed' : 'failed';
        $existingSubmission->save();

        // Calculate course progress
        $progressService = new CourseProgressService();
        $progress = $progressService->calculateProgress($course->id, $request->user()->id);

        return response()->json([
            'message' => 'Assignment submitted successfully',
            'data' => new AssignmentSubmissionResource($existingSubmission),
            'progress_status' => $existingSubmission->progress_status,
            'course_progress' => new CourseProgressResource($progress)
        ]);
    }
    }

    // Allow resubmission (INSTRUCTOR ONLY)
    public function allowResubmission(Request $request, Assignment $assignment, AssignmentSubmission $submission)
    {
        $user = $request->user();

        // Check if the user is the instructor of the assignment
        if ($user->id !== $assignment->instructor_id) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        // Ensure submission belongs to the assignment
        if ($submission->assignment_id !== $assignment->id) {
            return response()->json(['message' => 'Submission does not belong to this assignment'], 404);
        }

        // Allow resubmission
        $submission->update(['is_resubmission_allowed' => true]);

        return response()->json(['message' => 'Resubmission allowed for the student']);
    }

    // Grade assignment submission (INSTRUCTOR ONLY)
    public function grade(Request $request, AssignmentSubmission $submission)
    {
        try {
            $this->authorize('grade', $submission);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }
        

        $validator = Validator::make($request->all(), [
            'grade' => 'sometimes|nullable|numeric|min:0|max:100',
            'feedback' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
            //'is_resubmission_allowed' => false,
        ]);

        $submission->load(['assignment', 'user']);

        return response()->json([
            'message' => 'Assignment graded successfully', 
            'data' => new AssignmentSubmissionResource($submission)
        ]);
    }

    // View assignment submission for given course
    public function viewSubmission(Request $request, Courses $course, Assignment $assignment)
    {
        $user = $request->user();

        $enrollment = Enrollment::where('course_id', $course->id)
                                ->where('user_id', $user->id)
                                ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        // Fetch the assignment submission for the given assignment and student
        $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                                          ->where('user_id', $user->id)
                                          ->first();

        if (!$submission) {
            return response()->json(['message' => 'No submission found for this assignment'], 404);
        }

        return response()->json([
            'message' => 'Assignment submission retrieved successfully',
            'data' => new AssignmentSubmissionResource($submission),
        ]);
    }

}

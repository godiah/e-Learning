<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\Lessons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    // Display Assignments for a given lesson
    public function index(Lessons $lesson)
    {
        $assignments = $lesson->assignments()->get();

        if($assignments->isNotEmpty())
        {
            return AssignmentResource::collection($assignments);
        }
        else
        {
            return response()->json(['message' => 'No available assignments for this lesson found'], 404);
        }        
    }

    // Display a single assignment of a lesson
    public function show(Lessons $lesson, Assignment $assignment)
    {
        if($assignment->lesson_id !== $lesson->id)
        {
            return response()->json(['message' => 'Assignment does not exist in this lesson'], 404);
        }

        return new AssignmentResource($assignment);
    }

    // Add a new assignment to a lesson
    public function store(Request $request, Lessons $lesson)
    {
        $user = request()->user();
    
        if (!$user->is_instructor || $lesson->course->instructor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assignments')->where(function ($query) use ($lesson) {
                    return $query->where('lesson_id', $lesson->id);
                })
            ],
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $validator->messages(),
            ], 422);
        }

        $assignment = $lesson->assignments()->create([
            'title' => $request->title,
            'description' => $request->description,
            'instructor_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Assignment created successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    // Update an assignment
    public function update(Request $request, Lessons $lesson, Assignment $assignment)
    {
        $user = request()->user();
    
        if (!$user->is_instructor) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        if ($assignment->instructor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        if ($assignment->lesson_id !== $lesson->id) 
        {
            return response()->json(['error' => 'Assignment not found in this lesson'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('assignments')->where(function ($query) use ($lesson) {
                    return $query->where('lesson_id', $lesson->id);
                })->ignore($assignment->id), 
            ],
            'description' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $assignment->update($request->only(['title', 'description']));

        return response()->json([
            'message' => 'Assignment updated successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    public function destroy(Lessons $lesson, Assignment $assignment)
    {
        $user = request()->user();
    
        if (!$user->is_instructor) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        if ($assignment->instructor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        if ($assignment->lesson_id !== $lesson->id) 
        {
            return response()->json(['error' => 'Assignment not found in this lesson'], 404);
        }

        $assignment->delete();

        return response()->json(['message' => 'Assignment deleted successfully']);
    }
}

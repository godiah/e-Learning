<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssignmentController extends Controller
{
    public function index()
    {
        $assignments = Assignment::get();
        if($assignments->count() > 0)
        {
            return AssignmentResource::collection($assignments);
        }
        else
        {
            return response()->json(['message' => 'No available assignments found'], 404);
        }        
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required|exists:lessons,id',
            'title' => 'required|string|max:255|unique:App\Models\Assignment,title',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $assignment = Assignment::create($request->all());

        return response()->json([
            'message' => 'Assignment created successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    public function show(Assignment $assignment)
    {
        return new AssignmentResource($assignment);
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'exists:lessons,id',
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $assignment->update($request->all());

        return response()->json([
            'message' => 'Assignment updated successfully',
            'data' => new AssignmentResource($assignment)
        ]);
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return response()->json(['message' => 'Assignment deleted successfully']);
    }
}

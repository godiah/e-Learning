<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAssignment;
use App\Http\Resources\UserAssignmentResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class UserAssignmentController extends Controller
{
    public function index()
    {
        $userAssignments = UserAssignment::get();
        if($userAssignments->count() > 0)
        {
            return UserAssignmentResource::collection($userAssignments);
        }
        else
        {
            return response()->json(['message' => 'No available assignments found'], 404);
        }        
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'assignment_id' => 'required|exists:assignments,id',
            'submission' => 'required|string',
            'submitted_at' => 'required|date',
            'grade' => 'nullable|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $userAssignment = UserAssignment::create($request->all());

        return response()->json([
            'message' => 'User assignment created successfully',
            'data' => new UserAssignmentResource($userAssignment)
        ]);
    }

    public function update(Request $request, UserAssignment $userAssignment)
    {
        $validator = Validator::make($request->all(), [
            'submission' => 'nullable|string',
            'submitted_at' => 'nullable|date',
            'grade' => 'required|min:0|max:100',
            'feedback' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $userAssignment->update($request->all());

        return response()->json([
            'message' => 'User assignment updated successfully',
            'data' => new UserAssignmentResource($userAssignment)
        ]);
    }

    public function destroy(UserAssignment $userAssignment)
    {
        $userAssignment->delete();
        return response()->json(['message' => 'User assignment deleted successfully']);
    }
}

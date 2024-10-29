<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use Illuminate\Http\Request;
use App\Http\Resources\DiscussionResource;
use Illuminate\Support\Facades\Validator;

class DiscussionController extends Controller
{
    public function index($courseId)
    {
        // Fetch all discussions for a specific course
        $discussions = Discussion::where('course_id', $courseId)->with('user')->get();
        return DiscussionResource::collection($discussions);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $discussion = Discussion::create($request->all());

        return response()->json([
            'message' => 'Discussion started successfully',
            'data' => new DiscussionResource($discussion)
        ]);
    }

    public function update(Request $request, Discussion $discussion)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $discussion->update($request->all());

        return response()->json([
            'message' => 'Discussion updated successfully',
            'data' => new DiscussionResource($discussion)
        ]);
    }

    public function destroy(Discussion $discussion)
    {
        $discussion->delete();
        return response()->json(['message' => 'Discussion deleted successfully']);
    }
}

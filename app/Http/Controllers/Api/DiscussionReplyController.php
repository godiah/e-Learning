<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscussionReply;
use Illuminate\Http\Request;
use App\Http\Resources\DiscussionReplyResource;
use Illuminate\Support\Facades\Validator;

class DiscussionReplyController extends Controller
{
    public function index($discussionId)
    {
        // Fetch all replies for a specific discussion
        $replies = DiscussionReply::where('discussion_id', $discussionId)->with('user')->get();
        return DiscussionReplyResource::collection($replies);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'discussion_id' => 'required|exists:discussions,id',
            'user_id' => 'required|exists:users,id',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $reply = DiscussionReply::create($request->all());

        return response()->json([
            'message' => 'Reply added successfully',
            'data' => new DiscussionReplyResource($reply)
        ]);
    }

    public function update(Request $request, DiscussionReply $discussionReply)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $discussionReply->update($request->all());

        return response()->json([
            'message' => 'Reply updated successfully',
            'data' => new DiscussionReplyResource($discussionReply)
        ]);
    }

    public function destroy(DiscussionReply $discussionReply)
    {
        $discussionReply->delete();
        return response()->json(['message' => 'Reply deleted successfully']);
    }
}

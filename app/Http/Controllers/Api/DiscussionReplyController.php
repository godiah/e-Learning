<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscussionReply;
use Illuminate\Http\Request;
use App\Http\Resources\DiscussionReplyResource;
use App\Models\Discussion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;

class DiscussionReplyController extends Controller
{
    use AuthorizesRequests;

    public function index(Discussion $discussion)
    {
        $replies = $discussion->replies()
            ->latest()
            ->paginate(20);

        if ($replies->isEmpty()) {
            return response()->json([
                'message' => 'No present replies found for this course'
            ]);
        }

        return DiscussionReplyResource::collection($replies);
    }

    public function store(Request $request, Discussion $discussion)
    {
        try {
           $this->authorize('create', [DiscussionReply::class, $discussion]);
        }catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized Action.'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $reply = $discussion->replies()->create($validated);

        return new DiscussionReplyResource($reply);
    }

    public function update(Request $request, DiscussionReply $discussionReply)
    {
        try {
            $this->authorize('update', $discussionReply);
        }catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized Action.'], 403);
        }

        $validated = $request->validate([
            'content' => 'sometimes|string',
        ]);

        $discussionReply->update($validated);

        return new DiscussionReplyResource($discussionReply);
    }

    public function destroy(DiscussionReply $discussionReply)
    {
        try {
            $this->authorize('delete', $discussionReply);
        }catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized Action.'], 403);
        }

        $discussionReply->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}

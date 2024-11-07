<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use Illuminate\Http\Request;
use App\Http\Resources\DiscussionResource;
use App\Models\Courses;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DiscussionController extends Controller
{
    use AuthorizesRequests;

    public function index(Courses $course)
    {
        try {
            $this->authorize('viewAny', [Discussion::class, $course]);
        }catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized Action.'], 403);
        }

        $discussions = $course->discussion()
            ->withCount('replies')
            ->with(['replies' => function ($query) {
                $query->latest()->take(3); 
            }, 'replies.user'])
            ->latest()
            ->paginate(15);

        if ($discussions->isEmpty()) {
            return response()->json([
                'message' => 'No present discussions found for this course'
            ]);
        }

        return DiscussionResource::collection($discussions);
    }

    public function show(Courses $course, Discussion $discussion)
    {
        $discussion = $course->discussion()->where('id', $discussion->id)
                            ->with(['replies' => function ($query) {
                                $query->latest();
                            }, 'replies.user'])
                            ->firstOrFail();

        return new DiscussionResource($discussion);
    }

    public function store(Request $request, Courses $course)
    {
        try {
            $this->authorize('create', [Discussion::class, $course]);
        }catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized Action.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $discussion = $course->discussion()->create($validated);

        return new DiscussionResource($discussion);
    }

    public function update(Request $request, Courses $course, Discussion $discussion)
    {
        try {
            $this->authorize('update', $discussion);
        }catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized Action.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        $discussion->update($validated);

        return new DiscussionResource($discussion);
    }

    public function destroy(Discussion $discussion)
    {
        try {
            $this->authorize('delete', $discussion);
        }catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized Action.'], 403);
        }

        $discussion->delete();
        return response()->json(['message' => 'Deleted successfully'], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    // Create Activity
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $activity = Activity::create($request->only(['name', 'type']));

        return response()->json([
            'message' => 'Activity created successfully',
            'data' => new ActivityResource($activity)
        ], 201);
    }

    // Fetch Activities
    public function index()
    {
        $activities = Activity::paginate(10);

        if ($activities->count() > 0) {
            return ActivityResource::collection($activities);
        } else {
            return response()->json(['message' => 'No activities found.'], 404);
        }
    }

    // Fetch single activity
    public function show(Activity $activity)
    {
        return new ActivityResource($activity);
    }
}

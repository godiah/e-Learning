<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonsResource;
use App\Models\Lessons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LessonsController extends Controller
{
    //Display  all lessons
    public function index()
    {
        $lesson = Lessons::get();
        if($lesson->count() > 0)
        {
            return LessonsResource::collection($lesson);
        }
        else
        {
            return response()->json(['message' => 'No available lessons found'], 404);
        }
    }

    //Add a new lesson
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'course_id' => 'required|exists:courses,id',  
            'title' => 'required|string|max:255',         
            'content' => 'nullable|string',              
            'video_url' => 'nullable|url',             
            'order_index' => [
                'required',
                'integer',
                'min:1',                                  
                function ($attribute, $value, $fail) use ($request) {
                    // Ensure order_index is unique within the same course
                    $exists = Lessons::where('course_id', $request->course_id)
                                    ->where('order_index', $value)
                                    ->exists();
                    if ($exists) {
                        $fail("The $attribute must be unique within the same course.");
                    }
                },
            ],
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $lesson = Lessons::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'content' => $request->content,
            'video_url' => $request->video_url,
            'order_index' => $request->order_index,
        ]);

        return response()->json([
            'message' => 'Lesson created successfully',
            'data' => new LessonsResource($lesson)
        ]);
    }

    //Display a lesson
    public function show(Lessons $lesson)
    {
        return new LessonsResource($lesson);
    }

    //Update a lesson
    public function update(Request $request, Lessons $lesson)
    {
        $validator = Validator::make($request->all(),[
            'course_id' => 'exists:courses,id',  
            'title' => 'string|max:255',         
            'content' => 'nullable|string',              
            'video_url' => 'nullable|url',             
            'order_index' => [                
                'integer',
                'min:1',                                  
                function ($attribute, $value, $fail) use ($request) {
                    // Ensure order_index is unique within the same course
                    $exists = Lessons::where('course_id', $request->course_id)
                                    ->where('order_index', $value)
                                    ->exists();
                    if ($exists) {
                        $fail("The $attribute must be unique within the same course.");
                    }
                },
            ],
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $lesson->update([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'content' => $request->content,
            'video_url' => $request->video_url,
            'order_index' => $request->order_index,
        ]);

        return response()->json([
            'message' => 'Lesson updated successfully',
            'data' => new LessonsResource($lesson)
        ]);

    }

    //Delete a lesson
    public function destroy(Lessons $lesson)
    {
        $lesson->delete();
        return response()->json([
            'message' => 'Lesson deleted successfully'
        ]);
    }
}

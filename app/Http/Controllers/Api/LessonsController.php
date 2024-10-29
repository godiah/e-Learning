<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonsResource;
use App\Models\Courses;
use App\Models\Lessons;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
class LessonsController extends Controller
{
    use AuthorizesRequests;

    //Display  all lessons
    public function index(Courses $course)
    {
        $lessons = $course->lessons()->orderBy('order_index')->get();
        if($lessons->count() > 0 )
        {
            return LessonsResource::collection($lessons);
        }
        else
        {
            return response()->json([
                'message' => 'No lessons added.',                
            ], 404);
        }
    }

    //Add a new lesson
    public function store(Request $request, Courses $course)
    {
        $validator = Validator::make($request->all(),[  
            'title' => 'required|string|max:255',         
            'content' => 'nullable|string',              
            'video' => 'nullable|file|mimes:mp4,mov,avi|max:102400',             
            'order_index' => ['required','integer','min:1',                                  
                Rule::unique('lessons')->where(function ($query) use ($course) {
                    return $query->where('course_id', $course->id);
                })
            ],
            'video_duration' => 'nullable|integer|min:1',            
            'resource' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx|max:10240',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validatedData = $validator->validated();

        // Handle video upload
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('lesson-videos', 'public');
            $validatedData['video_url'] = $videoPath;
        }

        // Handle resource upload
        if ($request->hasFile('resource')) {
            $resourcePath = $request->file('resource')->store('lesson-resources', 'public');
            $validatedData['resource_path'] = $resourcePath;
        }

        // Use a database transaction to ensure atomicity
        try {
            DB::beginTransaction();

            // Double-check order_index uniqueness
            $existingLesson = Lessons::where('course_id', $course->id)
                                    ->where('order_index', $validatedData['order_index'])
                                    ->lockForUpdate()
                                    ->first();

            if ($existingLesson) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['order_index' => ['The order index must be unique within the same course.']]
                ], 422);
            }

        $lesson = $course->lessons()->create(array_merge(
            $validatedData,
            ['instructor_id' => $request->user()->id]
        ));

        DB::commit();

        return new LessonsResource($lesson);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while creating the lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Display a single lesson
    public function show(Courses $course, Lessons $lesson)
    {
        if ($lesson->course_id !== $course->id) 
        {
            return response()->json(['error' => 'Lesson not found in this course'],404);
        }

        return new LessonsResource($lesson);
    }

    // Update a lesson
    public function update(Request $request, Courses $course, Lessons $lesson)
    {
        if ($lesson->course_id !== $course->id) {
            return response()->json(['error' => 'Lesson not found in this course'], 404);
        }
    
        $this->authorize('update', [$lesson, $course]);

        // Validate basic lesson details
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'order_index' => 'sometimes|integer',
            'video' => 'sometimes|mimetypes:video/mp4,video/avi,video/mpeg|max:102400', // 100MB max
            'resource' => 'sometimes|mimes:pdf,doc,docx,ppt,pptx,zip|max:10240', // 10MB max
            'video_duration' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Handle video upload if present
            if ($request->hasFile('video')) {
                if ($lesson->video_url) {
                    Storage::disk('public')->delete($lesson->video_url);
                }
                $videoPath = $request->file('video')->store('lesson-videos', 'public');
                $lesson->video_url = $videoPath;
            }

            // Handle resource upload if present
            if ($request->hasFile('resource')) {
                if ($lesson->resource_path) {
                    Storage::disk('public')->delete($lesson->resource_path);
                }
                $resourcePath = $request->file('resource')->store('lesson-resources', 'public');
                $lesson->resource_path = $resourcePath;
            }

            // Update other lesson details
            $fieldsToUpdate = $request->only(['title', 'content', 'order_index', 'video_duration']);
            $lesson->fill($fieldsToUpdate);

            $lesson->save();

            DB::commit();

            return new LessonsResource($lesson);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Delete a lesson
    public function destroy(Courses $course, Lessons $lesson)
    {
        try {
            $this->authorize('delete', [$lesson, $course]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorised Access'], 403);
        }

        DB::beginTransaction();

        try {
            if ($lesson->video_url) {
                Storage::disk('public')->delete($lesson->video_url);
            }

            if ($lesson->resource_path) {
                Storage::disk('public')->delete($lesson->resource_path);
            }

            $lesson->delete();

            DB::commit();

            return response()->json([
                'message' => 'Lesson and associated files deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while deleting the lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

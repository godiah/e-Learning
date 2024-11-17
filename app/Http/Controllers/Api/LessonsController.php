<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonsResource;
use App\Models\Courses;
use App\Models\Lessons;
use App\Models\LessonSubcontent;
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

    /**
     * Display a listing of lessons for a specific course.
     */
    public function index(Request $request, Courses $course)
    {
        try {
            $lessons = $course->lessons()
                ->with(['subcontents' => function ($query) {
                    $query->orderBy('order_index', 'asc');
                }])
                ->orderBy('order_index', 'asc')
                ->paginate(10);

            return LessonsResource::collection($lessons);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching lessons',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * Display a specific lesson and its subcontents.
     */
    public function show(Request $request, Courses $course, Lessons $lesson)
    {
        try {
            if ($lesson->course_id !== $course->id) {
                return response()->json([
                    'message' => 'This lesson does not belong to the specified course.'
                ], 404);
            }

            // Load the lesson with its subcontents
            $lesson->load(['subcontents' => function ($query) {
                $query->orderBy('order_index', 'asc');
            }]);

            return new LessonsResource($lesson);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new lesson with its sub  contents
     */
    public function store(Request $request, Courses $course)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:App\Models\Lessons,title',
            'content' => 'nullable|string',
            'subcontents' => 'required|array|min:1',
            'subcontents.*.name' => 'required|string|max:255',
            'subcontents.*.description' => 'nullable|string',
            'subcontents.*.video' => 'nullable|file|mimes:mp4,mov,avi|max:102400',
            'subcontents.*.video_duration' => 'nullable|integer|min:1',
            'subcontents.*.resource' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            DB::beginTransaction();

            // Create the main lesson
            $lesson = $course->lessons()->create([
                'title' => $request->title,
                'content' => $request->content,
                'instructor_id' => $request->user()->id,
                'total_watch_time' => 0
            ]);

            // Process subcontents
            $totalWatchTime = 0;
            foreach ($request->subcontents as $index => $subcontentData) {
                // Get the next order index
                $nextOrderIndex = $lesson->subcontents()->max('order_index') + 1;

                $subcontent = new LessonSubcontent([
                    'name' => $subcontentData['name'],
                    'description' => $subcontentData['description'] ?? null,
                    'order_index' => $nextOrderIndex,
                    'video_duration' => $subcontentData['video_duration'] ?? 0
                ]);

                // Handle video upload
                if (isset($request->file('subcontents')[$index]['video'])) {
                    $videoFile = $request->file('subcontents')[$index]['video'];
                    $videoPath = $videoFile->store('lesson-videos', 'public');
                    $subcontent->video_url = $videoPath;
                }

                // Handle resource upload
                if (isset($request->file('subcontents')[$index]['resource'])) {
                    $resourceFile = $request->file('subcontents')[$index]['resource'];
                    $resourcePath = $resourceFile->store('lesson-resources', 'public');
                    $subcontent->resource_path = $resourcePath;
                }

                $lesson->subcontents()->save($subcontent);
                $totalWatchTime += $subcontentData['video_duration'] ?? 0;
            }

            // Update total watch time
            $lesson->update(['total_watch_time' => $totalWatchTime]);

            DB::commit();
            return new LessonsResource($lesson->load('subcontents'));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while creating the lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a lesson with its sub contents
     */
    public function update(Request $request, Courses $course, Lessons $lesson)
{
    $validator = Validator::make($request->all(), [
        'title' => 'sometimes|string|max:255',
        'content' => 'nullable|string',
        // Validate subcontents array if provided
        'subcontents' => 'sometimes|array',
        'subcontents.*.id' => 'sometimes|exists:lesson_subcontents,id',
        'subcontents.*.name' => 'required_without:subcontents.*.id|string|max:255',
        'subcontents.*.description' => 'nullable|string',
        'subcontents.*.video' => 'nullable|file|mimes:mp4,mov,avi|max:102400',
        'subcontents.*.video_duration' => 'nullable|integer|min:1',
        'subcontents.*.resource' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx|max:10240',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    try {
        DB::beginTransaction();

        // Update main lesson
        if ($request->has('title')) {
            $lesson->title = $request->title;
        }
        if ($request->has('content')) {
            $lesson->content = $request->content;
        }
        $lesson->save();

        // Update or create subcontents if provided
        if ($request->has('subcontents')) {
            $existingSubcontentIds = $lesson->subcontents->pluck('id')->toArray();
            $updatedSubcontentIds = [];

            foreach ($request->subcontents as $index => $subcontentData) {
                // If subcontent has ID, update existing
                if (isset($subcontentData['id'])) {
                    $subcontent = LessonSubcontent::find($subcontentData['id']);

                    if ($subcontent && $subcontent->lesson_id == $lesson->id) {
                        $updatedSubcontentIds[] = $subcontent->id;

                        // Update basic fields
                        if (isset($subcontentData['name'])) {
                            $subcontent->name = $subcontentData['name'];
                        }
                        if (isset($subcontentData['description'])) {
                            $subcontent->description = $subcontentData['description'];
                        }
                        if (isset($subcontentData['video_duration'])) {
                            $subcontent->video_duration = $subcontentData['video_duration'];
                        }

                        // Handle video update
                        if (isset($request->file('subcontents')[$index]['video'])) {
                            // Delete old video if exists
                            if ($subcontent->video_url) {
                                Storage::disk('public')->delete($subcontent->video_url);
                            }
                            $videoFile = $request->file('subcontents')[$index]['video'];
                            $videoPath = $videoFile->store('lesson-videos', 'public');
                            $subcontent->video_url = $videoPath;
                        }

                        // Handle resource update
                        if (isset($request->file('subcontents')[$index]['resource'])) {
                            // Delete old resource if exists
                            if ($subcontent->resource_path) {
                                Storage::disk('public')->delete($subcontent->resource_path);
                            }
                            $resourceFile = $request->file('subcontents')[$index]['resource'];
                            $resourcePath = $resourceFile->store('lesson-resources', 'public');
                            $subcontent->resource_path = $resourcePath;
                        }

                        $subcontent->save();
                    }
                } else {
                    // Create new subcontent
                    $nextOrderIndex = $lesson->subcontents()->max('order_index') + 1;
                    
                    $subcontent = new LessonSubcontent([
                        'name' => $subcontentData['name'],
                        'description' => $subcontentData['description'] ?? null,
                        'order_index' => $nextOrderIndex,
                        'video_duration' => $subcontentData['video_duration'] ?? 0
                    ]);

                    // Handle new video upload
                    if (isset($request->file('subcontents')[$index]['video'])) {
                        $videoFile = $request->file('subcontents')[$index]['video'];
                        $videoPath = $videoFile->store('lesson-videos', 'public');
                        $subcontent->video_url = $videoPath;
                    }

                    // Handle new resource upload
                    if (isset($request->file('subcontents')[$index]['resource'])) {
                        $resourceFile = $request->file('subcontents')[$index]['resource'];
                        $resourcePath = $resourceFile->store('lesson-resources', 'public');
                        $subcontent->resource_path = $resourcePath;
                    }

                    $lesson->subcontents()->save($subcontent);
                    $updatedSubcontentIds[] = $subcontent->id;
                }
            }

            $this->calculateTotalWatchTime($lesson);
        }

        DB::commit();
        return new LessonsResource($lesson->load('subcontents'));

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'An error occurred while updating the lesson',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Delete an entire lesson and all its subcontents.
     */
    public function destroy(Request $request, Courses $course, Lessons $lesson)
    {
        try {
            $this->authorize('delete', [$lesson, $course]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorised Access'], 403);
        }

        try {
            // Verify the lesson belongs to the course
            if ($lesson->course_id !== $course->id) {
                return response()->json([
                    'message' => 'This lesson does not belong to the specified course.'
                ], 404);
            }

            DB::beginTransaction();

            foreach ($lesson->subcontents as $subcontent) {
                $this->deleteFiles($subcontent);
            }

            $lesson->delete();

            DB::commit();

            return response()->json([
                'message' => 'Lesson and all associated content successfully deleted'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while deleting the lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * Delete a specific subcontent from a lesson.
     */
    public function destroySubcontent(Request $request, Courses $course, Lessons $lesson, LessonSubcontent $subcontent)
    {
        // try {
        //     $this->authorize('delete', [$lesson, $course]);
        // } catch (AuthorizationException $e) {
        //     return response()->json(['message' => 'Unauthorised Access'], 403);
        // }

        try {
            //$this->authorize('delete', [$lesson, $course]);
            // Verify the lesson belongs to the course and subcontent belongs to the lesson
            if ($lesson->course_id !== $course->id || $subcontent->lesson_id !== $lesson->id) {
                return response()->json([
                    'message' => 'Invalid Request.'
                ], 404);
            }

            DB::beginTransaction();

            // Delete associated files
            $this->deleteFiles($subcontent);

            // Delete the subcontent
            $subcontent->delete();

            // // Reorder remaining subcontents to maintain sequential order
            // $lesson->subcontents()
            //     ->where('order_index', '>', $subcontent->order_index)
            //     ->decrement('order_index');

            // Update total watch time
            $this->updateTotalWatchTime($lesson);

            DB::commit();

            return response()->json([
                'message' => 'Subcontent successfully deleted',
                'updated_lesson' => new LessonsResource($lesson->load('subcontents'))
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while deleting the subcontent',
                'error' => $e->getMessage()
            ], 500);
        }
    }


     /**
      * Recalculate total watch time for an entire lesson
      */
    public function updateTotalWatchTime(Lessons $lesson)
    {
        $totalWatchTime = $lesson->subcontents()->sum('video_duration');
        $lesson->update(['total_watch_time' => $totalWatchTime]);
        return $totalWatchTime;
    }

    /**
     * Calculate total watch time for an entire lesson
     */
    protected function calculateTotalWatchTime($lesson)
    {
        $totalWatchTime = $lesson->subcontents()->sum('video_duration');
        $lesson->total_watch_time = $totalWatchTime;
        $lesson->save();
    }

    /**
     * Helper method to delete files associated with a subcontent
     */
    private function deleteFiles($subcontent)
    {
        if ($subcontent->video_url) {
            Storage::disk('public')->delete($subcontent->video_url);
        }
        if ($subcontent->resource_path) {
            Storage::disk('public')->delete($subcontent->resource_path);
        }
    }
}

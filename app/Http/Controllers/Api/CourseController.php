<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CoursesResource;
use App\Models\Courses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    //Display all available courses
    public function index()
    {
        $courses = Courses::get();
        if($courses->count() > 0)
        {
            return CoursesResource::collection($courses);
        }
        else
        {
            return response()->json(['message' => 'No courses found'], 404);
        }
    }

    //Add a new course
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'title' => 'required|string|max:255|unique:App\Models\Courses,title',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'level' => 'required|in:beginner,intermediate,advanced',
            'category_id' => 'required|exists:categories,id',  // Ensures category exists
            'instructor_id' => 'required|exists:users,id',      // Ensures instructor exists
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $courses = Courses::create([
            'title' => $request['title'],
            'description' => $request['description'],
            'price' => $request['price'],
            'level' => $request['level'],
            'category_id' => $request['category_id'],
            'instructor_id' => $request['instructor_id'],
        ]);

        return response()->json([
            'message' => 'Course created successfully',
            'data' => new CoursesResource($courses)
        ]);
    }

    //Display a course
    public function show(Courses $course)
    {
        return new CoursesResource($course);
    }

    //Update a course
    public function update(Request $request, Courses $course)
    {
        $validator = Validator::make($request->all(),[
            'title' => 'string|max:255',
            'description' => 'string',
            'price' => 'numeric',
            'level' => 'in:beginner,intermediate,advanced',
            'category_id' => 'exists:categories,id',  // Ensures category exists
            'instructor_id' => 'exists:users,id',      // Ensures instructor exists
        ]);

        if($validator->fails())
        {
            return response()->json([
                'error' => $validator->messages(),
            ], 422);
        }

        $course->update([
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'level' => $request->level,
            'category_id' => $request->category_id,
            'instructor_id' => $request->instructor_id,
        ]);

        return response()->json([
            'message' => 'Course updated successfully',
            'data' => new CoursesResource($course)
        ]);
    }

    //Delete a course
    public function destroy(Courses $course)
    {
        $course->delete();
        return response()->json([
            'message' => 'Course deleted successfully'
        ]);
    }
}

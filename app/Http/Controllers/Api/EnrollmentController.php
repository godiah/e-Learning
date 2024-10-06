<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnrollmentController extends Controller
{
    //Display all enrolled students
    public function index()
    {
        $enrollment = Enrollment::get();
        if($enrollment->count() > 0)
        {
            return EnrollmentResource::collection($enrollment);
        }
        else
        {
            return response()->json(['message' => 'No students currently enrolled found'], 404);
        }
    }

    //Add enrollment
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,id', // Ensure the user exists
            'course_id' => 'required|exists:courses,id', // Ensure the course exists
            'enrollment_date' => 'nullable|date',
            'completion_date' => 'nullable|date',

            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request) {
                    if (Enrollment::where('user_id', $value)
                        ->where('course_id', $request->input('course_id'))
                        ->exists()) {
                        $fail('The user is already enrolled in this course.');
                    }
                },
            ]            
        ]);

        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        // Create a new enrollment
        $enrollment = Enrollment::create([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'enrollment_date' => $request->enrollment_date,
            'completion_date' => $request->completion_date
        ]);

        // Return a response with the created enrollment
        return response()->json([
            'message' => 'You have enrolled successfully',
            'data' => new EnrollmentResource($enrollment)
        ]);
    }

    //Display an enrollment(s)
    public function show(Enrollment $enrollment)
    {
        return new EnrollmentResource($enrollment);
    }

    //Update an enrollment
    public function update(Request $request, Enrollment $enrollment)
    {
        $validator = Validator::make($request->all(),[
            'user_id' => 'exists:users,id', // Ensure the user exists
            'course_id' => 'exists:courses,id', // Ensure the course exists
            'enrollment_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'error' => $validator->messages(),
            ], 422);
        }

        $enrollment->update([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'enrollment_date' => $request->enrollment_date,
            'completion_date' => $request->completion_date,
        ]);

        return response()->json([
            'message' => 'Enrollment updated successfully',
            'data' => new EnrollmentResource($enrollment)
        ]);
    }

    //Delete an Enrollment(s)
    public function destroy(Enrollment $enrollment)
    {
        $enrollment->delete();
        return response()->json([
            'message' => 'Enrollment deleted successfully'
        ]);
    }
}

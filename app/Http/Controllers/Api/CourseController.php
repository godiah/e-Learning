<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseApprovalResource;
use App\Http\Resources\CoursesResource;
use App\Mail\CourseApprovalMail;
use App\Mail\CourseSubmittedForApproval;
use App\Models\Courses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    use AuthorizesRequests;

    //Display all approved available courses
    public function index()
    {
        $courses = Courses::where('status','approved')->get();
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
        try {
            $this->authorize('create', Courses::class);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'You are not authorized to create a course. Only instructors can create courses'], 403);
        }

        $user = request()->user();
        if (!$user->roles()->where('name', 'instructor')->exists()) {
            return response()->json([
                'message' => 'Only instructors can create courses',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:App\Models\Courses,title',
            'description' => 'required|string',
            'detailed_description' => 'nullable|string',
            'image_cover' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',            
            'price' => 'required|numeric|min:0',
            'level' => 'required|in:beginner,intermediate,advanced',
            'category_id' => 'required|exists:categories,id',
            'language' => 'nullable|string|max:50',
            'objectives' => 'nullable|array',
            'objectives.*' => 'string',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string',
            'who_is_for' => 'nullable|array',
            'who_is_for.*' => 'string',
            'status' => 'pending',
        ]);

        if($validator->fails()) {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        // Check if a course application with the same title already exists for this instructor
        $existingApplication = Courses::where('instructor_id', $user->id)
            ->where('title', $request->title)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingApplication) {
            return response()->json([
                'message' => 'A course with this title has already been submitted for approval or approved.',
                'data' => new CoursesResource($existingApplication)
            ], 409);
        }

        try {
            DB::beginTransaction();

            $courseData = $request->all();

            // Handle image upload
            if ($request->hasFile('image_cover')) {                
                $imagePath = $request->file('image_cover')->store('courses-profile', 'public');
                $courseData['course_image'] = $imagePath;
            }

            // Convert the arrays to JSON
            $courseData['objectives'] = json_encode($request->objectives);
            $courseData['requirements'] = json_encode($request->requirements);
            $courseData['who_is_for'] = json_encode($request->who_is_for);
            $courseData['instructor_id'] = $user->id;

            // Create a pending course approval
            $courseApproval = Courses::create($courseData);        

            // Send email to the instructor
            Mail::to($user->email)->send(new CourseSubmittedForApproval($courseApproval));

            DB::commit();

            return response()->json([
                'message' => 'Course submitted for approval. You will be notified of the decision soon.',
                'data' => new CoursesResource($courseApproval)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded image if exists
            if (isset($imagePath) && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'message' => 'An error occurred while creating the course',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Approve or Reject a course(Admin ONLY)
    public function approveCourse(Request $request, Courses $courseApproval)
    {        
        try {
            $this->authorize('approveCoursesCommand', Courses::class);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'You are not authorized to approve a course. Only System Administrators are permitted'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid status',
                'error' => $validator->messages(),
            ], 422);
        }

        $courseApproval->status = $request->status;
        $courseApproval->save();

        $instructor = $courseApproval->instructor;

        if ($request->status === 'approved') { 
            // Update course status to approved
            $courseApproval->status = 'approved';
            $courseApproval->save();

            // Send approval email
            Mail::to($instructor->email)->send(new CourseApprovalMail($courseApproval, 'approved'));

            return response()->json([
                'message' => 'Course approved and created successfully',
            ]);
        } else
        {
            // Send rejection email
            Mail::to($instructor->email)->send(new CourseApprovalMail($courseApproval, 'rejected'));

            $courseApproval->delete();

            return response()->json([
                'message' => 'Course application rejected',
            ]);
        }
    }

    //View Pending Approvals(Admin ONLY)
    public function courseApprovalRequests()
    {
        $approvalrequests = Courses::where('status','pending')->get();

        if($approvalrequests->count()>0)
        {
            return CourseApprovalResource::collection($approvalrequests);
        }
        else
        {
            return response()->json(['message' => 'No pending applications at the moment.'], 404);
        }
    }

    //Display  course details
    public function show(Courses $course)
    {
        $this->authorize('view', $course); 
        if ($course->status !== 'approved') {
            return response()->json(['message' => 'Course not found'], 404);
        }
        else{
            return new CoursesResource($course);
        }        
    }

    //Update a course
    public function update(Request $request, Courses $course)
    {
        try {
            $this->authorize('update', $course);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'You are not authorized to update this course.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'description' => 'string',
            'detailed_description' => 'nullable|string',
            'image_cover' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'price' => 'numeric|min:0',
            'level' => 'in:beginner,intermediate,advanced',
            'category_id' => 'exists:categories,id',
            'language' => 'nullable|string|max:50',
            'objectives' => 'nullable|array',
            'objectives.*' => 'string',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string',
            'who_is_for' => 'nullable|array',
            'who_is_for.*' => 'string',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'error' => $validator->messages(),
            ], 422);
        }

        try
        {
            DB::beginTransaction();

            // Handle image upload if present
            if ($request->hasFile('image_cover')) {
                if ($course->course_image) {
                    Storage::disk('public')->delete($course->course_image);
                }
                $imagePath = $request->file('image_cover')->store('courses-profile', 'public');
                $course->course_image = $imagePath;
            }

            $fieldsToUpdate = $request->only(['title', 'description', 'detailed_description', 'price','level','category_id','language','objectives','requirements','who_is_for']);
            $course->fill($fieldsToUpdate);

            $course->save();

            DB::commit();

            return new CoursesResource($course);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update course',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Delete a course
    public function destroy(Courses $course)
    {
        try {
        $this->authorize('delete', $course);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'You are not authorized to delete this course.'], 403);
        }

        $course->delete();
        return response()->json([
            'message' => 'Course deleted successfully'
        ]);
    }
}

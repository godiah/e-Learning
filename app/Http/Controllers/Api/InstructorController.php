<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstructorResource;
use App\Mail\InstructorApplicationReceived;
use App\Mail\InstructorApprovalMail;
use App\Mail\InstructorRejectionMail;
use App\Models\InstructorApplication;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class InstructorController extends Controller
{
    // Instructor Application
    public function instructorApplication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'headline' => 'required|string|max:255',
            'bio' => 'required|string',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'terms_accepted' => 'required|boolean|accepted',
            'payment_method' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'All fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        // Check if an existing application is already pending approval
        $existingApplication = $request->user()->instructorApplication()->where('status', 'pending')->first();
        
        if ($existingApplication) {
            return response()->json([
                'message' => 'Application already submitted. Please await approval.',
            ], 409);
        }

        // Store profile picture if provided
        $validatedData = $validator->validated();
        if ($request->hasFile('profile_pic')) {
            $path = $request->file('profile_pic')->store('profile-pictures', 'public');
            $validatedData['profile_pic_url'] = $path;
        }

        // Create the instructor application with the validated data
        $application = $request->user()->instructorApplication()->create($validatedData);

        // Send confirmation email
        Mail::to($request->user()->email)->send(new InstructorApplicationReceived($request->user(), $application));

        return response()->json([
            'message' => 'Instructor application request submitted successfully. Waiting for admin approval.',
            'data' => new InstructorResource($application),
        ]);
    }

    //View Application Requests(Admin ONLY)
    public function index()
    {
        $applications = InstructorApplication::where('status', 'pending')->paginate(5);

        if($applications->count()>0)
        {
            return InstructorResource::collection($applications)->response();
        }
        else
        {
            return response()->json(['message' => 'No pending instructor applications at the moment.'], 404);
        }
        
    }

    //Approve Applications(Admin ONLY)
    public function approve(InstructorApplication $application)
    {
        $application->update(['status' => 'approved']);
        $user = $application->user;
        $user->update(['is_instructor' => true]);

        $instructorRole = Role::where('name', 'instructor')->first();
        if ($instructorRole) {
            $user->roles()->syncWithoutDetaching([$instructorRole->id]);
        }

        // Send approval email
        Mail::to($user->email)->send(new InstructorApprovalMail($user));

        return response()->json(['message' => 'Application approved successfully.']);
    }

    // Reject Applications(Admin ONLY)
    public function reject(InstructorApplication $application)
    {
        $application->update(['status' => 'rejected']);

        $reason = "Your application did not meet the necessary criteria for approval.";

        Mail::to($application->user->email)->send(new InstructorRejectionMail($application->user, $reason));

        return response()->json(['message' => 'Application rejected.']);
    }

}

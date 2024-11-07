<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AffiliateResource;
use App\Mail\AffiliateAccountSuspensionMail;
use App\Mail\AffiliateApplicationApprovedMail;
use App\Mail\AffiliateApplicationReceivedMail;
use App\Mail\AffiliateApplicationRejectionMail;
use App\Mail\AffiliateSuspensionLiftedMail;
use App\Models\Affiliate;
use App\Models\AffiliateLink;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AffiliateController extends Controller
{
    // Affiliate Application Submission
    public function affiliateApplication(Request $request)
    {
        $user = request()->user();

        $validator = Validator::make($request->all(),[
            'name' => 'required|string',
            'email' => 'required|email',
            'payment_info' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'All fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $existingApplication = $request->user()->affiliateApplication()
            ->whereIn('status', ['pending', 'approved','suspended'])
            ->first();
        
        if ($existingApplication) {
            return response()->json([
                'message' => 'Application previously submitted.',
            ], 409);
        }

        $validatedData = $validator->validated();
        
        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'payment_info' => $validatedData['payment_info'],
            'status' => 'pending',
        ]);

        Mail::to($request->user()->email)->send(new AffiliateApplicationReceivedMail($request->user(), $affiliate));

        return response()->json([
            'message' => 'Application submitted successfully. Wait for approval.',
            'data' => new AffiliateResource($affiliate)
        ], 201);
    }

    // View Application Requests
    public function index()
    {
        $pendingApplications = Affiliate::where('status', 'pending')->paginate(10);

        if ($pendingApplications->isNotEmpty()) {
            return response()->json([
                'message' => 'Pending applications retrieved successfully.',
                'data' => AffiliateResource::collection($pendingApplications)
            ]);
        } else {
            return response()->json([
                'message' => 'No pending applications present.'
            ], 404);
        }
    }

    // Approve Applications
    public function approve(Affiliate $affiliate)
    {
        if ($affiliate->status === 'approved') {
            if ($affiliate->link) {
                return response()->json([
                    'message' => 'This application has already been approved.',
                    'link' => $affiliate->link->code
                ]);
            }
        }
        // Approve the application
        $affiliate->update(['status' => 'approved']);

        // Assign the 'affiliate' role to the user
        $user = User::findOrFail($affiliate->user_id);
        $affiliateRole = Role::where('name', 'affiliate')->first();
        if ($affiliateRole) {
            $user->roles()->syncWithoutDetaching([$affiliateRole->id]);
        }

        // Update the user's is_affiliate flag
        $user->update(['is_affiliate' => true]);

        // Generate unique affiliate link
        $code = strtoupper(Str::random(8));
        while (AffiliateLink::where('code', $code)->exists()) {
            $code = strtoupper(Str::random(8));
        }

        $affiliate->link()->create([
            'code' => $code
        ]);

        Mail::to($user->email)->send(new AffiliateApplicationApprovedMail($user,$code));

        return response()->json([
            'message' => 'Affiliate application approved and code sent to your email',
            'data' => new AffiliateResource($affiliate),
            'link' => $affiliate->link
        ]);
    }

    // Reject Applications
    public function reject(Affiliate $affiliate)
    {
        $affiliate->update(['status' => 'rejected']);

        Mail::to($affiliate->user->email)->send(new AffiliateApplicationRejectionMail($affiliate->user));

        return response()->json([
            'message' => 'Affiliate application rejected',
            'data' => new AffiliateResource($affiliate)
        ]);
    }

    // Suspend Affiliation account
    public function suspend(Affiliate $affiliate)
    {
        $affiliate->update(['status' => 'suspended']);

        $user = User::findOrFail($affiliate->user_id);

        $affiliateRole = Role::where('name', 'affiliate')->first();
        if ($affiliateRole) {
            $user->roles()->detach($affiliateRole->id);
        }

        $user->update(['is_affiliate' => false]);

        Mail::to($affiliate->user->email)->send(new AffiliateAccountSuspensionMail($affiliate->user));

        return response()->json([
            'message' => 'Affiliate account suspended',
            'data' => new AffiliateResource($affiliate)
        ]);
    }

    // View Suspended Accounts
    public function viewSuspended()
    {
        $affiliates = Affiliate::where('status', 'suspended')->paginate(10);

        if ($affiliates->isNotEmpty()) {
            return response()->json([
                'message' => 'Suspended accounts retrieved successfully.',
                'data' => AffiliateResource::collection($affiliates)
            ]);
        } else {
            return response()->json([
                'message' => 'No current suspended accounts'
            ], 404);
        }
    }

    // Lift Suspension on Accounts
    public function liftSuspension(Affiliate $affiliate)
    {
        if ($affiliate->status !== 'suspended') {
            return response()->json([
                'message' => 'This affiliate is not currently suspended.',
                'status' => $affiliate->status
            ], 400);
        }

        $affiliate->update(['status' => 'approved']);

        $user = User::findOrFail($affiliate->user_id);

        $affiliateRole = Role::where('name', 'affiliate')->first();
        if ($affiliateRole) {
            $user->roles()->syncWithoutDetaching([$affiliateRole->id]);
        }

        // Update the user's is_affiliate flag
        $user->update(['is_affiliate' => true]);

        Mail::to($user->email)->send(new AffiliateSuspensionLiftedMail($user));

        return response()->json([
            'message' => 'Affiliate suspension lifted and notification email sent.',
            'data' => new AffiliateResource($affiliate)
        ]);
    }

    // Affiliate Statistics
    public function stats(Affiliate $affiliate)
        {
        $user = request()->user();
        if ($user->id !== $affiliate->user_id && !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $affiliate->load(['purchases' => function ($query) {
            $query->latest();
        }]);

        if ($affiliate->total_earnings > 0 || $affiliate->total_sales > 0 || $affiliate->purchases->isNotEmpty()) {
            return response()->json([
                'total_earnings' => $affiliate->total_earnings,
                'total_sales' => $affiliate->total_sales,
                'recent_purchases' => $affiliate->purchases,
                'status' => $affiliate->status
            ]);
        } else {
            return response()->json([
                'message' => 'No earnings or sales recorded yet for this affiliate.'
            ]);
        }
    }

    // Adjust Affiliate Commision Rates
    public function commissionRates(Affiliate $affiliate)
    {
        
    }

}

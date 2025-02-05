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
use Carbon\Carbon;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Return overall stats for the logged-in affiliate.
     */
    public function getOverallStats(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized. Please log in.'], 401);
        }
        
        // Ensure the logged-in user is an affiliate.
        $existingAffiliate = $user->is_affiliate;
        if (!$existingAffiliate) {
            return response()->json(['error' => 'Access Denied. You are not an affiliate.'], 403);
        }
        
        $affiliate = $user->affiliate;
        // Cache key unique to this affiliate
        $cacheKey = "affiliate_stats_{$affiliate->id}";

        // Cache results for 15 minutes
        return Cache::remember($cacheKey, 900, function () use ($affiliate) {
            $links = $affiliate->links;

            $totalClicks = 0;
            $totalConversions = 0;
            $totalEarnings = 0;
            $totalSales = 0;
            $last30DaysClicks = 0;
            $last30DaysConversions = 0;

            foreach ($links as $link) {
                $totalClicks += $link->clicks()->count();
                $totalConversions += $link->conversions()->count();
                $totalEarnings += $link->commissions()->sum('commission_amount');
                $totalSales = $totalConversions;

                $last30DaysClicks += $link->clicks()
                    ->where('clicked_at', '>=', now()->subDays(30))
                    ->count();
                $last30DaysConversions += $link->conversions()
                    ->where('converted_at', '>=', now()->subDays(30))
                    ->count();
            }

            $conversionRate = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;

            $stats = [
                'total_clicks' => $totalClicks,
                'total_conversions' => $totalConversions,
                'conversion_rate' => $conversionRate,
                'total_earnings' => $totalEarnings,
                'total_sales' => $totalSales,
                'last_30_days' => [
                    'clicks' => $last30DaysClicks,
                    'conversions' => $last30DaysConversions,
                ]
            ];

            return response()->json(['data' => $stats]);
        });
    }
    
    /**
     * USER MANAGEMENT ADMIN RESTRICTED ROUTES
     */

    // View Affiliate Application Requests - User Mgt Admin
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

    // Approve Affiliate Applications - User Mgt Admin
    public function approve(Affiliate $affiliate)
    {
        if ($affiliate->status === 'approved') {
            return response()->json([
                'message' => 'This application has already been approved.'                
            ]);
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

        Mail::to($user->email)->send(new AffiliateApplicationApprovedMail($user));

        return response()->json([
            'message' => 'Affiliate application approved. Check your email for confirmation.',
            'data' => new AffiliateResource($affiliate)            
        ]);
    }

    // Reject Affiliate Applications - User Mgt Admin
    public function reject(Affiliate $affiliate)
    {
        $affiliate->update(['status' => 'rejected']);

        Mail::to($affiliate->user->email)->send(new AffiliateApplicationRejectionMail($affiliate->user));

        return response()->json([
            'message' => 'Affiliate application rejected',
            'data' => new AffiliateResource($affiliate)
        ]);
    }

    // Suspend Affiliate Account - User Mgt Admin
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

    // View Suspended Affiliate Accounts - User Mgt Admin
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

    // Lift Suspension on Affiliate Accounts - User Mgt Admin
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
}


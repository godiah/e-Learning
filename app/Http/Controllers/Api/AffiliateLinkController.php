<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AffiliateLinkGenerated;
use App\Models\Affiliate;
use App\Models\AffiliateLink;
use App\Models\Courses;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class AffiliateLinkController extends Controller
{
    /**
     * Generate a new affiliate link for a course
     */
    public function generate(Request $request, Courses $course, Affiliate $affiliate)
    {
        // Ensure the user is authenticated
        $user = request()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized. Please log in.'], 401);
        }

        // Check if the user is an affiliate
        $affiliate = Affiliate::where('user_id', $user->id)->first();
        if (!$user->is_affiliate) {
            return response()->json(['error' => 'Access Denied. You are not an affiliate.'], 403);
        }  

        // Check if link already exists for this affiliate and course
        $existingLink = AffiliateLink::where('affiliate_id', $affiliate->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingLink) {
            return response()->json(['error' => 'Affiliate link already exists for this course'], 422);
        }

        // Generate unique tracking code and short URL
        $trackingCode = $this->generateTrackingCode($affiliate->id, $course->id);
        $shortUrl = $this->generateShortUrl();

        // Create new affiliate link
        $affiliateLink = AffiliateLink::create([
            'affiliate_id' => $affiliate->id,
            'course_id' => $course->id,
            'tracking_code' => $trackingCode,
            'short_url' => config('app.url') . '/api/r/' . $shortUrl            
        ]);

        // Send email notification with course name
        Mail::to($user->email)->send(new AffiliateLinkGenerated(
            $user,
            $course->title,
            $trackingCode,
            $shortUrl
        ));

        return response()->json([
            'message' => 'Affiliate link generated successfully. Check your email for confirmation.',
            'data' => $affiliateLink
        ], 201);
    }

    /**
     * List all affiliate links for the current affiliate
     */
    public function index(Request $request)
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized. Please log in.'], 401);
        }

        // Check if the user is an affiliate
        $affiliate = $user->is_affiliate;
        if (!$affiliate) {
            return response()->json(['error' => 'Access Denied. You are not an affiliate.'], 403);
        }   
        
        $existingAffiliate = Affiliate::where('user_id', $user->id)->first();

        // Ensure the affiliate exists
        if (!$existingAffiliate) {
            return response()->json(['error' => 'Affiliate record not found.'], 404);
        }
        
        $links = AffiliateLink::where('affiliate_id', $existingAffiliate->id)
            ->with(['course:id,title,price','clicks','conversions']) // Include clicks and conversions xtics
            ->paginate(5);

        if ($links->isEmpty()) {
            return response()->json([
                'message' => 'No affiliate links found.',
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => 'Affiliate links retrieved successfully.',
            'data' => $links
        ], 200);
    }

    /**
     * Get statistics for a specific affiliate link with caching
     */
    public function getStats(AffiliateLink $affiliateLink)
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized. Please log in.'], 401);
        }

        // Check if the user is an affiliate
        $affiliate = $user->is_affiliate;
        if (!$affiliate) {
            return response()->json(['error' => 'Access Denied. You are not an affiliate.'], 403);
        }
        
        // Add ownership check
        $userAffiliate = $user->affiliate;
        if (!$userAffiliate || $userAffiliate->id !== $affiliateLink->affiliate_id) {
            return response()->json(['error' => 'Unauthorized Access !'], 403);
        }

        // Cache key unique to this affiliate link
        $cacheKey = "affiliate_link_stats_{$affiliateLink->id}";
        
        // Cache for 15 minutes
        return Cache::remember($cacheKey, 900, function () use ($affiliateLink) {
            $stats = [
                'total_clicks' => $affiliateLink->clicks()->count(),
                'total_conversions' => $affiliateLink->conversions()->count(),
                'conversion_rate' => $affiliateLink->clicks()->count() > 0 
                    ? ($affiliateLink->conversions()->count() / $affiliateLink->clicks()->count()) * 100 
                    : 0,
                'total_earnings' => $affiliateLink->commissions()
                    ->sum('commission_amount'),
                'last_30_days' => [
                    'clicks' => $affiliateLink->clicks()
                        ->where('clicked_at', '>=', now()->subDays(30))
                        ->count(),
                    'conversions' => $affiliateLink->conversions()
                        ->where('converted_at', '>=', now()->subDays(30))
                        ->count(),
                ]
            ];

            return response()->json(['data' => $stats]);
        });
    }

    /**
     * USER MANAGEMENT ADMIN RESTRICTED ROUTES
     */

    // Adjust Affiliate Commision Rates
    public function updateCommissionRate(Request $request, AffiliateLink $affiliateLink)
    {
        $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100'
        ]);

        $affiliateLink->update([
            'commission_rate' => $request->commission_rate
        ]);

        return response()->json([
            'message' => 'Commission rate updated successfully',
            'data' => $affiliateLink
        ]);
    }


    /**
     * Generate a unique tracking code
     */
    private function generateTrackingCode($affiliateId, $courseId)
    {
        $baseCode = "COURSIO-{$affiliateId}-{$courseId}-" . Str::random(8);
        return Str::slug($baseCode);
    }

    /**
     * Generate a unique short URL
     */
    private function generateShortUrl()
    {
        do {
            $code = Str::lower(Str::random(8));
        } while (AffiliateLink::where('short_url', 'like', "%/$code")->exists());

        return $code;
    }
}

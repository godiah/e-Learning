<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Models\ClickTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class TrackingController extends Controller
{
    public function trackClick($shortCode, Request $request)
    {
        // Find the affiliate link
        $affiliateLink = AffiliateLink::where('short_url', 'http://localhost:8000/api/r/' . $shortCode)->first();

        // If no link found, return a 404 response
        if (!$affiliateLink) {
            return response()->json([
                'error' => 'Affiliate link not found',
                'shortCode' => $shortCode
            ], 404);
        }

        // Create click record
        $click = ClickTracking::create([
            'affiliate_link_id' => $affiliateLink->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'clicked_at' => now(),
        ]);

        // Set cookie for conversion attribution (30 days)
        return redirect($affiliateLink->course->url)
            ->withCookie(Cookie::make('affiliate_click_id', $click->id, 60 * 24 * 30));
    }
}

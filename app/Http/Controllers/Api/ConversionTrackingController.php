<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClickTracking;
use App\Models\Commission;
use App\Models\ConversionTracking;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ConversionTrackingController extends Controller
{
    /**
     * Record a conversion for an order.
     *
     * This method expects that an order has been created during checkout.
     * It will:
     * - Look for the affiliate_click_id cookie.
     * - Retrieve the associated click and affiliate link.
     * - Verify that the purchased course (or one of the purchased courses) is the same
     *   as the course tied to the affiliate link.
     * - Record the conversion, calculate the commission, and update affiliate stats.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Order  $order  (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordConversionFromCheckout(Request $request, Order $order)
    {
        // Attempt to read the affiliate_click_id cookie.
        $affiliateClickId = $request->cookie('affiliate_click_id');

        if (!$affiliateClickId) {
            // No affiliate tracking cookie exists so nothing to record.
            return response()->json([
                'message' => 'No affiliate click recorded.'
            ], 200);
        }

        // Find the click tracking record.
        $click = ClickTracking::find($affiliateClickId);
        Log::info('Click record:', $click ? $click->toArray() : ['click' => 'not found']);
        if (!$click) {
            return response()->json([
                'message' => 'Affiliate click not found.' // Invalid Click Record
            ], 200);
        }

        // Get the affiliate link (and its related course)
        $affiliateLink = $click ? $click->link : null;
        Log::info('Affiliate link:', $affiliateLink ? $affiliateLink->toArray() : ['affiliate_link' => 'not found']);
        if (!$affiliateLink) {
            return response()->json([
                'message' => 'Affiliate link not found.'
            ], 200);
        }

        /*
         * Check if the order contains the course tied to the affiliate link.
         */

        // Collect all purchased course IDs.
        $purchasedCourseIds = $order->items->pluck('course_id')->toArray();

        if (!in_array($affiliateLink->course_id, $purchasedCourseIds)) {
            return response()->json([
                'message' => 'The purchased course does not match the affiliate link.'
            ], 200);
        }

        // Get the order item that matches the affiliate-linked course
        $orderItem = $order->items->firstWhere('course_id', $affiliateLink->course_id);
        $saleAmount = $orderItem->final_price;

        DB::beginTransaction();

        try {
            // Create a ConversionTracking record.
            $conversion = ConversionTracking::create([
                'affiliate_link_id'  => $affiliateLink->id,
                'click_tracking_id'  => $click->id,
                'order_id'           => $order->id,
                'sale_amount'        => $saleAmount,
                'converted_at'       => now(),
            ]);

            $commissionRate = $affiliateLink->commission_rate;
            $commissionAmount = $saleAmount * ($commissionRate / 100);

            // Create the Commission record.
            $commission = Commission::create([
                'affiliate_id'      => $affiliateLink->affiliate_id,
                'conversion_id'     => $conversion->id,
                'commission_amount' => $commissionAmount,
                'status'            => 'pending',
            ]);

            // Update the Affiliate totals:
            $affiliate = $affiliateLink->affiliate;
            $affiliate->increment('total_sales', 1);
            $affiliate->total_earnings += $commissionAmount;
            $affiliate->save();

            DB::commit();

            // Send email notification to the affiliate.
            Mail::to($affiliate->email)
                ->send(new \App\Mail\AffiliateConversionMail($affiliate, $conversion, $commission));

            // Clear the affiliate cookie.
            Cookie::queue(Cookie::forget('affiliate_click_id'));
            //Cookie::queue('affiliate_click_id', null, -1);

            return response()->json([
                'message'    => 'Conversion and commission recorded successfully.',
                'conversion' => $conversion,
                'commission' => $commission,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to record conversion.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}

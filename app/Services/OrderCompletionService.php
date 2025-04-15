<?php

namespace App\Services;

use App\Http\Controllers\Api\ConversionTrackingController;
use App\Mail\OrderConfirmation;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderCompletionService
{
    /**
     * Complete an order after successful payment
     */
    public function completeOrder(PaymentTransaction $transaction)
    {
        try {
            $order = Order::find($transaction->order_id);

            if (!$order) {
                Log::error('Order not found for transaction', [
                    'transaction_id' => $transaction->id,
                    'order_id' => $transaction->order_id
                ]);
                return false;
            }

            // Update order payment ID
            $order->update([
                'payment_id' => $transaction->id
            ]);

            // Enroll user in courses
            $this->enrollUserInCourses($order);

            // Delete the cart if it still exists
            $this->deleteCart($order->user_id);

            // Send confirmation email
            $this->sendOrderConfirmationEmail($order->user, $order);

            // Record conversion for affiliate tracking
            $this->recordConversion($order);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to complete order: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'transaction_id' => $transaction->id,
                'order_id' => $transaction->order_id
            ]);
            return false;
        }
    }

    /**
     * Enroll user in courses
     */
    protected function enrollUserInCourses(Order $order)
    {
        foreach ($order->items as $item) {
            $courseId = $item->course_id;
            $existingEnrollment = Enrollment::where('user_id', $order->user_id)
                ->where('course_id', $courseId)
                ->first();

            if (!$existingEnrollment) {
                Enrollment::create([
                    'user_id' => $order->user_id,
                    'course_id' => $courseId,
                    'enrollment_date' => now(),
                    'completion_date' => null
                ]);
            }
        }
    }

    /**
     * Delete user's cart after successful order
     */
    protected function deleteCart($userId)
    {
        $cart = \App\Models\Cart::where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if ($cart) {
            $cart->items()->delete();
            $cart->delete();
        }
    }

    /**
     * Send order confirmation email
     */
    protected function sendOrderConfirmationEmail(User $user, Order $order)
    {
        try {
            Mail::to($user)->send(new OrderConfirmation($order));
        } catch (\Exception $e) {
            Log::error('Failed to send order confirmation email: ' . $e->getMessage());
        }

        Log::info('Order confirmation email would be sent to user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'order_id' => $order->id
        ]);
    }

    /**
     * Record conversion for affiliate tracking
     */
    protected function recordConversion(Order $order)
    {
        try {
            // Create a mock request for compatibility with your existing controller
            $request = new \Illuminate\Http\Request();

            // Call the conversion tracking controller
            $controller = App::make(ConversionTrackingController::class);
            $result = $controller->recordConversionFromCheckout($request, $order);

            Log::info('Conversion tracking result', [
                'order_id' => $order->id,
                'result' => json_encode($result->getData(true))
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record conversion: ' . $e->getMessage(), [
                'order_id' => $order->id
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\IPNListenerService;
use App\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $pesapalService;
    protected $ipnListenerService;

    public function __construct(
        PesapalService $pesapalService,
        IPNListenerService $ipnListenerService
    ) {
        $this->pesapalService = $pesapalService;
        $this->ipnListenerService = $ipnListenerService;
    }

    /**
     * Process payment using Pesapal
     */
    public function processPayment(Request $request, Order $order)
    {
        $user = $request->user();

        // Check if order belongs to user
        if ($order->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if order has a pending transaction
        $pendingTransaction = PaymentTransaction::where('order_id', $order->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingTransaction && isset($pendingTransaction->payment_data['redirect_url'])) {
            return response()->json([
                'success' => true,
                'message' => 'Payment in progress',
                'redirect_url' => $pendingTransaction->payment_data['redirect_url']
            ]);
        }

        // Process payment with Pesapal
        $result = $this->pesapalService->submitOrder($order, $user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Payment initiated',
                'redirect_url' => $result['redirect_url']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to process payment',
            'errors' => $result['errors'] ?? null
        ], 400);
    }

    /**
     * Handle Pesapal callback
     */
    public function callback(Request $request)
    {
        $orderTrackingId = $request->get('OrderTrackingId');
        $merchantReference = $request->get('OrderMerchantReference');

        Log::info('Pesapal callback received', $request->all());

        if (!$orderTrackingId || !$merchantReference) {
            return response()->json(['error' => 'Invalid callback data'], 400);
        }

        // Find transaction
        $transaction = PaymentTransaction::where('tracking_id', $orderTrackingId)
            ->orWhere('reference', $merchantReference)
            ->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        // Check payment status from Pesapal
        $statusData = $this->pesapalService->checkPaymentStatus($orderTrackingId);

        if (!$statusData) {
            return response()->json(['error' => 'Failed to check payment status'], 500);
        }

        // Determine redirect based on status
        $pesapalStatus = strtolower($statusData['payment_status_description'] ?? '');

        if (in_array($pesapalStatus, ['completed', 'paid'])) {
            return response()->json(['success' => true, 'message' => 'Payment successful']);
        } elseif (in_array($pesapalStatus, ['failed', 'invalid', 'cancelled'])) {
            return response()->json(['error' => 'Payment failed'], 400);
        }

        // Default to pending page
        return response()->json(['error' => 'Payment status unknown'], 400);
    }

    /**
     * Handle Pesapal IPN (Instant Payment Notification)
     */
    public function ipn(Request $request)
    {
        Log::info('IPN notification received', $request->all());

        $ipnData = $request->all();
        $success = $this->ipnListenerService->processNotification($ipnData);

        if ($success) {
            return response('OK', 200);
        }

        return response('Failed to process IPN', 400);
    }

    /**
     * Check payment status for an order
     */
    public function checkStatus(Request $request, Order $order)
    {
        $user = $request->user();

        // Check if order belongs to user
        if ($order->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Find transaction
        $transaction = PaymentTransaction::where('order_id', $order->id)
            ->latest()
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'No payment found for this order'
            ], 404);
        }

        if ($transaction->status === 'completed') {
            return response()->json([
                'success' => true,
                'status' => 'completed',
                'message' => 'Payment completed successfully',
                'transaction' => $transaction
            ]);
        }

        // For pending transactions, check status from Pesapal
        if ($transaction->status === 'pending' && $transaction->tracking_id) {
            $statusData = $this->pesapalService->checkPaymentStatus($transaction->tracking_id);

            if ($statusData) {
                $pesapalStatus = strtolower($statusData['payment_status_description'] ?? '');

                // Update status if needed
                if ($pesapalStatus !== 'pending') {
                    $this->pesapalService->processIpnResponse([
                        'OrderTrackingId' => $transaction->tracking_id,
                        'OrderMerchantReference' => $transaction->reference
                    ]);

                    // Refresh transaction
                    $transaction = $transaction->fresh();
                }
            }
        }

        return response()->json([
            'success' => true,
            'status' => $transaction->status,
            'message' => 'Payment status: ' . ucfirst($transaction->status),
            'transaction' => $transaction
        ]);
    }
}

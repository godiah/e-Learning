<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;

class IPNListenerService
{
    protected $pesapalService;
    protected $orderCompletionService;

    public function __construct(
        PesapalService $pesapalService,
        OrderCompletionService $orderCompletionService
    ) {
        $this->pesapalService = $pesapalService;
        $this->orderCompletionService = $orderCompletionService;
    }

    /**
     * Process IPN notification
     */
    public function processNotification(array $ipnData)
    {
        try {
            $orderTrackingId = $ipnData['OrderTrackingId'] ?? null;
            $merchantReference = $ipnData['OrderMerchantReference'] ?? null;

            if (!$orderTrackingId || !$merchantReference) {
                Log::error('Invalid IPN data received', ['data' => $ipnData]);
                return false;
            }

            // Process the IPN with Pesapal service
            $success = $this->pesapalService->processIpnResponse($ipnData);
            
            if (!$success) {
                return false;
            }

            // Find updated transaction
            $transaction = PaymentTransaction::where('tracking_id', $orderTrackingId)
                ->orWhere('reference', $merchantReference)
                ->first();

            if (!$transaction) {
                Log::error('Transaction not found after IPN processing', [
                    'tracking_id' => $orderTrackingId,
                    'reference' => $merchantReference
                ]);
                return false;
            }

            // If payment is completed, complete the order
            if ($transaction->status === 'completed') {
                return $this->orderCompletionService->completeOrder($transaction);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Exception processing IPN notification: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}

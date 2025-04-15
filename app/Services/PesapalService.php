<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PesapalService
{
    protected $baseUrl;
    protected $consumerKey;
    protected $consumerSecret;
    protected $token;
    protected $callbackUrl;
    protected $ipnUrl;
    protected $environment;

    public function __construct()
    {
        // Set the environment - 'sandbox' or 'production'
        $this->environment = config('services.pesapal.environment', 'sandbox');

        // Set the base URL based on environment
        if ($this->environment === 'production') {
            $this->baseUrl = config('services.pesapal.base_url_live');
            $this->consumerKey = config('services.pesapal.consumer_key_live');
            $this->consumerSecret = config('services.pesapal.consumer_secret_live');
        } else {
            $this->baseUrl = config('services.pesapal.base_url');
            $this->consumerKey = config('services.pesapal.consumer_key');
            $this->consumerSecret = config('services.pesapal.consumer_secret');
        }

        $this->callbackUrl = config('services.pesapal.callback_url');
        $this->ipnUrl = config('services.pesapal.ipn_url');
    }

    /**
     * Get authentication token from Pesapal.
     */
    public function getToken()
    {
        try {
            if ($this->token) {
                return $this->token;
            }

            Log::info('Pesapal Auth Request', [
                'environment' => $this->environment,
                'url' => $this->baseUrl . '/api/Auth/RequestToken',
                'consumer_key' => $this->consumerKey,
                // Don't log the full secret for security reasons
                'consumer_secret_length' => strlen($this->consumerSecret)
            ]);

            $response = Http::asJson() // Add this
                ->post($this->baseUrl . '/api/Auth/RequestToken', [
                    'consumer_key' => $this->consumerKey,
                    'consumer_secret' => $this->consumerSecret
                ]);

            $data = $response->json();

            if ($response->successful() && isset($data['token'])) {
                $this->token = $data['token'];
                return $this->token;
            }

            Log::error('Failed to get Pesapal token', [
                'response' => $data
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Exception while getting Pesapal token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Register IPN URL with Pesapal
     */
    public function registerIpn()
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return null;
            }

            $response = Http::withToken($token)
                ->post($this->baseUrl . '/api/URLSetup/RegisterIPN', [
                    'url' => $this->ipnUrl,
                    'ipn_notification_type' => 'GET'
                ]);

            $data = $response->json();

            if ($response->successful() && isset($data['ipn_id'])) {
                return $data;
            }

            Log::error('Failed to register IPN URL', [
                'response' => $data
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Exception while registering IPN URL: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Submit an order to Pesapal
     */
    public function submitOrder(Order $order, User $user)
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return null;
            }

            // Create reference based on order ID for tracking
            $reference = 'ORDER-' . $order->id . '-' . Str::random(6);

            // Get or create payment transaction
            $transaction = PaymentTransaction::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'amount' => $order->final_amount,
                    'currency' => 'KES', // Set your currency
                    'status' => 'pending',
                ]
            );

            // Get IPN details
            $ipnDetails = $this->registerIpn();
            if (!$ipnDetails) {
                throw new Exception('Failed to register IPN URL');
            }

            $payload = [
                'id' => $reference,
                'currency' => 'KES', // Set your currency
                'amount' => (float) $order->final_amount,
                'description' => 'Purchase of courses from ' . config('app.name'),
                'callback_url' => $this->callbackUrl,
                'notification_id' => $ipnDetails['ipn_id'],
                'billing_address' => [
                    'email_address' => $user->email,
                    'phone_number' => $user->phone ?? '',
                    'first_name' => $user->first_name ?? $user->name,
                    'last_name' => $user->last_name ?? '',
                ]
            ];

            $response = Http::withToken($token)
                ->post($this->baseUrl . '/api/Transactions/SubmitOrderRequest', $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['order_tracking_id'])) {
                // Update transaction with tracking ID and IPN URL
                $transaction->update([
                    'tracking_id' => $data['order_tracking_id'],
                    'ipn_url' => $ipnDetails['ipn_id'],
                    'payment_data' => [
                        'redirect_url' => $data['redirect_url'],
                        'order_tracking_id' => $data['order_tracking_id'],
                        'merchant_reference' => $reference,
                        'request_payload' => $payload
                    ]
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $data['redirect_url'],
                    'transaction' => $transaction
                ];
            }

            Log::error('Failed to submit order to Pesapal', [
                'response' => $data,
                'payload' => $payload
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process payment with Pesapal',
                'errors' => $data
            ];
        } catch (Exception $e) {
            Log::error('Exception while submitting order to Pesapal: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Exception when processing payment',
                'errors' => $e->getMessage()
            ];
        }
    }

    /**
     * Process IPN response from Pesapal
     */
    public function processIpnResponse(array $ipnData)
    {
        try {
            $orderTrackingId = $ipnData['OrderTrackingId'] ?? null;
            $merchantReference = $ipnData['OrderMerchantReference'] ?? null;

            if (!$orderTrackingId || !$merchantReference) {
                Log::error('Invalid IPN data received', ['data' => $ipnData]);
                return false;
            }

            // Find transaction by tracking ID or reference
            $transaction = PaymentTransaction::where('tracking_id', $orderTrackingId)
                ->orWhere('reference', $merchantReference)
                ->first();

            if (!$transaction) {
                Log::error('Transaction not found for IPN', [
                    'tracking_id' => $orderTrackingId,
                    'reference' => $merchantReference
                ]);
                return false;
            }

            // Get full status from Pesapal
            $statusData = $this->checkPaymentStatus($orderTrackingId);
            if (!$statusData) {
                return false;
            }

            // Map Pesapal status to our status
            $pesapalStatus = strtolower($statusData['payment_status_description'] ?? '');
            $status = 'pending';

            if (in_array($pesapalStatus, ['completed', 'paid'])) {
                $status = 'completed';
            } elseif (in_array($pesapalStatus, ['failed', 'invalid'])) {
                $status = 'failed';
            } elseif ($pesapalStatus === 'cancelled') {
                $status = 'cancelled';
            }

            // Update transaction details
            $transaction->update([
                'transaction_id' => $statusData['payment_transaction_id'] ?? null,
                'payment_method' => $statusData['payment_method'] ?? null,
                'status' => $status,
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'ipn_response' => $ipnData,
                    'status_response' => $statusData
                ]),
                'paid_at' => $status === 'completed' ? now() : null
            ]);

            // If completed, update the order as well
            if ($status === 'completed') {
                $this->updateOrderPaymentStatus($transaction->order_id, $transaction);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Exception while processing IPN: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check payment status from Pesapal
     */
    public function checkPaymentStatus($orderTrackingId)
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return null;
            }

            $response = Http::withToken($token)
                ->get($this->baseUrl . '/api/Transactions/GetTransactionStatus', [
                    'orderTrackingId' => $orderTrackingId
                ]);

            $data = $response->json();

            if ($response->successful()) {
                return $data;
            }

            Log::error('Failed to check payment status', [
                'response' => $data,
                'orderTrackingId' => $orderTrackingId
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Exception while checking payment status: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update the order's payment status
     */
    protected function updateOrderPaymentStatus($orderId, PaymentTransaction $transaction)
    {
        try {
            $order = Order::find($orderId);
            if (!$order) {
                return;
            }

            // Update the order with payment ID
            $order->update([
                'payment_id' => $transaction->id
            ]);

            //dispatch any events you need here->maybe a notification
        } catch (Exception $e) {
            Log::error('Exception updating order payment status: ' . $e->getMessage());
        }
    }
}

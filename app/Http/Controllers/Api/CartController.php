<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\WishlistResource;
use App\Mail\OrderConfirmation;
use App\Models\Affiliate;
use App\Models\AffiliatePurchase;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Courses;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CartController extends Controller
{
    protected $pesapalService;
    
    public function __construct(PesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    // View active cart items
    public function viewCart()
    {
        $user = request()->user();

        try {
            $cart = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('items.course')
                ->firstOrFail();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'message' => 'No cart items available',
                    'cart' => null
                ], 200);
            }

            return response()->json([
                'cart' => new CartResource($cart)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No cart items available'], 500);
        }
    }

    // Add an item to cart
    public function addToCart(Request $request)
    {
        $user = request()->user();

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        try {
            DB::beginTransaction();

            // Get or create active cart
            $cart = Cart::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'status' => 'active'
                ],
                [
                    'total_amount' => 0,
                    'discount_total' => 0,
                    'final_amount' => 0
                ]
            );

            // Check if course already exists in cart
            if ($cart->items()->where('course_id', $validated['course_id'])->exists()) {
                return response()->json(['message' => 'Course already exists in cart'], 400);
            }

            $course = Courses::with('validDiscount')->findOrFail($validated['course_id']);
            $price = $course->price;
            $discountAmount = 0;

            if ($course->validDiscount) {
                $discountAmount = $price * ($course->validDiscount->discount_rate / 100);
            }

            // Create cart item
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'course_id' => $course->id,
                'price' => $price,
                'discount_amount' => $discountAmount,
                'final_price' => $price - $discountAmount,
            ]);

            $this->updateCartTotals($cart);

            DB::commit();

            return response()->json([
                'message' => 'Course added to cart successfully',
                'cart' => new CartResource($cart->fresh(['items.course']))
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to add course to cart'], 500);
        }
    }

    // Remove item from cart
    public function removeFromCart($cartItemId)
    {
        $user = request()->user();

        try {
            DB::beginTransaction();

            $cart = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->firstOrFail();

            $cartItem = $cart->items()->where('id', $cartItemId)->firstOrFail();
            $cartItem->delete();

            // If no items left, delete cart
            if ($cart->items()->count() === 0) {
                $cart->delete();
                DB::commit();
                return response()->json(['message' => 'Cart is now empty']);
            }

            $this->updateCartTotals($cart);

            DB::commit();

            return response()->json([
                'message' => 'Item removed from cart successfully',
                'cart' => new CartResource($cart->fresh(['items.course']))
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to remove item from cart'], 500);
        }
    }

    // Clear all items from cart
    public function clearCart()
    {
        $user = request()->user();

        try {
            DB::beginTransaction();

            // Find active cart
            $cart = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            // If cart doesn't exist or is empty
            if (!$cart || $cart->items()->count() === 0) {
                DB::commit();
                return response()->json([
                    'message' => 'Cart is already empty',
                    'cart' => null
                ], 200);
            }

            // Delete all cart items
            $cart->items()->delete();

            // Update cart totals
            $cart->update([
                'total_amount' => 0,
                'discount_total' => 0,
                'final_amount' => 0
            ]);

            // If cart is empty after deletion, delete the cart itself
            if ($cart->items()->count() === 0) {
                $cart->delete();
                DB::commit();
                return response()->json([
                    'message' => 'Cart cleared successfully',
                    'cart' => null
                ], 200);
            }

            DB::commit();
            return response()->json([
                'message' => 'All items removed from cart',
                'cart' => new CartResource($cart->fresh(['items.course']))
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to clear cart',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Checkout and create order
    public function checkout(Request $request)
    {
        $user = request()->user();

        DB::beginTransaction();
        try {
            $cart = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('items.course')
                ->firstOrFail();

            if ($cart->items->isEmpty()) {
                return response()->json(['message' => 'Cart is empty'], 400);
            }           

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $cart->total_amount,
                'discount_total' => $cart->discount_total,
                'final_amount' => $cart->final_amount,
            ]);

            foreach ($cart->items as $cartItem) {
                \App\Models\OrderItem::create([
                    'order_id'        => $order->id,
                    'course_id'       => $cartItem->course_id,
                    'price'           => $cartItem->price,
                    'discount_amount' => $cartItem->discount_amount,
                    'final_price'     => $cartItem->final_price,
                ]);
            }

            // Process payment with Pesapal
            $paymentResult = $this->pesapalService->submitOrder($order, $user);
            
            if (!$paymentResult['success']) {
                throw new \Exception('Payment initiation failed: ' . ($paymentResult['message'] ?? 'Unknown error'));
            }

            // We will keep the cart until payment is confirmed via IPN

            DB::commit();

            return response()->json([
                'message' => 'Order created and payment initiated',
                'order' => new OrderResource($order),
                'payment' => [
                    'redirect_url' => $paymentResult['redirect_url'],
                    'transaction_reference' => $paymentResult['transaction']->reference,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Checkout failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            DB::rollBack();
            return response()->json(['error' => 'Failed to complete checkout: ' . $e->getMessage()], 500);
        }
    }

    /// Add to wishlist
    public function addToWishlist(Request $request)
    {
        $user = request()->user();

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        try {
            $wishlist = Wishlist::firstOrCreate([
                'user_id' => $user->id,
                'course_id' => $validated['course_id'],
                'added_at' => now(),
            ]);

            return response()->json([
                'message' => 'Course added to wishlist',
                'wishlist' => new WishlistResource($wishlist),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add course to wishlist'], 500);
        }
    }

    // Remove from wishlist
    public function removeFromWishlist($wishlistId)
    {
        $user = request()->user();

        try {
            $wishlist = Wishlist::where('id', $wishlistId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $wishlist->delete();

            return response()->json([
                'message' => 'Course removed from wishlist',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to remove course from wishlist'], 500);
        }
    }

    // View wishlist items
    public function viewWishlist()
    {
        $user = request()->user();

        try {
            $wishlist = Wishlist::where('user_id', $user->id)
                ->with('course')
                ->get();

            return response()->json([
                'wishlist' => WishlistResource::collection($wishlist),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch wishlist'], 500);
        }
    }

    private function updateCartTotals(Cart $cart)
    {
        $totals = $cart->items()->selectRaw('
            SUM(price) as total_amount,
            SUM(discount_amount) as discount_total,
            SUM(final_price) as final_amount
        ')->first();

        $cart->update([
            'total_amount' => $totals->total_amount ?? 0,
            'discount_total' => $totals->discount_total ?? 0,
            'final_amount' => $totals->final_amount ?? 0
        ]);
    }
}

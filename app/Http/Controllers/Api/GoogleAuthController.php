<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth page
     */
    public function redirectToGoogle(): JsonResponse
    {
        try {
            $url = Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl();
            
            return response()->json([
                'url' => $url
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to initialize Google login'
            ], 500);
        }
    }

     /**
     * Handle Google callback
     */
    // public function handleGoogleCallback(Request $request): JsonResponse
    // {
    //     try {
    //         $googleUser = Socialite::driver('google')
    //             ->stateless()
    //             ->user();
            
    //         // Check if user exists with this email
    //         $user = User::where('email', $googleUser->email)->first();
            
    //         if (!$user) {
    //             // Create new user
    //             $user = User::create([
    //                 'name' => $googleUser->name,
    //                 'email' => $googleUser->email,
    //                 'password' => Hash::make(Str::random(16)),
    //                 'email_verified_at' => now(),
    //                 'google_id' => $googleUser->id,
    //                 'profile_pic_url' => $googleUser->avatar,
    //             ]);
    //         } else {
    //             $updateData = [
    //                 'google_id' => $googleUser->id
    //             ];
                
    //             // Update picture only if user doesn't have one or it's a Google avatar
    //             if (!$user->profile_pic_url || strpos($user->profile_pic_url, 'googleusercontent.com') !== false) {
    //                 $updateData['profile_pic_url'] = $googleUser->avatar;
    //             }
                
    //             $user->update($updateData);
    //         }
            
    //         // Generate token
    //         $token = $user->createToken($user->name.'Auth-Token')->plainTextToken;
            
    //         return response()->json([
    //             'message' => 'Google login successful',
    //             'token_type' => 'Bearer',
    //             'token' => $token
    //         ]);
            
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to process Google login'
    //         ], 500);
    //     }
    // }

    public function handleGoogleCallback(Request $request): JsonResponse
    {
        try {
            // For testing/local environment, handle the code directly
            if (app()->environment('local', 'testing')) {
                $googleUser = Socialite::driver('google')
                    ->stateless() 
                    ->user();
            } else {
                // Production flow remains unchanged
                $googleUser = Socialite::driver('google')
                    ->user();
            }
            
            // Check if user exists with this email
            $user = User::where('email', $googleUser->email)->first();
            
            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                    'google_id' => $googleUser->id,
                    'profile_pic_url' => $googleUser->avatar,
                ]);
            } else {
                // Update existing user's Google ID and picture if not set
                $updateData = [
                    'google_id' => $googleUser->id
                ];
                
                if (!$user->profile_pic_url || strpos($user->profile_pic_url, 'googleusercontent.com') !== false) {
                    $updateData['profile_pic_url'] = $googleUser->avatar;
                }
                
                $user->update($updateData);
            }
            
            // Generate token
            $token = $user->createToken($user->name.'Auth-Token')->plainTextToken;
            
            return response()->json([
                'message' => 'Google login successful',
                'token_type' => 'Bearer',
                'token' => $token,
                'user' => $user
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to process Google login',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }
}

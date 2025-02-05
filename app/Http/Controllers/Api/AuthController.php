<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AccountLockedNotification;
use App\Mail\EmailChangeVerification;
use App\Mail\VerificationEmail;
use App\Mail\PasswordResetEmail;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\PasswordReset;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register New User
     */
    public function register(Request $request):JsonResponse
    {
        $executionKey = 'register:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($executionKey, 3)) {
            $seconds = RateLimiter::availableIn($executionKey);
            return response()->json([
                'message' => "Too many registration attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        RateLimiter::hit($executionKey);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
        ]);

        if ($user) 
        {
            $verificationCode = $this->generateVerificationCode($user);
            $this->sendVerificationEmail($user, $verificationCode);

            return response()->json([
                'message' => 'Registration successful. Please check your email for the verification code.',
                'user_id' => $user->id
            ], 201);
        }
        else 
        {
            return response()->json([
                'message' => 'Something went wrong! Try Again'
            ], 500);
        }
    }

    //Verification Email
    public function verifyEmail(Request $request): JsonResponse
    {
        $executionKey = 'verify:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($executionKey, 2)) {
            $seconds = RateLimiter::availableIn($executionKey);
            return response()->json([
                'message' => "Too many verification attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        RateLimiter::hit($executionKey);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'verification_code' => 'required|string',
        ]);

        $user = User::findOrFail($request->user_id);
        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('code', $request->verification_code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verificationCode) {
            return response()->json([
                'message' => 'Invalid or expired verification code.'
            ], 400);
        }

        $user->email_verified_at = now();
        $user->save();

        $verificationCode->delete();

        $token = $user->createToken($user->name.'Auth-Token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully.',
            'token_type' => 'Bearer',
            'token' => $token
        ]);
    }

    //Resend verification Code
    public function resendVerificationCode(Request $request): JsonResponse
    {
        $executionKey = 'resend:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($executionKey, 2)) {
            $seconds = RateLimiter::availableIn($executionKey);
            $minutes = ceil($seconds / 60);
            return response()->json([
                'message' => "Too many resend attempts. Please try again in {$minutes} minutes."
            ], 429);
        }

        RateLimiter::hit($executionKey, 30 * 60);

        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this email address.'
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified.'
            ], 400);
        }

        // Delete any existing verification codes for this user
        VerificationCode::where('user_id', $user->id)->delete();

        $verificationCode = $this->generateVerificationCode($user);
        $this->sendVerificationEmail($user, $verificationCode);

        return response()->json([
            'message' => 'Verification code resent. Please check your email.',
            'user_id' => $user->id
        ]);
    }

    //Forgot Password
    public function forgotPassword(Request $request): JsonResponse
    {
        $executionKey = 'forgot_password:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($executionKey, 3)) {
            $seconds = RateLimiter::availableIn($executionKey);
            //$minutes = ceil($seconds / 60);
            return response()->json([
                'message' => "Too many password reset attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        RateLimiter::hit($executionKey);

        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this email address.'
            ], 404);
        }

        // Generate reset code
        $resetCode = sprintf('%06d', mt_rand(0, 999999));

        // Save reset code
        PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'token' => $resetCode,
                'created_at' => now()
            ]
        );

        // Send reset code email
        Mail::to($user->email)->send(new PasswordResetEmail($user, $resetCode));

        return response()->json([
            'message' => 'Password reset code sent to your email.',
        ]);
    }

    //Reset User Password
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $passwordReset = PasswordReset::where('email', $request->email)
            ->where('token', $request->code)
            ->where('created_at', '>', now()->subMinutes(2))
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'Invalid or expired password reset code.'
                
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this email address.'
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $passwordReset->delete();

        return response()->json([
            'message' => 'Password has been reset successfully.'
        ]);
    }

    //Update User Password
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.'
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }

    //Log in
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if account is locked
        if ($user->locked_until && $user->locked_until > now()) {
            $timeLeft = floor(now()->diffInMinutes($user->locked_until, false));
            return response()->json([
                'message' => "Account is locked. Please try again in {$timeLeft} minutes."
            ], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            // Increment failed attempts
            $user->failed_login_attempts += 1;
            
            if ($user->failed_login_attempts >= 3) {
                $user->locked_until = now()->addMinutes(60);
                $user->failed_login_attempts = 0;
                $user->save();

                // Send email notification for failed login attempts
                Mail::to($user->email)->send(new AccountLockedNotification($user));
                
                return response()->json([
                    'message' => 'Account locked due to multiple failed attempts. Please try again in 1 hour.'
                ], 403);
            }
            
            $user->save();

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Reset failed attempts on successful login
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();

        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
                'user_id' => $user->id
            ], 403);
        }

        $token = $user->createToken($user->name.'Auth-Token')->plainTextToken;

        return response()->json([
            'message' => 'Log in successful',
            'token_type' => 'Bearer',
            'token' => $token
        ]);
    }

    //User Profile
    public function profile(Request $request):JsonResponse
    {
        if($request->user())
        {
            return response()->json([
                'message' => 'Your Profile',
                'data' => $request->user()
            ]);
        }
        else{
            return response()->json([
                'message' => 'Kindly Log in to proceed'
            ]);
        }
    }

    //Update user details
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),[
            'name' => 'string|max:255',
            'bio' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();
        $user->update($request->only(['name', 'bio']));
        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);       
    }

    // Upload Profile Picture
    public function upload(Request $request): JsonResponse
    {
        // Validate the upload
        $validator = Validator::make($request->all(), [
            'profile_picture' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048', // 2MB max size
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid file upload',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get the authenticated user
            $user = auth()->user();

            // Delete old profile picture if exists
            if ($user->profile_pic_url) {
                Storage::disk('public')->delete($user->profile_pic_url);
            }

            // Store the new file
            $path = $request->file('profile_picture')->store('profile-pictures', 'public');

            // Update user's profile picture in database
            $user->profile_pic_url = $path;
            $user->save();

            // Generate full URL
            $url = Storage::url($path);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile picture updated successfully',
                'data' => [
                    'profile_picture_url' => $url
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload profile picture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete profile picture
    public function deleteProfilePicture(): JsonResponse
    {
        try {
            $user = auth()->user();

            if ($user->profile_pic_url) {
                Storage::disk('public')->delete($user->profile_pic_url);
                $user->profile_pic_url = null;
                $user->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Profile picture deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete profile picture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Change user email
    public function changeEmail(Request $request): JsonResponse
    {
        $request->validate([
            'new_email' => 'required|email|unique:users,email',
        ]);

        $user = $request->user();

        // Generate verification code
        $verificationCode = sprintf('%06d', mt_rand(0, 999999));

        // Save verification code
        VerificationCode::create([
            'user_id' => $user->id,
            'code' => $verificationCode,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Send verification email
        Mail::to($request->new_email)->send(new EmailChangeVerification($user, $verificationCode));

        // Store new email temporarily
        $user->new_email = $request->new_email;
        $user->save();

        return response()->json([
            'message' => 'Verification code sent to your new email address.',
        ]);
    }
    
    public function verifyEmailChange(Request $request): JsonResponse
    {
        $request->validate([
            'verification_code' => 'required|string',
        ]);

        $user = $request->user();

        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('code', $request->verification_code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verificationCode) {
            return response()->json([
                'message' => 'Invalid or expired verification code.'
            ], 400);
        }

        // Update email
        $user->email = $user->new_email;
        $user->new_email = null;
        $user->save();

        // Delete verification code
        $verificationCode->delete();

        return response()->json([
            'message' => 'Email address updated successfully.',
        ]);
    }

    //Log out
    public function logout(Request $request):JsonResponse
    {
        $user = User::where('id',$request->user()->id)->first();
        if($user)
        {
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Logged out successfully'                
            ]);
        }
        else
        {
            return response()->json([
                'message' => 'Something went wrong!'                
            ]);
        }
    }

    //Delete Account
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password.'
            ], 400);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Delete user
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully.',
        ]);
    }

    private function generateVerificationCode(User $user): string
    {
        $code = sprintf('%06d', mt_rand(0, 999999));

        VerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
        ]);

        return $code;
    }

    private function sendVerificationEmail(User $user, string $code): void
    {
        Mail::to($user->email)->send(new VerificationEmail($user, $code));
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    //Log in
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password))
        {
            return response()->json([
                'message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken($user->name.'Auth-Token')->plainTextToken;

        return response()->json([
            'message' => 'Log in successful',
            'token_type' => 'Bearer',
            'token' => $token            
        ]);
    }

    //Registration
    public function register(Request $request):JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|in:student,instructor,admin',
            'bio' => 'nullable|string|max:1000', 
            'profile_pic_url' => 'nullable|url',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
            'role' => $request->role,
            'bio' => $request->bio,
            'profile_pic_url' => $request->profile_pic_url
        ]);

        if($user)
        {
            $token = $user->createToken($user->name.'Auth-Token')->plainTextToken;
            return response()->json([
                'message' => 'Registration successful',
                'token_type' => 'Bearer',
                'token' => $token 
            ]);
        }
        else
        {
            return response()->json([
                'message' => 'Something went wrong!'                
            ]);
        }
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
}

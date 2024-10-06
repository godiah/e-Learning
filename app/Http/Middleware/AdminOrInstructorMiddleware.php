<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Courses;

use Symfony\Component\HttpFoundation\Response;

class AdminOrInstructorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if the user is an admin or an instructor
        $user = Auth::user();
        if ($user->role === 'admin' || $user->role === 'instructor') {
            return $next($request);
        }

        // If not authorized, return an error response
        return response()->json(['message' => 'Forbidden'], 403);
    }
}

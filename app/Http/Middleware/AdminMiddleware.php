<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Super admin always has access
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check if user has any of the specified roles
        foreach ($roles as $role) {
            switch ($role) {
                case 'user-admin':
                    if ($user->isUserAdmin()) return $next($request);
                    break;
                case 'content-admin':
                    if ($user->isContentAdmin()) return $next($request);
                    break;
                case 'finance-admin':
                    if ($user->isFinanceAdmin()) return $next($request);
                    break;
            }
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }
}

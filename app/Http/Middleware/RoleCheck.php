<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  mixed    ...$roles  One or more role names
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Not authenticated.'
            ], 401);
        }

        $roleName = $user->role->name;

        // If none of the passed roles match, block access
        if (!in_array($roleName, $roles, true)) {
            return response()->json([
                'message' => 'Unauthorized. Insufficient permissions to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}

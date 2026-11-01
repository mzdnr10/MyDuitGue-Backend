<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! in_array($user->role, $roles, true)) {
            return response()->json([
                'responseCode' => 'AUTH_403',
                'responseMessage' => 'You are not authorized to access this resource.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}

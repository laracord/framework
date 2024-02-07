<?php

namespace Laracord\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?? $request->query('token');

        if (! $token) {
            return response()->json(['message' => 'You must specify a token.'], 401);
        }

        $token = PersonalAccessToken::findToken($token);

        if (! $token || ! $token->can('http')) {
            return response()->json(['message' => 'You are not authorized.'], 401);
        }

        return $next($request);
    }
}

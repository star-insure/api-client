<?php

namespace StarInsure\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

class StarAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('access_token')) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if (! auth()->check()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $next($request);
    }
}

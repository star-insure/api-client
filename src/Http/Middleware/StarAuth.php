<?php

namespace StarInsure\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

class StarAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('access_token') || ! auth()->check()) {
            // Store the intended URL in the session
            session()->put('url.intended', $request->input('returnUrl', url()->current()));

            throw new AuthenticationException('Unauthenticated.', [], route('auth.authorize'));
        }
        return $next($request);
    }
}

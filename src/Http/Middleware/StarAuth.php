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

            $redirect = config('star.auth_strategy') === 'user' ? route('auth.authorize') : '/login';

            throw new AuthenticationException('Unauthenticated.', [], $redirect);
        }
        return $next($request);
    }
}

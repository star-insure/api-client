<?php

namespace StarInsure\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

class StarAuth
{
    public function handle(Request $request, Closure $next)
    {
        $redirect = config('star.auth_strategy') === 'user' ? route('auth.authorize', ['returnUrl' => $request->input('returnUrl')]) : '/login';

        if (! session('access_token')) {
            throw new AuthenticationException('Unauthenticated.', [], $redirect);
        }

        if (! auth()->check()) {
            throw new AuthenticationException('Unauthenticated.', [] , $redirect);
        }

        return $next($request);
    }
}

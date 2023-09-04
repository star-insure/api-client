<?php

namespace StarInsure\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Providers\RouteServiceProvider;

class StarGuest
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            return redirect(RouteServiceProvider::HOME);
        }

        return $next($request);
    }
}

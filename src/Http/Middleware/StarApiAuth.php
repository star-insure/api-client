<?php

namespace StarInsure\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StarApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        // Check we actually have a token in the request
        if (! $token) {
            return new Response('Unauthorized', 401);
        }

        // Create an API instance, forwarding on the bearer token we have
        $api = new \StarInsure\Api\StarApi(
            auth_strategy: config('star.auth_strategy'),
            version: config('star.version'),
            apiTokenOverride: $token,
        );

        // Now check the token against the API
        $res = $api->get('/users/me');

        if (! $res['ok']) {
            return new Response('Unauthorized', 403);
        }

        try {
            // Log the user into this API so controllers can access user data
            \Illuminate\Support\Facades\Auth::login(new \App\Models\User($res['data']));
        } catch (\Throwable $th) {
            return new Response('Internal Server Error', 500);
        }

        return $next($request);
    }
}

<?php

namespace StarInsure\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use StarInsure\Api\StarAuth;

class IsAuth
{
    public function handle(Request $request, Closure $next)
    {
        $accessToken = session('access_token');

        if (! $accessToken) {
            // No access token in session, fail straight away
            return $this->fail();
        }

        try {
            // Attempt to hit the auth server with the provided tokens
            cache()->remember("user:{$accessToken}", now()->addSeconds(config('star-auth.cache_user', 5)), function () use ($accessToken) {
                $user = (new StarAuth(config('star-auth.url'), $accessToken))->get('/me');

                if (!$user) {
                    return $this->fail();
                }

                return $user;
            });

            return $next($request);
        } catch (AuthenticationException $e) {
            // If failed, redirect to the authenticator app
            return $this->fail();
        }

        // Save our details in a session
        session(['access_token' => $accessToken]);

        return $next($request);
    }

    /**
     * Builds up a request to redirect to the auth app,
     * and passes in a redirect URL to come back to afterwards
     */
    public function fail()
    {
        $clientId = config('star-auth.client_id');
        $sessionId = session()->getId();
        $redirectUrl = request()->url();

        // Build up our query
        $query = "?redirectUrl={$redirectUrl}&clientId={$clientId}&sessionId={$sessionId}";

        // Forget session data
        session()->forget('access_token');

        // In local dev, we want requests to run through the Docker network, but redirects to just go through localhost
        if (env('APP_ENV') === 'local') {
            $url = str_replace('host.docker.internal', 'localhost', config('star-auth.url'));
        } else {
            $url = config('star-auth.url');
        }

        return redirect()->to($url  . $query);
    }
}

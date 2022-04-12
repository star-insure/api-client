<?php

namespace AlexClark\StarAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use AlexClark\StarAuth\StarAuth;
use Illuminate\Auth\AuthenticationException;

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
            $user = (new StarAuth(config('star-auth.url'), $accessToken))->get('/me');

            if (!$user) {
                return $this->fail();
            }

            $next($request);
        } catch (AuthenticationException $e) {
            // If failed, redirect to the authenticator app
            return $this->fail();
        }

        // Save our details in a session
        session(['user' => $user]);
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
        session()->forget('user');

        return redirect()->to(config('star-auth.url')  . $query);
    }
}

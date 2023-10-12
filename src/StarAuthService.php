<?php

namespace StarInsure\Api;

use Illuminate\Support\Facades\Http;
use StarInsure\Api\Models\StarUser;

class StarAuthService
{
    public function __construct(
        protected ?string $authServerUrl = null,
    ) {
        $this->authServerUrl ??= config('star.api_url');
    }

    /**
     * Redirect the user to the API's OAuth authentication page.
     */
    public function authorize()
    {
        // Generate a random string to use as state
        session()->put('state', $state = \str()->random(40));

        // Store the intended URL in the session
        session()->put('url.intended', request()->input('returnUrl', url()->previous()));

        // Build the query string
        $query = http_build_query([
            'client_id' => config('star.client_id'),
            'redirect_uri' => route('auth.callback'),
            'response_type' => 'code',
            'scope' => '',
            'state' => $state,
        ]);

        // Redirect to the authorization server
        return redirect($this->authServerUrl.'/oauth/authorize?'.$query);
    }

    /**
     * Handle the callback from the API's OAuth authentication page
     * and exchange the authorization code for an access token.
     */
    public function callback()
    {
        $requestState = request()->input('state');
        $sessionState = session()->pull('state');

        // Check if the request state matches what's in the session
        if (! $requestState || ! $sessionState || $requestState !== $sessionState) {
            abort(403, 'Invalid state');
        }

        // Exchange the authorization code for an access token
        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            "{$this->authServerUrl}/oauth/token",
            [
                'grant_type' => 'authorization_code',
                'client_id' => config('star.client_id'),
                'client_secret' => config('star.client_secret'),
                'redirect_uri' => route('auth.callback'),
                'code' => request()->input('code'),
            ]
        );

        // Check if the response was successful
        if (! $response->successful()) {
            session()->forget(['state', 'access_token']);

            return redirect(route('login'))->withErrors([
                'email' => 'Unable to authenticate with the given credentials.',
            ]);
        }

        // Store the token in the session
        session()->put($response->json());

        // Redirect to the Single-Sign-On route
        return redirect(route('auth.sso'));
    }

    /**
     * Get the user from the API and log them in.
     */
    public function singleSignOn()
    {
        $access_token = request()->session()->get('access_token');

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$access_token,
        ])->get("{$this->authServerUrl}/api/v1/users/me", [
            'include' => 'groups,groups.role,groups.role.permissions',
        ]);

        if (! $response->successful()) {
            session()->forget(['state', 'access_token']);

            return redirect(route('login'))->withErrors([
                'email' => 'Unable to authenticate with the given credentials.',
            ]);
        }

        // Get the user from the API response
        $user = $response->json()['data'];

        // Log in as the user (not stored in our database)
        auth()->login(new StarUser($user));

        return redirect()->intended();
    }

    /**
     * Revoke all access tokens for the user (log out).
     */
    public function revokeAll()
    {
        $returnUrl = request()['return_url'] ?? config('app.url');
        $logoutUrl = config('star.api_url') . '/logout?return_url=' . $returnUrl;

        // Use inertia redirect for apps using Inertia
        if (class_exists('Inertia\Inertia')) {
            return \Inertia\Inertia::location($logoutUrl);
        }

        return redirect($logoutUrl);
    }

    /**
     * Request handler to impersonate another user
     */
    public function impersonate()
    {
        abort_if(auth()->cannot('IMPERSONATE'), 403);

        request()->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $res = api()->post('/impersonate', [
            'user_id' => request()->input('user_id'),
        ]);

        if (! $res['ok']) {
            return back()->withErrors($res['errors'] ?? $res['message'] ?? 'Unknown error');
        }

        session()->put('impersonate_id', $res['data']['id']);

        // Forget the current group so we can refresh it with the new user
        session()->forget('group_id');

        // Do a full page redirect to avoid weird state
        if (class_exists('Inertia\Inertia')) {
            return inertia()->location('/');
        }

        return redirect('/');
    }

    /**
     * Request handler to exit impersonation
     */
    public function impersonationExit()
    {
        session()->forget('impersonate_id');

        // Forget the group_id which will have been set from the impersonated user
        session()->forget('group_id');

        // Do a full page redirect to avoid weird state
        if (class_exists('Inertia\Inertia')) {
            return inertia()->location('/');
        }

        return redirect('/');
    }
}

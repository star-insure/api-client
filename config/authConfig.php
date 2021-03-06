<?php

return [
    /**
     * URL to the auth app (e.g. https://auth.starinsure.co.nz)
     */
    'url' => env('SIS_AUTH_URL'),

    /**
     * ID of the client app we're using
     */
    'client_id' => env('SIS_AUTH_CLIENT_ID'),

    /**
     * Optional URL to redirect to after login
     */
    'dashboard_url' => env('SIS_AUTH_AFTER_LOGIN_URL', config('app.url')),

    /**
     * How long (in seconds) do we want to cache the authenticated user
     */
    'cache_user' => env('SIS_AUTH_CACHE_USER', 5),
];

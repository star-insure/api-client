<?php

return [
    /**
     * URL to the API, e.g. "https://api.starinsure.co.nz"
     * No trailing slash, no "/api" suffix – we add those later.
     */
    'url' => env('SIS_API_URL'),

    /**
     * Are we authenticating by default as a "user" or an "app"?
     * Options: "user" or "app"
     */
    'auth_strategy' => env('SIS_API_AUTH_STRATEGY', 'app'),

    /**
     * A pre-generated long lifen access token
     * Required if "auth_strategy" is "app"
     */
    'token' => env('SIS_API_TOKEN', ''),

    /**
     * Which group are we acting within? ("administrator" is 2)
     * Required if "auth_strategy" is "app"
     */
    'group_id' => env('SIS_API_GROUP_ID', '2'),

    /**
     * The API version to use, e.g. "v1"
     */
    'version' => env('SIS_API_VERSION', 'v1'),
];

<?php

namespace StarInsure\Api\Http\Controllers;

use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function login()
    {
        return redirect()->to($this->authAppUrl() . '?redirectUrl=' . config('star-auth.dashboard_url'));
    }

    public function logout()
    {
        // Clear any cached user data
        $accessToken = session('access_token');
        if ($accessToken) {
            cache()->forget("user:{$accessToken}");
        }

        // Flush session data
        session()->flush();

        // Clear cookies
        cookie('access_token', null, -1);
        cookie('refresh_token', null, -1);

        // Redirect through the auth app
        return redirect()->to($this->authAppUrl());
    }

    public function authAppUrl()
    {
        // In local dev, we want requests to run through the Docker network, but redirects to just go through localhost
        if (env('APP_ENV') === 'local') {
            return str_replace('host.docker.internal', 'localhost', config('star-auth.url'));
        } else {
            return config('star-auth.url');
        }
    }
}

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
        // Forget session data
        $accessToken = session('access_token');
        session()->forget('access_token');
        cache()->forget("user:{$accessToken}");

        // Redirect through the auth app
        return redirect()->to($this->authAppUrl() . '/logout' . '?redirectUrl=' . config('app.url'));
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

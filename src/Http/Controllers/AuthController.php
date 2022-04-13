<?php

namespace StarInsure\Api\Http\Controllers;

use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function login()
    {
        return redirect()->to(config('star-auth.url') . '?redirectUrl=' . config('star-auth.dashboard_url'));
    }

    public function logout()
    {
        // Forget session data
        session()->forget('user');
        session()->forget('access_token');

        // Redirect through the auth app
        return redirect()->to(config('star-auth.url') . '/logout' . '?redirectUrl=' . config('app.url'));
    }
}

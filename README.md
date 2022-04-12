# Star Insure API Client

A package for Laravel apps that includes a wrapper for the Star Inure API and scaffolds out routes, controllers and middleware for authenticating with the Star auth app.

## Installation

You can install the package via composer:

```bash
composer require star-insurance/api-client
```

Add these values to your `.env` file, and make sure your session driver is set to "database"
```
SESSION_DRIVER=database
...
SIS_API_URL=
SIS_API_TOKEN=

SIS_AUTH_URL=
SIS_AUTH_CLIENT_ID=
SIS_AUTH_AFTER_LOGIN_URL=
```

Run this command to create the sessions table in your database
```
php artisan session:table && php artisan migrate
```

### CSRF protection
Disable CSRF protection for the webhook route in `app\Http\Middleware\VerifyCsrfToken.php`
```php
protected $except = [
    '/auth/cb',
];
```
This is required so the auth server can make POST requests to this callback endpoint, which handles modifying the user's current session.

### Publish config:
```bash
php artisan vendor:publish
```
And select the corresponding number for this package.

## Usage

### API
Call the Star API by instantiating a new client, or using the `StarInsure\Api\Facades\StarApi` facade.
```php
StarApi::get('/users/me');
```

### Auth
Just protect a route with middleware. Middleware and routes are automatically registered within the package.
```php
Route::get('/protected', function () {
    return 'Only authenticated users can see this.';
})->middleware('auth.star');
```

Or a route group:
```php
Route::middleware(['auth.star'])->group(function () {
    Route::get('/protected', function () {
        return "Only authenticated users can see this.";
    });
});
```

The user will automatically be redirected to the auth server if no valid session, and sent back to their request destination after successfully logging in.

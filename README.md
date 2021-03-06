# Star Insure API Client

A package for Laravel apps that includes a wrapper for the Star Inure API and scaffolds out routes, controllers and middleware for authenticating with the Star auth app.

## Installation

You can install the package via composer:

```bash
composer require star-insure/api-client
```

Add these values to your `.env` file, and make sure your session driver is set to "database"
```
SESSION_DRIVER=database
...
SIS_API_URL=http://api.starinsure.test
SIS_API_TOKEN=
SIS_API_AUTH_STRATEGY=user|app
SIS_API_GROUP_ID=

SIS_AUTH_URL=http://auth.starinsure.test
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
php artisan vendor:publish --tag=starinsure
```

## Usage

### API
Call the Star API by instantiating a new client, or using the `StarInsure\Api\Facades\StarApi` facade.
```php
StarApi::get('/users/me');
StarApi::get('/users');
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

### Helper functions
Following the Laravel style, you also have the option of using helper functions. To register the auth helper so it overrides Laravel's built-in `auth()` functions, follow the steps below.

This package has a dependency on `funkjedi/composer-include-files`, which allows you to load your own functions prior to any of your dependencies global functions.

Create a `helpers.php` file within the `app` directory (or edit your existing one):
```php
/**
 * Global helper to create an instance of the StarApi client
 */
function api()
{
    return new \StarInsure\Api\StarApi(
        config('star-api.auth_strategy'),
        config('star-api.version')
    );
}

/**
 * Global helper to access details about the authenticated user
 */
function auth()
{
    return new \StarInsure\Api\Helpers\AuthHelper();
}
```

Autoload your helpers file in `composer.json`:
```json
"autoload": {
    ...
    "files": [
        "app/helpers.php"
    ]
},
```

Add/Edit the "extra" block in `composer.json`:
```json
"extra": {
    "laravel": {
        "dont-discover": []
    },
    "include_files": [
        "app/helpers.php"
    ]
},
```

You can now use the global helper functions and not worry about namespaces/imports.
```php
$user = auth()->user();
$id = auth()->id();
$group = auth()->group();
$permissions = auth()->permissions();
$audience = auth()->audience();

$apiResponse = api()->get('users/me', [ 'include' => 'groups' ]);
```

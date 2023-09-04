# Star Insure API Client

A package for Laravel apps that includes a wrapper for the Star Inure API and scaffolds out routes, controllers and middleware for authenticating with the Star auth app.

## Installation

You can install the package via composer:

```sh
composer require star-insure/api-client
```

Add these values to your `.env` file:
```sh
# API
SIS_API_URL=http://api.starinsure.test
SIS_API_AUTH_STRATEGY=user|app
SIS_API_TOKEN=dev
SIS_API_GROUP_ID=2

# OAuth client
APP_CLIENT_ID=app_name
APP_CLIENT_SECRET=secret
```

### Publish config:
```sh
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
Use Laravel's provided "auth" middleware.
```php
Route::get('/protected', function () {
    return 'Only authenticated users can see this.';
})->middleware('auth');
```

Or a route group:
```php
Route::middleware(['auth'])->group(function () {
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

if (! function_exists('auth')) {
    /**
     * Get the available auth instance.
     */
    function auth(): StarInsure\Api\StarAuthManager
    {
        return app(\StarInsure\Api\StarAuthManager::class);
    }
}

if (! function_exists('api')) {
    /**
     * Global helper to create an instance of the StarApi client.
     */
    function api()
    {
        return new \StarInsure\Api\StarApi(
            config('star.auth_strategy'),
            config('star.version'),
        );
    }
}

if (! function_exists('appApi')) {
    /**
     * An instance of the API client for non-authenticated routes
     */
    function appApi()
    {
        return new \StarInsure\Api\StarApi(
            'app',
            config('star.version'),
            config('star.token'),
            config('star.group_id')
        );
    }
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

After adding the helpers file to composer.json, you'll need to dump the autoloader
```
composer dump-autoload
```

You can now use the global helper functions and not worry about namespaces/imports.
```php
$user = auth()->user();
$id = auth()->id();
$group = auth()->group();
$role = auth()->role();
$permissions = auth()->permissions();
$context = auth()->context();

$apiResponse = api()->get('/users/me', [ 'include' => 'groups' ]);
```

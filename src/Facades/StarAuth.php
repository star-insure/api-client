<?php

namespace StarInsure\Api\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * An instance of the auth app client
 *
 * @method @static call(string $method, string $uri, array $data = [])
 * @method @static get(string $endpoint, array $data = [])
 * @method @static post(string $endpoint, array $data = [])
 */
class StarAuth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'star-auth';
    }
}

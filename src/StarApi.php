<?php


namespace StarInsure\Api;

use Illuminate\Support\Facades\Http;

class StarApi
{
    private $apiUrl;
    private $client;

    /**
     * Constructor for an API instance
     *
     * @param string $auth_type (Either "app" or "user")
     * @param string $version ("v1")
     */
    public function __construct(string $auth_strategy, string $version = '')
    {
        // Define our API's URL
        $this->apiUrl = config('star-api.url') . '/api/' . $version ?? config('star-api.version');

        // We can interact either as an authenticated user, or as an application itself
        $token = $auth_strategy === 'app' ? config('star-api.token') : session('access_token');

        // Set the default headers for our API
        $this->client = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->withToken($token);
    }

    /**
     * Handles any call to the API
     *
     * @param string $method (GET, POST, PUT, DELETE)
     * @param string $endpoint (The endpoint to call, e.g. "/users")
     * @param array $data (The data to send to the endpoint)
     * @return mixed (json response)
     */
    public function call(string $method = 'GET', string $endpoint, array $data = [])
    {
        // Always prefix endpoints with a slash
        $url = $this->apiUrl . '/' . trim($endpoint, '/');

        // Convert the supplied method to a method that exists on the HTTP client
        $method = match ($method) {
            'GET' => 'get',
            'POST' => 'post',
            'PUT' => 'put',
            'DELETE' => 'delete',
        };

        // Make the request
        return $this->client->$method($url, $data)->json();
    }

    /**
     * GET requests wrapper
     *
     * @param string $endpoint (ModelName or custom endpoint)
     * @param array $data (Query strings as an array)
     * @return mixed (json response)
     */
    public function get(string $endpoint, array $data = [])
    {
        return $this->call('GET', $endpoint, $data);
    }

    /**
     * POST requests wrapper
     *
     * @param string $endpoint (ModelName or custom endpoint)
     * @param array $data (An array of key/value pairs matching the model's db columns)
     * @return mixed (json response)
     */
    public function post(string $endpoint, array $data = [])
    {
        return $this->call('POST', $endpoint, $data);
    }

    /**
     * PUT requests wrapper
     *
     * @param string $endpoint (ModelName or custom endpoint)
     * @param string $data (An array of key/value pairs matching the model's db columns)
     * @return mixed (json response)
     */
    public function put(string $endpoint, array $data = [])
    {
        return $this->call('PUT', $endpoint, $data);
    }

    /**
     * DELETE requests wrapper
     *
     * @param string $endpoint (ModelName or custom endpoint)
     * @return mixed (json response)
     */
    public function del(string $endpoint)
    {
        return $this->call('DELETE', $endpoint);
    }
}

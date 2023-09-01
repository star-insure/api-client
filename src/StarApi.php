<?php


namespace StarInsure\Api;

use Illuminate\Support\Facades\Http;

class StarApi
{
    private $apiUrl;
    private $client;
    private $timeout = 10;

    /**
     * Constructor for an API instance
     *
     * @param string $auth_type (Either "app" or "user")
     * @param string $version ("v1")
     * @param string|null $apiTokenOverride ("JWT")
     * @param string|int|null $groupIdOverride (2)
     */
    public function __construct(string $auth_strategy, string $version = '', string|null $apiTokenOverride = null, int|null $groupIdOverride = null)
    {
        // Define our API's URL
        $this->apiUrl = config('star.api_url') . '/api/' . $version ?? config('star.version');

        // We can interact either as an authenticated user, or as an application itself
        // We first look for a token in the session (user), then manual override (queued job), and lastly config (app env variable)
        $token = session('access_token') ?? $apiTokenOverride ?? config('star.token');

        // If we're going ahead with a token from the session, we're interacting as a user
        $auth_strategy = session('access_token') ? 'user' : $auth_strategy;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        /**
         * Conditionally attach the group ID as a header
         * This will define permissions and filter results in the API
         * Apps with the auth strategy set to "user" will have this in their session
         * Apps with the auth strategy set to "app" will have this as an environment variable
         */
        if ($auth_strategy === 'user') {
            if (session('group_id')) {
                $headers['X-Group-Id'] = session('group_id');
            }
        }

        if ($auth_strategy === 'app') {
            $headers['X-Group-Id'] = config('star.group_id', '2');
        }

        // If a groupId Override was passed in, we'll use that instead of the default
        if ($groupIdOverride) {
            $headers['X-Group-Id'] = $groupIdOverride;
        }

        // Set the default headers for our API
        $this->client = Http::withHeaders($headers)
            ->withToken($token)
            ->timeout($this->timeout);
    }

    /**
     * Handles any call to the API
     *
     * @param string $method (GET, POST, PUT, DELETE)
     * @param string $endpoint (The endpoint to call, e.g. "/users")
     * @param array $data (The data to send to the endpoint)
     * @return mixed (json response)
     */
    public function call(string $method, string $endpoint, array $data = [])
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
        $res = $this->client
            ->timeout($this->timeout)
            ->$method($url, $data);

        // Body may not exist for empty content responses (e.g. on DELETE requests)
        $body = $res->json() ?? [];

        // Don't attach any additional headers if the API request was proxied
        if ($res->header('X-Proxied')) {
            return $body;
        }

        // Return a JSON response, along with the status code and OK status
        return [...$body, 'status' => $res->status(), 'ok' => $res->successful()];
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

    /**
     * Set the timeout for the API request
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }
}

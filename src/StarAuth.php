<?php

namespace StarInsure\Api;

use Illuminate\Support\Facades\Http;

class StarAuth
{
    protected $baseUrl;
    protected $client;

    public function __construct(string $url = '', string $token = '')
    {
        $this->baseUrl = $url ?? config('star-auth.url');
        $this->client = Http::withToken($token);
    }

    /**
     * Handles any call to the auth app
     *
     * @param string $method (GET, POST, PUT, DELETE)
     * @param string $endpoint (The endpoint to call, e.g. "/users")
     * @param array $data (The data to send to the endpoint)
     * @return mixed (json response)
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function call(string $method = 'GET', string $endpoint, array $data = [])
    {
        // Always prefix endpoints with a slash
        $url = $this->baseUrl . '/' . trim($endpoint, '/');

        // Convert the supplied method to a method that exists on the HTTP client
        $method = match ($method) {
            'GET' => 'get',
            'POST' => 'post',
            'PUT' => 'put',
            'DELETE' => 'delete',
        };

        // Make the request
        $res = $this->client->$method($url, $data);

        if ($res->failed()) {
            throw new \Illuminate\Auth\AuthenticationException();
        }

        // Return the JSON response
        return $res->json();
    }

    /**
     * GET request wrapper
     *
     * @param string $endpoint
     * @param array $data
     */
    public function get(string $endpoint, array $data = [])
    {
        return $this->call('GET', $endpoint, $data);
    }

    /**
     * POST request wrapper
     *
     * @param string $endpoint
     * @param array $data
     */
    public function post(string $endpoint, array $data = [])
    {
        return $this->call('POST', $endpoint, $data);
    }
}

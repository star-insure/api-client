<?php

namespace StarInsure\Api\Http\Service;

class UserMemoizationService
{
    private $memoizedData;

    public function getData(?string $token = null, ?string $apiUrl = null, ?bool $bypass = false)
    {
        // Check if the data is already memoized – or if we're bypassing
        if (isset($this->memoizedData) && !$bypass) {
            return $this->memoizedData;
        }

        if (! $token || ! $apiUrl) {
            return null;
        }

        $data = function () use ($token, $apiUrl) {
            $res = \Illuminate\Support\Facades\Http::withHeaders([
                'Accept'           => 'application/json',
                'Authorization'    => 'Bearer '.$token,
                'X-Impersonate-Id' => session('impersonate_id'),
            ])->get("{$apiUrl}/users/me", [
                'include' => 'groups,groups.role,groups.role.permissions',
            ]);

            if (! $res->successful()) {
                session()->forget('access_token');

                return null;
            }

            $user = $res->json('data');

            return $user;
        };

        // Run the function and memoize the result
        $this->memoizedData = $data();

        return $this->memoizedData;
    }
}

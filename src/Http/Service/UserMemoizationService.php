<?php

namespace StarInsure\Api\Http\Service;

class UserMemoizationService
{
    private $memoizedData;

    public function getData($token, $apiUrl)
    {
        // Check if the data is already memoized
        if (!isset($this->memoizedData)) {

            $data = function () use ($token, $apiUrl) {
                $res = \Illuminate\Support\Facades\Http::withHeaders([
                    'Accept'           => 'application/json',
                    'Authorization'    => 'Bearer '.$token,
                    'X-Impersonate-Id' => session('impersonate_id'),
                ])->get("{$apiUrl}/users/me", [
                    'include' => 'groups,groups.role,groups.role.permissions',
                ]);

                if (!$res->successful()) {
                    session()->forget('access_token');
                    return null;
                }

                $user = $res->json('data');
                return $user;
            };

            $this->memoizedData = $data();
        }

        return $this->memoizedData;
    }

    public function clearMemoizedData()
    {
        // Clear the memoized data
        $this->memoizedData = null;
    }
}

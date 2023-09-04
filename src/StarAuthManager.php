<?php

namespace StarInsure\Api;

class StarAuthManager extends \Illuminate\Auth\AuthManager
{
    public function __construct(
        $app,
        protected ?string $apiUrl = null,
    ) {
        $this->apiUrl ??= config('star.api_url') . '/api/' . config('star.version');

        parent::__construct($app);
    }

    /**
     * The authenticated user
     */
    public function user()
    {
        return $this->useRequestCache('user', function () {
            $token = session('access_token');

            // Hit the API to get the user
            $res = \Illuminate\Support\Facades\Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ])->get("{$this->apiUrl}/api/v1/users/me", [
                'include' => 'groups,groups.role,groups.role.permissions',
            ]);

            if (! $res->successful()) {
                session()->forget('access_token');

                return null;
            }

            $user = $res->json('data');

            return $user;
        });
    }

    /**
     * An extension to the Laravel Auth::check() method that also
     * checks if the user is authenticated within the API
     */
    public function check(): bool
    {
        if (! session()->has('access_token')) {
            return false;
        }

        return $this->user() !== null;
    }

    /**
     * The groups that the authenticated user belongs to
     */
    public function groups()
    {
        return $this->useRequestCache('groups', function () {
            if ($user = $this->user()) {
                if (! array_key_exists('groups', $user)) {
                    $user = $this->user();
                }

                return collect($user['groups']);
            }

            return collect([]);
        });
    }

    /**
     * The current group the authenticated user is acting within
     */
    public function group()
    {
        return $this->useRequestCache('group', function () {
            if ($groupId = session('group_id')) {
                return $this->groups()->firstWhere('id', $groupId);
            }

            $groups = $this->groups();

            if (count($groups) > 0) {
                $defaultGroup = $groups->filter(function ($group) {
                    if (\str($group['name'])->lower()->contains('administrator')) {
                        return true;
                    }

                    if (\str($group['name'])->lower()->contains('staff')) {
                        return true;
                    }
                })->first();

                if ($defaultGroup) {
                    session(['group_id' => $defaultGroup['id']]);

                    return $defaultGroup;
                }

                // Fall back to the first group (most brokers and agents only have one anyway)
                session(['group_id' => $groups[0]['id']]);

                return $groups[0];
            }

            return null;
        });
    }

    /**
     * Get the current group's ID
     */
    public function groupId()
    {
        return $this->useRequestCache('groupId', function () {
            if ($group = $this->group()) {
                return $group['id'];
            }

            return null;
        });
    }

    /**
     * Get the current group's code
     */
    public function groupCode()
    {
        return $this->useRequestCache('groupCode', function () {
            if ($group = $this->group()) {
                return $group['code'];
            }

            return null;
        });
    }

    /**
     * The role for the current group
     */
    public function role()
    {
        return $this->useRequestCache('role', function () {
            if ($group = $this->group()) {
                return $group['role'];
            }

            return null;
        });
    }

    /**
     * The permissions that apply to the current group
     */
    public function permissions()
    {
        return $this->useRequestCache('permissions', function () {
            if ($group = $this->group()) {
                return collect($group['role']['permissions'] ?? [])->map(fn ($p) => $p['name']);
            }

            return collect([]);
        });
    }

    /**
     * Get the current group's user context
     */
    public function context()
    {
        return $this->useRequestCache('context', function () {
            if ($group = $this->group()) {
                $role = $group['role'];

                if (array_key_exists('context', $role)) {
                    return $role['context'];
                }

                $roleName = $role['name'];

                if (str_contains($roleName, 'broker')) {
                    return 'broker';
                }

                if (str_contains($roleName, 'agent')) {
                    return 'agent';
                }

                if (str_contains($roleName, 'admin')) {
                    return 'administrator';
                }

                if (str_contains($roleName, 'staff')) {
                    return 'staff';
                }

                if (str_contains($roleName, 'security')) {
                    return 'security';
                }

                return 'customer';
            }
        });
    }

    /**
     * Get the value from the request cache, or run the function and cache the result
     */
    public function useRequestCache(string $key, callable $func)
    {
        if ($value = request()->get($key)) {
            return $value;
        }

        $value = $func();

        request()->merge([$key => $value]);

        return $value;
    }
}

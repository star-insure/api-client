<?php

namespace StarInsure\Api;

class StarAuthManager extends \Illuminate\Auth\AuthManager
{
    protected $cache = [];

    public function __construct(
        $app,
        protected ?string $apiUrl = null,
        protected ?string $apiToken = null,
    ) {
        $this->apiUrl ??= config('star.api_url').'/api/'.config('star.version');
        $this->apiToken = $apiToken ?? session('access_token') ?? request()->bearerToken();

        parent::__construct($app);
    }

    /**
     * The authenticated user
     */
    public function user(?bool $bypassCache = false)
    {
        return $this->useRequestCache('user', function () {
            $token = $this->apiToken;

            // Hit the API to get the user
            $res = \Illuminate\Support\Facades\Http::withHeaders([
                'Accept'           => 'application/json',
                'Authorization'    => 'Bearer '.$token,
                'X-Impersonate-Id' => session('impersonate_id'),
            ])->get("{$this->apiUrl}/users/me", [
                'include' => 'groups,groups.role,groups.role.permissions',
            ]);

            if (! $res->successful()) {
                session()->forget('access_token');

                return;
            }

            $user = $res->json('data');

            return $user;
        }, $bypassCache);
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
     * The authenticated user's ID
     */
    public function id()
    {
        return $this->user()['id'] ?? null;
    }

    /**
     * The groups that the authenticated user belongs to
     */
    public function groups()
    {
        if ($user = $this->user()) {
            if (! array_key_exists('groups', $user)) {
                $user = $this->user(bypassCache: true);
            }

            return collect($user['groups']);
        }

        return collect([]);
    }

    /**
     * The current group the authenticated user is acting within
     */
    public function group()
    {
        $groups = $this->groups();

        if (($groupId = session('group_id')) && $group = $groups->firstWhere('id', $groupId)) {
            return $group;
        }

        if (count($groups) > 0) {
            $staffGroups = $groups->filter(fn ($group) => $group['role']['context'] === 'staff');

            if ($staffGroups->isNotEmpty()) {
                // We have an arbitrary priority with our groups, which we'll use to get our default group
                $staffGroupPriorityByName = collect([
                    'Administrators',
                    'Staff - Executive',
                    'Staff - IT',
                    'Staff - Managers',
                    'Staff - Accounts',
                    'Staff - Key Account Managers',
                    'Staff - Claims',
                    'Staff - Direct Sales',
                    'Staff - Assessing',
                    'Staff - Processing',
                ]);

                $staffGroup = $staffGroupPriorityByName->reduce(function ($selectedGroup, $groupName) use ($staffGroups) {
                    // Return early if we've already found a match
                    if ($selectedGroup) {
                        return $selectedGroup;
                    }

                    // Use the first group in our priority list
                    if ($match = $staffGroups->firstWhere(fn (array $group) => $group['name'] === $groupName)) {
                        return $match;
                    }
                }, null);

                // If we don't have one of the "priority" staff groups, just use the first one we do have
                if (! $staffGroup) {
                    $staffGroup = $staffGroups->first();
                }

                // Save our group ID in the session so we can skip this logic next time
                session(['group_id' => $staffGroup['id']]);

                return $staffGroup;
            }

            // Fall back to the first group (most brokers and agents only have one anyway)
            session(['group_id' => $groups[0]['id']]);

            return $groups[0];
        }
    }

    /**
     * Get the current group's ID
     */
    public function groupId()
    {
        if ($group = $this->group()) {
            return $group['id'];
        }
    }

    /**
     * Get the current group's code
     */
    public function groupCode()
    {
        return $this->group()['code'] ?? null;
    }

    /**
     * The role for the current group
     */
    public function role()
    {
        return $this->group()['role'] ?? null;
    }

    /**
     * The permissions this user inherits from all groups and roles
     */
    public function permissions()
    {
        return collect($this->user()['permissions'] ?? []);
    }

    /**
     * Get the current group's user context
     */
    public function context()
    {
        return $this->group()['role']['context'] ?? 'customer';
    }

    /**
     * Check if the user is authorized to perform the specified ability
     */
    public function can(?string $ability = ''): bool
    {
        if (! $ability) {
            return true;
        }

        return $this->permissions()->contains($ability);
    }

    /**
     * Check if the user is NOT authorized to perform the specified ability
     */
    public function cannot(?string $ability = ''): bool
    {
        if (! $ability) {
            return false;
        }

        return $this->permissions()->doesntContain($ability);
    }

    /**
     * Get the value from the request cache, or run the function and cache the result
     */
    public function useRequestCache(string $key, callable $func, ?bool $bypass = false)
    {
        if ($bypass) {
            return $func();
        }

        if ($value = request()->get($key)) {
            return $value;
        }

        $value = $func();

        request()->merge([$key => $value]);

        return $value;
    }
}

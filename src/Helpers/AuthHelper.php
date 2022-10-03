<?php

namespace StarInsure\Api\Helpers;

class AuthHelper
{
    /**
     * The authenticated user
     */
    public function user()
    {
        $api = new \StarInsure\Api\StarApi(
            config('star-api.auth_strategy'),
            config('star-api.version')
        );

        $token = session('access_token');

        return cache()->remember("user:{$token}", now()->addSeconds(config('star-auth.cache_user', 5)), function () use ($api) {
            $res = $api->get('users/me', [
                'include' => 'groups,groups.role,groups.role.permissions',
            ]);

            if (key_exists('data', $res)) {
                return $res['data'];
            }

            session()->forget('access_token');
            return null;
        });
    }

    /**
     * The ID of the authenticated user
     */
    public function id()
    {
        if ($user = $this->user()) {
            return $user['id'];
        }

        return null;
    }

    /**
     * The groups that the authenticated user belongs to
     */
    public function groups()
    {
        if ($user = $this->user()) {
            if (! key_exists('groups', $user)) {
                // If the "groups" key doesn't exist, the session has been invalidated. We'll need to refresh the user.
                cache()->forget("user:" . session('access_token'));
                $user = $this->user();
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
        if ($groupId = session('group_id')) {
            return $this->groups()->firstWhere('id', $groupId);
        }

        $groups = $this->groups();

        if (count($groups) > 0) {
            // If we have an administrator group, that should take priority
            if ($adminGroup = $groups->firstWhere('role.name', 'administrator')) {
                session(['group_id' => $adminGroup['id']]);
                return $adminGroup;
            };

            // Second priority is staff
            if ($staffGroup = $groups->firstWhere('role.name', 'staff')) {
                session(['group_id' => $staffGroup['id']]);
                return $staffGroup;
            };

            // Fall back to the first group (most brokers and agents only have one anyway)
            session(['group_id' => $groups[0]['id']]);
            return $groups[0];
        }

        return null;
    }

    /**
     * Get the current group's ID
     */
    public function groupId()
    {
        if ($group = $this->group()) {
            return $group['id'];
        }

        return null;
    }

    /**
     * Get the current group's code
     */
    public function groupCode()
    {
        if ($group = $this->group()) {
            return $group['code'];
        }

        return null;
    }

    /**
     * The permissions that apply to the current group
     */
    public function permissions()
    {
        if ($group = $this->group()) {
            return collect($group['role']['permissions'] ?? [])->map(fn ($p) => $p['name']);
        }

        return collect([]);
    }

    /**
     * Get the current group's user context
     */
    public function context()
    {
        if ($group = $this->group()) {
            $role = $group['role'];

            if (key_exists('context', $role)) {
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
    }
}

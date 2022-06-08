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

        $res = $api->get('users/me', [
            'include' => 'groups',
        ]);

        if (key_exists('data', $res)) {
            return $res['data'];
        }

        return null;
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
            return collect($user['groups']);
        }

        return collect([]);
    }

    /**
     * The current group the authenticated user is acting within
     */
    public function group()
    {
        if ($groupId = session('groupId')) {
            return $this->groups()->firstWhere('id', $groupId);
        }

        $groups = $this->groups();

        if (count($groups) > 0) {
            // If we have an administrator group, that should take priority
            if ($adminGroup = $groups->firstWhere('role.name', 'administrator')) {
                session(['groupId' => $adminGroup['id']]);
                return $adminGroup;
            };

            // Second priority is staff
            if ($staffGroup = $groups->firstWhere('role.name', 'staff')) {
                session(['groupId' => $staffGroup['id']]);
                return $staffGroup;
            };

            // Fall back to the first group (most brokers and agents only have one anyway)
            session(['groupId' => $groups[0]['id']]);
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
            return collect($group['permissions'])->map(fn ($p) => $p['name']);
        }

        return collect([]);
    }

    /**
     * Get the current group's audience
     */
    public function audience()
    {
        if ($group = $this->group()) {
            return $group['role']['name'];
        }

        return '';
    }
}

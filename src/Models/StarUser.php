<?php

namespace StarInsure\Api\Models;

use ArrayAccess;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class StarUser extends Authenticatable implements ArrayAccess, Arrayable
{
    protected $guarded = [];

    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->setRawAttributes($data);
        }
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->attributes['id'];
    }

    public function get(?string $key = null)
    {
        if (! $key) {
            return null;
        }

        return $this->attributes[$key] ?? null;
    }

    public function set(string $key, mixed $value)
    {
        $this['attributes'][$key] = $value;

        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }



    /**
     * The permissions this user inherits from all groups and roles
     */
    public function permissions(): Collection
    {
        return collect($this->get('permissions') ?? []);
    }


    /**
     * Check if the user is authorized to perform the specified ability
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($abilities, $arguments = [])
    {
        if (! $abilities) {
            return true;
        }

        if (is_array($abilities)) {
            $can = true;

            foreach ($abilities as $ability) {
                if (! $this->can($ability)) {
                    $can = false;
                    break;
                }
            }

            return $can;
        }

        return $this->permissions()->contains($abilities);
    }

    /**
     * Check if the user is NOT authorized to perform the specified ability
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cannot($abilities, $arguments = [])
    {
        if (! $abilities) {
            return false;
        }

        return $this->permissions()->doesntContain($abilities);
    }

    public function groups(): Collection
    {
        return collect($this->get('groups') ?? []);
    }

    public function isInGroup(int $groupId)
    {
        $isInGroup = false;

        // Return false straight away if there are no groups to check
        if (! array_key_exists('groups', $this->attributes)) {
            return false;
        }

        foreach ($this->get('groups') as $group) {
            if ($group['id'] === $groupId) {
                $isInGroup = true;
            }
        }

        return $isInGroup;
    }
}

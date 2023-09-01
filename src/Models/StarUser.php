<?php

namespace StarInsure\Api\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class StarUser extends Authenticatable
{
    protected $guarded = [];

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->attributes['id'];
    }
}

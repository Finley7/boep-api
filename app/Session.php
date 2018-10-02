<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $fillable = [
        'user_id', 'token', 'user_agent', 'ip_address', 'expires', 'unique_device_id', 'expired', 'auth_method'
    ];

    protected $hidden = [
        'token'
    ];

    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}

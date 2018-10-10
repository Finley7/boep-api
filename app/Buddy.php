<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Buddy extends Model
{
    public function first_user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function second_user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}

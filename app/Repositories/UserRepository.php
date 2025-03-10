<?php

namespace App\Repositories;

use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email)
    {
        // return User::with([
        //     'language',
        // ])
        //     ->where('email', $email)
        //     ->first();

        return User::withTrashed()->where('email', $email)
            ->first();
    }
}

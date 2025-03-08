<?php

namespace App\Interfaces\Repositories;

interface UserRepositoryInterface
{
    public function findByEmail(string $email);
}

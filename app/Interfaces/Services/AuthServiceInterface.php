<?php

namespace App\Interfaces\Services;

interface AuthServiceInterface
{
    public function login(object $payload);

    public function logout(object $payload);
}

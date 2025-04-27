<?php

namespace App\Interfaces\Repositories;

interface LanguageRepositoryInterface
{
    public function findByUuid(string $uuid);

    public function findAll();
}

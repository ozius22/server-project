<?php

namespace App\Repositories;

use App\Interfaces\Repositories\LanguageRepositoryInterface;
use App\Models\Language;

class LanguageRepository implements LanguageRepositoryInterface
{
    public function findByUuid(string $uuid)
    {
        return Language::where('uuid', $uuid)->first();
    }

    public function findAll()
    {
        return Language::all();
    }
}

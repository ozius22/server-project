<?php

namespace App\Interfaces\Services;

interface LanguageServiceInterface
{
    public function createLanguage(object $payload);

    public function show();
}

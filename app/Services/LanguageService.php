<?php

namespace App\Services;

use App\Http\Resources\LanguageResource;
use App\Interfaces\Repositories\LanguageRepositoryInterface;
use App\Interfaces\Services\LanguageServiceInterface;

class LanguageService implements LanguageServiceInterface
{
    private LanguageRepositoryInterface $languageRepository;

    public function __construct(
        LanguageRepositoryInterface $languageRepository
    ) {
        $this->languageRepository = $languageRepository;
    }

    public function createLanguage(object $payload)
    {
        // …
    }

    public function show()
    {
        $languages = $this->languageRepository->findAll();

        return LanguageResource::collection($languages);
    }
}

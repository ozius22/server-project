<?php

namespace App\Services;

use App\Http\Resources\CountryResource;
use App\Interfaces\Repositories\CountryRepositoryInterface;
use App\Interfaces\Services\CountryServiceInterface;

class CountryService implements CountryServiceInterface
{
    private CountryRepositoryInterface $countryRepository;

    public function __construct(
        CountryRepositoryInterface $countryRepository
    ) {
        $this->countryRepository = $countryRepository;
    }

    public function listCountry()
    {
        $collection = $this->countryRepository->listAll();

        return CountryResource::collection($collection);
    }

    public function createCountry(object $payload)
    {
        $model = $this->countryRepository->create($payload);

        return new CountryResource($model);
    }

    public function getCountry(string $uuid)
    {
        $model = $this->countryRepository->findByUuid($uuid);

        return new CountryResource($model);
    }

    public function updateCountry(string $uuid, object $payload)
    {
        $model = $this->countryRepository->update($uuid, $payload);

        return new CountryResource($model);
    }

    public function deleteCountry(string $uuid)
    {
        $model = $this->countryRepository->delete($uuid);

        return new CountryResource($model);
    }
}

<?php

namespace App\Repositories;

use App\Interfaces\Repositories\CountryRepositoryInterface;
use App\Models\Country;

class CountryRepository implements CountryRepositoryInterface
{
    public function listAll()
    {
        return Country::all();
    }

    public function create(object $payload)
    {
        return Country::create((array) $payload);
    }

    public function findByUuid(string $uuid)
    {
        return Country::where('uuid', $uuid)->first();
    }

    public function update(string $uuid, object $payload)
    {
        $model = Country::where('uuid', $uuid)->firstOrFail();
        $model->update((array) $payload);

        return $model;
    }

    public function delete(string $uuid)
    {
        $model = Country::where('uuid', $uuid)->firstOrFail();
        $model->delete();

        return $model;
    }
}

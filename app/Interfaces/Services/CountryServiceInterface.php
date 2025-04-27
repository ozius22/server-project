<?php

namespace App\Interfaces\Services;

interface CountryServiceInterface
{
    public function listCountry();

    public function createCountry(object $payload);

    public function getCountry(string $uuid);

    public function updateCountry(string $uuid, object $payload);

    public function deleteCountry(string $uuid);
}

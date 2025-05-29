<?php

namespace App\Interfaces\Repositories;

interface ImageRepositoryInterface
{
    public function listAll();

    public function create(object $payload);

    public function findByUuid(string $uuid);

    public function update(string $uuid, object $payload);

    public function delete(string $uuid);
}

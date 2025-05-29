<?php

namespace App\Interfaces\Services;

interface ImageServiceInterface
{
    public function listImage();

    public function createImage(object $payload);

    public function scanImage(object $payload);

    public function getImage(string $uuid);

    public function updateImage(string $uuid, object $payload);

    public function deleteImage(string $uuid);
}

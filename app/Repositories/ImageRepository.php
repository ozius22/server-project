<?php

namespace App\Repositories;

use App\Interfaces\Repositories\ImageRepositoryInterface;
use App\Models\Image;

class ImageRepository implements ImageRepositoryInterface
{
    public function listAll()
    {
        return Image::all();
    }

    public function create(object $payload)
    {
        return Image::create(get_object_vars($payload));
    }

    public function findByUuid(string $uuid)
    {
        return Image::where('uuid', $uuid)->first();
    }

    public function update(string $uuid, object $payload)
    {
        $model = Image::where('uuid', $uuid)->firstOrFail();
        $model->update(get_object_vars($payload));

        return $model;
    }

    public function delete(string $uuid)
    {
        $model = Image::where('uuid', $uuid)->firstOrFail();
        $model->delete();

        return $model;
    }
}

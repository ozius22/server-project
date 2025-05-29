<?php

namespace App\Services;

use App\Http\Resources\ImageResource;
use App\Interfaces\Repositories\ImageRepositoryInterface;
use App\Interfaces\Services\ImageServiceInterface;

class ImageService implements ImageServiceInterface
{
    private ImageRepositoryInterface $imageRepository;

    public function __construct(
        ImageRepositoryInterface $imageRepository
    ) {
        $this->imageRepository = $imageRepository;
    }

    public function listImage()
    {
        $collection = $this->imageRepository->listAll();

        return ImageResource::collection($collection);
    }

    public function createImage(object $payload)
    {
        $model = $this->imageRepository->create($payload);

        return new ImageResource($model);
    }

    public function scanImage(object $payload)
    {
        return 'scan';
    }

    public function getImage(string $uuid)
    {
        $model = $this->imageRepository->findByUuid($uuid);

        return new ImageResource($model);
    }

    public function updateImage(string $uuid, object $payload)
    {
        $model = $this->imageRepository->update($uuid, $payload);

        return new ImageResource($model);
    }

    public function deleteImage(string $uuid)
    {
        $model = $this->imageRepository->delete($uuid);

        return new ImageResource($model);
    }
}

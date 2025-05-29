<?php

namespace App\Services;

use App\Http\Resources\ImageResource;
use App\Interfaces\Repositories\ImageRepositoryInterface;
use App\Interfaces\Services\ImageServiceInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;

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
        $pythonService = 'http://127.0.0.1:5005';
        $uploadDir = storage_path('app/uploaded_faces');
        $testImagePath = storage_path('app/test_images/test.png');
        $matchedDir = storage_path('app/matched_faces');
        $downloadDir = storage_path('app/downloaded_web_images');

        foreach ([$uploadDir, $matchedDir, $downloadDir] as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        $referenceImages = [];

        foreach ($payload->file('files') as $uploadedFile) {
            $filename = uniqid('face_').'.'.$uploadedFile->getClientOriginalExtension();
            $fullPath = $uploadDir.'/'.$filename;

            $uploadedFile->move($uploadDir, $filename);

            if (! file_exists($fullPath)) {
                throw new \Exception("Failed to save uploaded image: $fullPath");
            }

            $referenceImages[] = $fullPath;
        }

        if (empty($referenceImages)) {
            throw new \Exception('No reference images uploaded.');
        }

        $client = new Client;
        $multipart = array_map(fn ($filePath) => [
            'name' => 'images',
            'contents' => fopen($filePath, 'r'),
            'filename' => basename($filePath),
        ], $referenceImages);

        $response = $client->post("$pythonService/load_references", ['multipart' => $multipart]);
        $data = json_decode($response->getBody(), true);

        if (($data['count'] ?? 0) === 0) {
            throw new \Exception('No faces recognized in uploaded reference images.');
        }

        if (! file_exists($testImagePath)) {
            throw new \Exception("Test image not found at: $testImagePath");
        }

        $matchResponse = $client->post("$pythonService/match", [
            'multipart' => [
                [
                    'name' => 'image',
                    'contents' => fopen($testImagePath, 'r'),
                    'filename' => basename($testImagePath),
                ],
            ],
        ]);

        $matchData = json_decode($matchResponse->getBody(), true);

        if ($matchData['any_match'] ?? false) {
            $matchedFilename = uniqid('match_').'_'.basename($testImagePath);
            $matchedPath = $matchedDir.'/'.$matchedFilename;

            if (! copy($testImagePath, $matchedPath)) {
                throw new \Exception("Failed to copy matched image to: $matchedPath");
            }
        }

        File::cleanDirectory($downloadDir);

        return response()->json([
            'match_result' => $matchData,
        ]);
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

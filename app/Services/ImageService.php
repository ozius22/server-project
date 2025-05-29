<?php

namespace App\Services;

use App\Http\Resources\ImageResource;
use App\Interfaces\Repositories\ImageRepositoryInterface;
use App\Interfaces\Services\ImageServiceInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

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
        ini_set('max_execution_time', 5400);

        $pythonService = 'http://127.0.0.1:5005';
        $uploadDir = storage_path('app/uploaded_faces');
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
            return Response::json(['error' => 'No faces recognized in uploaded reference images.'], 404);
        }

        $webUrl = $payload->web_url ?? null;
        if ($webUrl) {
            $matches = $this->matchImagesFromWebsite($webUrl, $client, $downloadDir, $matchedDir, $pythonService);
        } else {
            $matches = [];
        }

        File::cleanDirectory($downloadDir);
        File::cleanDirectory($uploadDir);

        return response()->json([
            'match_result' => $matches,
        ]);
    }

    protected function matchImagesFromWebsite(string $url, Client $client, string $downloadDir, string $matchedDir, string $pythonService): array
    {
        $matches = [];

        try {
            $res = $client->get($url);
            $html = (string) $res->getBody();
            $dom = new \Symfony\Component\DomCrawler\Crawler($html);
            $imgUrls = $dom->filter('img')->each(fn ($node) => $node->attr('src'));

            foreach ($imgUrls as $imgSrc) {
                if (empty($imgSrc)) {
                    continue;
                }

                $imgSrc = \GuzzleHttp\Psr7\UriResolver::resolve(
                    new \GuzzleHttp\Psr7\Uri($url),
                    new \GuzzleHttp\Psr7\Uri($imgSrc)
                );

                $imgUrl = (string) $imgSrc;
                $imgName = uniqid('webimg_').'_'.basename(parse_url($imgUrl, PHP_URL_PATH));
                $imgPath = $downloadDir.'/'.$imgName;

                try {
                    $client->get($imgUrl, ['sink' => $imgPath]);

                    $matchRes = $client->post("$pythonService/match", [
                        'multipart' => [
                            [
                                'name' => 'image',
                                'contents' => fopen($imgPath, 'r'),
                                'filename' => basename($imgPath),
                            ],
                        ],
                    ]);

                    $matchData = json_decode($matchRes->getBody(), true);
                    if ($matchData['any_match'] ?? false) {
                        $matchedPath = $matchedDir.'/'.uniqid('match_').'_'.basename($imgPath);
                        copy($imgPath, $matchedPath);
                        $matches[] = [
                            'image' => $imgUrl,
                            'saved_as' => basename($matchedPath),
                        ];
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        } catch (\Throwable $e) {
            return [['error' => 'Failed to crawl: '.$e->getMessage()]];
        }

        return $matches;
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

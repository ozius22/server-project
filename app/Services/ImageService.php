<?php

namespace App\Services;

use App\Http\Resources\ImageResource;
use App\Interfaces\Repositories\ImageRepositoryInterface;
use App\Interfaces\Services\ImageServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlObservers\CrawlObserver;

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
            is_dir($dir) || mkdir($dir, 0777, true);
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
        if (! $referenceImages) {
            throw new \Exception('No reference images uploaded.');
        }

        $client = new Client;
        $multipart = array_map(fn ($p) => [
            'name' => 'images',
            'contents' => fopen($p, 'r'),
            'filename' => basename($p),
        ], $referenceImages);

        $resp = $client->post("$pythonService/load_references", ['multipart' => $multipart]);
        $data = json_decode($resp->getBody(), true);
        if (($data['count'] ?? 0) === 0) {
            return Response::json(['error' => 'No faces recognized in uploaded reference images.'], 404);
        }

        $matches = [];
        if ($webUrl = ($payload->web_url ?? null)) {

            $imgUrls = $this->crawlImageUrls($webUrl);
            Log::info('Crawl finished — found '.count($imgUrls).' <img> URLs');

            $imgUrls = array_values(array_unique(array_filter($imgUrls)));
            if (! $imgUrls) {
                Log::warning('Stage-1 failure: crawler returned nothing');
            }

            foreach ($imgUrls as $imgUrl) {

                $imgName = uniqid('webimg_').'_'.basename(parse_url($imgUrl, PHP_URL_PATH));
                $imgPath = $downloadDir.'/'.$imgName;

                try {
                    $client->get($imgUrl, [
                        'sink' => $imgPath,
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0',
                            'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                            'Accept-Language' => 'en-US,en;q=0.8',
                        ],
                        'http_errors' => false,
                    ]);

                    if (! is_file($imgPath)) {
                        Log::debug("DL failed (no file) → $imgUrl");

                        continue;
                    }
                    Log::debug('Downloaded '.filesize($imgPath).' bytes ← '.$imgUrl);

                    $matchRes = $client->post("$pythonService/match", [
                        'multipart' => [[
                            'name' => 'image',
                            'contents' => fopen($imgPath, 'r'),
                            'filename' => $imgName,
                        ]],
                    ]);
                    $matchData = json_decode($matchRes->getBody(), true);

                    if ($matchData['any_match'] ?? false) {
                        $matchedPath = $matchedDir.'/'.uniqid('match_').'_'.$imgName;
                        copy($imgPath, $matchedPath);

                        $matches[] = [
                            'image' => $imgUrl,
                            'saved_as' => basename($matchedPath),
                        ];
                    } else {
                        Log::debug('No face match ← '.$imgUrl);
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        File::cleanDirectory($downloadDir);
        File::cleanDirectory($uploadDir);

        Log::info('Scan done — total matches: '.count($matches));

        return response()->json(['match_result' => $matches]);
    }

    public function crawlImageUrls(string $websiteUrl): array
    {
        $tracker = [];
        $client = new Client(['http_errors' => false, 'timeout' => 15]);

        $addImage = function (string $url) use (&$tracker) {
            $key = strtolower($url);
            if (! isset($tracker[$key])) {
                $tracker[$key] = [
                    'url' => $url,
                    'is_image' => true,
                    'external' => false,
                ];
            }
        };

        try {
            $html = (string) $client->get($websiteUrl, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0',
                    'Accept' => 'text/html,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.8',
                ],
            ])->getBody();

            $dom = new \Symfony\Component\DomCrawler\Crawler($html);
            foreach ($dom->filter('img')->each(fn ($n) => $n->attr('src')) as $src) {
                if (! $src) {
                    continue;
                }
                $resolved = UriResolver::resolve(new Uri($websiteUrl), new Uri($src));
                $addImage((string) $resolved);
            }
        } catch (\Throwable $e) {
        }

        $baseUri = rtrim($websiteUrl, '/');
        $parsed = parse_url($baseUri);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? parse_url($websiteUrl, PHP_URL_HOST);
        $rootHost = preg_replace('/^www\./i', '', strtolower($host));

        try {
            $robots = $client->get("$scheme://$host/robots.txt")->getBody()->getContents();
            if (preg_match_all('/^(?:Allow|Disallow):\s*(\/[^\s]*\.(?:jpe?g|png|webp|gif|bmp))/mi', $robots, $m)) {
                foreach (array_unique($m[1]) as $path) {
                    $addImage("$scheme://$host$path");
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            $xmlRaw = $client->get("$scheme://$host/sitemap.xml")->getBody()->getContents();
            if ($xml = @simplexml_load_string($xmlRaw)) {
                foreach ($xml->url ?? [] as $u) {
                    $loc = (string) $u->loc;
                    if (preg_match('/\.(jpe?g|png|webp|gif|bmp)(\?|$)/i', $loc)) {
                        $addImage($loc);
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        $observer = new class($tracker, $rootHost) extends CrawlObserver
        {
            private array $tracker;

            private string $rootHost;

            private array $seen = [];

            public function __construct(array &$tracker, string $rootHost)
            {
                $this->tracker = &$tracker;
                $this->rootHost = $rootHost;
            }

            public function crawled(UriInterface $url, ResponseInterface $res, ?UriInterface $found = null, ?string $text = null): void
            {
                $urlStr = (string) $url;
                $norm = strtolower((new Uri($urlStr))->withQuery('')->withFragment('')->getPath());
                if (isset($this->seen[$norm])) {
                    return;
                }
                $this->seen[$norm] = true;

                if (strpos($res->getHeaderLine('Content-Type'), 'text/html') === 0) {
                    $html = (string) $res->getBody();
                    $crawler = new \Symfony\Component\DomCrawler\Crawler($html);
                    $imgs = $crawler->filter('img')->each(fn ($node) => $node->attr('src'));

                    foreach ($imgs as $src) {
                        if (! $src) {
                            continue;
                        }
                        try {
                            $resolved = UriResolver::resolve($url, new Uri($src));
                            $imgUrl = (string) $resolved;
                            if (! preg_match('/\.(jpe?g|png|webp|gif|bmp)(\?|$)/i', $imgUrl)) {
                                continue;
                            }
                            $key = strtolower($imgUrl);
                            if (! isset($this->tracker[$key])) {
                                $this->tracker[$key] = [
                                    'url' => $imgUrl,
                                    'is_image' => true,
                                    'external' => preg_replace('/^www\./i', '', strtolower($url->getHost() ?? '')) !== $this->rootHost,
                                ];
                            }
                        } catch (\Throwable $e) {
                        }
                    }
                }
            }
        };

        $headerPool = [
            [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/125 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
            [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7; rv:124.0) Gecko/20100101 Firefox/124.0',
                'Accept-Language' => 'en-GB,en;q=0.8',
            ],
            [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/125 Edg/125',
                'Accept-Language' => 'en-US,en;q=0.7',
            ],
        ];

        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest(function (RequestInterface $req) use ($headerPool) {
            foreach ($headerPool[array_rand($headerPool)] + [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
            ] as $h => $v) {
                $req = $req->withHeader($h, $v);
            }

            return $req;
        }));
        $stack->push(Middleware::retry(
            fn ($tries, $req, $res = null) => $tries < 3 && (! $res || in_array($res->getStatusCode(), [429, 500, 502, 503, 504])),
            fn ($tries) => pow(2, $tries) * 500 + random_int(0, 300)
        ));

        Crawler::create([
            'handler' => $stack,
            'http_errors' => false,
            'allow_redirects' => true,
            'timeout' => 15,
            'connect_timeout' => 10,
            'cookies' => new \GuzzleHttp\Cookie\CookieJar,
        ])
            ->setCrawlObserver($observer)
            ->setCrawlProfile(new class($websiteUrl) extends \Spatie\Crawler\CrawlProfiles\CrawlProfile
            {
                private string $baseHost;

                public function __construct(string $base)
                {
                    $this->baseHost = preg_replace('/^www\./i', '', strtolower(parse_url($base, PHP_URL_HOST) ?: ''));
                }

                public function shouldCrawl(UriInterface $url): bool
                {
                    $host = preg_replace('/^www\./i', '', strtolower($url->getHost() ?? ''));
                    $path = $url->getPath() ?? '';

                    return $host === $this->baseHost
                        && ! preg_match('/\.(?:css|js|woff2?|ttf|pdf|zip|mp4|svg)$/i', $path);
                }
            })
            ->setDelayBetweenRequests(100)
            ->setTotalCrawlLimit(500)
            ->startCrawling($websiteUrl);

        return array_values(array_map(
            fn ($item) => $item['url'],
            array_filter($tracker, fn ($i) => $i['is_image'] ?? false)
        ));
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeApiScaffoldCommand extends Command
{
    /**
     * Stub for service interface
     *
     * @var string
     */
    protected const SERVICE_INTERFACE_STUB = <<<'STUB'
    <?php
    
    namespace App\Interfaces\Services;
    
    interface DummyServiceInterface
    {
        public function listDummy();
        public function createDummy(object $payload);
        public function getDummy(string $uuid);
        public function updateDummy(string $uuid, object $payload);
        public function deleteDummy(string $uuid);
    }
    STUB;

    /**
     * Stub for service implementation
     *
     * @var string
     */
    protected const SERVICE_STUB = <<<'STUB'
    <?php
    
    namespace App\Services;
    
    use App\Interfaces\Services\DummyServiceInterface;
    use App\Interfaces\Repositories\DummyRepositoryInterface;
    use App\Http\Resources\DummyResource;
    
    class DummyService implements DummyServiceInterface
    {
        private DummyRepositoryInterface $dummyRepository;
    
        public function __construct(
            DummyRepositoryInterface $dummyRepository
        ) {
            $this->dummyRepository = $dummyRepository;
        }
    
        public function listDummy()
        {
            $collection = $this->dummyRepository->listAll();
            return DummyResource::collection($collection);
        }
    
        public function createDummy(object $payload): DummyResource
        {
            $model = $this->dummyRepository->create($payload);
            return new DummyResource($model);
        }
    
        public function getDummy(string $uuid): DummyResource
        {
            $model = $this->dummyRepository->findByUuid($uuid);
            return new DummyResource($model);
        }
    
        public function updateDummy(string $uuid, object $payload): DummyResource
        {
            $model = $this->dummyRepository->update($uuid, $payload);
            return new DummyResource($model);
        }
    
        public function deleteDummy(string $uuid): DummyResource
        {
            $model = $this->dummyRepository->delete($uuid);
            return new DummyResource($model);
        }
    }
    STUB;

    /**
     * Stub for repository interface
     *
     * @var string
     */
    protected const REPOSITORY_INTERFACE_STUB = <<<'STUB'
    <?php
    
    namespace App\Interfaces\Repositories;
    
    interface DummyRepositoryInterface
    {
        public function listAll();
        public function create(object $payload);
        public function findByUuid(string $uuid);
        public function update(string $uuid, object $payload);
        public function delete(string $uuid);
    }
    STUB;

    /**
     * Stub for repository implementation
     *
     * @var string
     */
    protected const REPOSITORY_STUB = <<<'STUB'
    <?php
    
    namespace App\Repositories;
    
    use App\Interfaces\Repositories\DummyRepositoryInterface;
    use App\Models\Dummy;
    
    class DummyRepository implements DummyRepositoryInterface
    {
        public function listAll()
        {
            return Dummy::all();
        }
    
        public function create(object $payload)
        {
            return Dummy::create((array) $payload);
        }
    
        public function findByUuid(string $uuid)
        {
            return Dummy::where('uuid', $uuid)->first();
        }
    
        public function update(string $uuid, object $payload)
        {
            $model = Dummy::where('uuid', $uuid)->firstOrFail();
            $model->update((array) $payload);
            return $model;
        }
    
        public function delete(string $uuid)
        {
            $model = Dummy::where('uuid', $uuid)->firstOrFail();
            $model->delete();
            return $model;
        }
    }
    STUB;

    protected $signature = 'make:api-scaffold 
                            {name : The base name of the resource (e.g. User, ConsentSession)} 
                            {--force : Overwrite existing files}';

    protected $description = 'Scaffold a full API (controller, request, resource, service & repo stubs)';

    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    public function handle()
    {
        $base = Str::studly($this->argument('name'));

        $controller = "{$base}Controller";
        $request = "{$base}StoreRequest";
        $resource = "{$base}Resource";

        $this->info("Starting API scaffold for {$base}…");

        $this->call('make:controller', [
            'name' => $controller,
            '--api' => true,
            '--force' => $this->option('force'),
        ]);

        $this->call('make:request', [
            'name' => $request,
            '--force' => $this->option('force'),
        ]);

        $this->call('make:resource', [
            'name' => $resource,
            '--force' => $this->option('force'),
        ]);

        $this->createServiceInterface($base);
        $this->createService($base);
        $this->createRepositoryInterface($base);
        $this->createRepository($base);
        $this->addControllerConstructor($base);

        $this->info('Scaffold complete.');
    }

    protected function createServiceInterface(string $base)
    {
        $name = "{$base}ServiceInterface";
        $subPath = config('api-scaffold.paths.service_interface');
        $targetDir = app_path($subPath);
        $targetFile = "{$targetDir}/{$name}.php";

        $this->files->ensureDirectoryExists($targetDir);

        $stub = static::SERVICE_INTERFACE_STUB;
        $stub = str_replace('DummyServiceInterface', $name, $stub);
        $stub = str_replace('Dummy', $base, $stub);

        $this->files->put($targetFile, $stub);
        $this->info("Created service interface: {$targetFile}");
    }

    protected function createService(string $base)
    {
        $class = "{$base}Service";
        $interface = "{$base}ServiceInterface";
        $repoInterface = "{$base}RepositoryInterface";
        $repoVariable = lcfirst($base).'Repository';

        $subPath = config('api-scaffold.paths.service');
        $targetDir = app_path($subPath);
        $targetFile = "{$targetDir}/{$class}.php";

        $this->files->ensureDirectoryExists($targetDir);

        $stub = static::SERVICE_STUB;

        $stub = str_replace('DummyServiceInterface', $interface, $stub);
        $stub = str_replace('DummyService', $class, $stub);
        $stub = str_replace('DummyRepositoryInterface', $repoInterface, $stub);

        $stub = str_replace('dummyRepository', $repoVariable, $stub);
        $stub = str_replace('Dummy', $base, $stub);

        $this->files->put($targetFile, $stub);
        $this->info("Created service: {$targetFile}");
    }

    protected function createRepositoryInterface(string $base)
    {
        $name = "{$base}RepositoryInterface";
        $subPath = config('api-scaffold.paths.repository_interface');
        $targetDir = app_path($subPath);
        $targetFile = "{$targetDir}/{$name}.php";

        $this->files->ensureDirectoryExists($targetDir);

        $stub = static::REPOSITORY_INTERFACE_STUB;
        $stub = str_replace('DummyRepositoryInterface', $name, $stub);
        $stub = str_replace('Dummy', $base, $stub);

        $this->files->put($targetFile, $stub);
        $this->info("Created repository interface: {$targetFile}");
    }

    protected function createRepository(string $base)
    {
        $class = "{$base}Repository";
        $interface = "{$base}RepositoryInterface";
        $subPath = config('api-scaffold.paths.repository');
        $targetDir = app_path($subPath);
        $targetFile = "{$targetDir}/{$class}.php";

        $this->files->ensureDirectoryExists($targetDir);

        $stub = static::REPOSITORY_STUB;
        $stub = str_replace('DummyRepositoryInterface', $interface, $stub);
        $stub = str_replace('DummyRepository', $class, $stub);
        $stub = str_replace('Dummy', $base, $stub);

        $this->files->put($targetFile, $stub);
        $this->info("Created repository: {$targetFile}");
    }

    protected function addControllerConstructor(string $base)
    {
        $controller = "{$base}Controller";
        $controllerPath = app_path("Http/Controllers/{$controller}.php");

        if (! $this->files->exists($controllerPath)) {
            return;
        }

        $content = $this->files->get($controllerPath);
        $lcBase = lcfirst($base);
        $serviceInterface = "App\\Interfaces\\Services\\{$base}ServiceInterface";

        if (! Str::contains($content, $serviceInterface)) {
            $content = preg_replace(
                '/namespace App\\\\Http\\\\Controllers;(\\r?\\n)/',
                'namespace App\Http\Controllers;$1use '.$serviceInterface.';$1',
                $content
            );
        }

        $property = "private {$base}ServiceInterface \${$lcBase}Service;\n\n";
        if (! Str::contains($content, $property)) {
            $content = preg_replace(
                '/class '.$controller.' extends Controller\s*\{\r?\n/',
                "class {$controller} extends Controller\n{\n{$property}",
                $content
            );
        }

        $ctor = <<<PHP
        public function __construct({$base}ServiceInterface \${$lcBase}Service)
        {
            \$this->{$lcBase}Service = \${$lcBase}Service;
        }\n\n
        PHP;

        if (! Str::contains($content, 'public function __construct')) {
            $escapedProperty = preg_quote($property, '/');

            $content = preg_replace(
                "/({$escapedProperty})/",
                "$1{$ctor}",
                $content,
                1
            );
        }

        $this->files->put($controllerPath, $content);
        $this->info("Injected constructor into {$controllerPath}");
    }
}

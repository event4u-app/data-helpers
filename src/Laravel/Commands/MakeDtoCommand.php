<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Artisan command to generate SimpleDTO classes.
 *
 * Usage:
 *   php artisan make:dto UserDTO
 *   php artisan make:dto UserDTO --validation
 *   php artisan make:dto UserDTO --collection
 *   php artisan make:dto UserDTO --resource
 */
class MakeDtoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:dto {name : The name of the DTO class}
                            {--validation : Add validation attributes}
                            {--collection : Add DataCollection support}
                            {--resource : Generate a resource DTO with common fields}
                            {--force : Overwrite existing file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new SimpleDTO class';

    /** Execute the console command. */
    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $validation = $this->option('validation');
        $collection = $this->option('collection');
        $resource = $this->option('resource');
        $force = $this->option('force');

        // Ensure name ends with DTO
        if (!Str::endsWith($name, 'DTO')) {
            $name .= 'DTO';
        }

        // Get path
        $path = $this->getPath($name);

        // Check if file exists
        if ($files->exists($path) && !$force) {
            $this->error("DTO [{$name}] already exists!");
            $this->info('Use --force to overwrite.');

            return self::FAILURE;
        }

        // Create directory if needed
        $directory = dirname($path);
        if (!$files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, true);
        }

        // Generate content
        $content = $this->generateContent($name, $validation, $collection, $resource);

        // Write file
        $files->put($path, $content);

        $this->info("DTO [{$name}] created successfully.");
        $this->info("Location: {$path}");

        return self::SUCCESS;
    }

    /** Get the destination path for the DTO. */
    protected function getPath(string $name): string
    {
        return $this->laravel->basePath('app') . '/DTOs/' . $name . '.php';
    }

    /** Get the root namespace for the application. */
    protected function rootNamespace(): string
    {
        return $this->laravel->getNamespace();
    }

    /** Generate the DTO content. */
    protected function generateContent(string $name, bool $validation, bool $collection, bool $resource): string
    {
        $namespace = $this->rootNamespace() . 'DTOs';
        $className = class_basename($name);

        $uses = [
            'use event4u\DataHelpers\SimpleDTO;',
        ];

        if ($validation) {
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\Attributes\Email;';
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\Attributes\Required;';
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\Attributes\Min;';
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\Attributes\Max;';
        }

        if ($collection) {
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;';
            $uses[] = 'use event4u\DataHelpers\SimpleDTO\DataCollection;';
        }

        $usesStr = implode("\n", $uses);

        if ($resource) {
            return $this->generateResourceDto($namespace, $className, $usesStr, $validation, $collection);
        }

        return $this->generateBasicDto($namespace, $className, $usesStr, $validation, $collection);
    }

    /** Generate a basic DTO. */
    protected function generateBasicDto(
        string $namespace,
        string $className,
        string $uses,
        bool $validation,
        bool $collection
    ): string
    {
        $properties = [];

        if ($validation) {
            $properties[] = <<<'PHP'
        #[Required]
        public readonly string $name,
PHP;
            $properties[] = <<<'PHP'

        #[Required]
        #[Email]
        public readonly string $email,
PHP;
        } else {
            $properties[] = <<<'PHP'
        public readonly string $name,
PHP;
            $properties[] = <<<'PHP'

        public readonly string $email,
PHP;
        }

        if ($collection) {
            $properties[] = <<<'PHP'

        #[DataCollectionOf(ItemDTO::class)]
        public readonly DataCollection $items,
PHP;
        }

        $propertiesStr = implode('', $properties);

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

{$uses}

class {$className} extends SimpleDTO
{
    public function __construct(
{$propertiesStr}
    ) {}
}

PHP;
    }

    /** Generate a resource DTO with common fields. */
    protected function generateResourceDto(
        string $namespace,
        string $className,
        string $uses,
        bool $validation,
        bool $collection
    ): string
    {
        $properties = [];

        if ($validation) {
            $properties[] = <<<'PHP'
        #[Required]
        public readonly int $id,
PHP;
            $properties[] = <<<'PHP'

        #[Required]
        #[Min(3)]
        #[Max(255)]
        public readonly string $name,
PHP;
            $properties[] = <<<'PHP'

        #[Required]
        #[Email]
        public readonly string $email,
PHP;
        } else {
            $properties[] = <<<'PHP'
        public readonly int $id,
PHP;
            $properties[] = <<<'PHP'

        public readonly string $name,
PHP;
            $properties[] = <<<'PHP'

        public readonly string $email,
PHP;
        }

        $properties[] = <<<'PHP'

        public readonly ?string $description = null,
PHP;

        $properties[] = <<<'PHP'

        public readonly ?DateTimeImmutable $createdAt = null,
PHP;

        $properties[] = <<<'PHP'

        public readonly ?DateTimeImmutable $updatedAt = null,
PHP;

        if ($collection) {
            $properties[] = <<<'PHP'

        #[DataCollectionOf(ItemDTO::class)]
        public readonly ?DataCollection $items = null,
PHP;
        }

        $propertiesStr = implode('', $properties);

        $casts = <<<'PHP'

    protected function casts(): array
    {
        return [
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
        ];
    }
PHP;

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use DateTimeImmutable;
{$uses}

class {$className} extends SimpleDTO
{
    public function __construct(
{$propertiesStr}
    ) {}
{$casts}
}

PHP;
    }
}


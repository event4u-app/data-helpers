<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Laravel\Commands;

use Illuminate\Filesystem\Filesystem;

// Create stub class if Laravel is not installed
if (!class_exists('Illuminate\Console\Command')) {
    if (!class_exists('event4u\DataHelpers\Frameworks\Laravel\Commands\Command')) {
        abstract class Command
        {
            /** @phpstan-ignore-next-line */
            public const SUCCESS = 0;
            /** @phpstan-ignore-next-line */
            public const FAILURE = 1;

            /** @phpstan-ignore-next-line */
            protected function info(string $message): void {}
            /** @phpstan-ignore-next-line */
            protected function error(string $message): void {}
            /** @phpstan-ignore-next-line */
            protected function option(string $name): mixed { return null; }
            /** @phpstan-ignore-next-line */
            protected function argument(string $name): mixed { return null; }
        }
    }
} elseif (!class_exists('event4u\DataHelpers\Frameworks\Laravel\Commands\Command')) {
    class_alias('Illuminate\Console\Command', 'event4u\DataHelpers\Frameworks\Laravel\Commands\Command');
}
use Illuminate\Support\Str;

/**
 * Artisan command to generate SimpleDTO classes.
 *
 * Usage:
 *   php artisan make:dto UserDTO
 *   php artisan make:dto UserDTO --validation
 *   php artisan make:dto UserDTO --collection
 *   php artisan make:dto UserDTO --resource
 *
 */
class MakeDtoCommand extends Command
{
    public $laravel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:dto {name : The name of the DTO class}
                            {--validation : Add validation attributes}
                            {--validate-request : Add ValidateRequest attribute for automatic validation}
                            {--form-request : Generate a DTOFormRequest instead}
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
        /** @phpstan-ignore-next-line */
        $name = (string)$this->argument('name');
        /** @phpstan-ignore-next-line */
        $validation = (bool)$this->option('validation');
        /** @phpstan-ignore-next-line */
        $collection = (bool)$this->option('collection');
        /** @phpstan-ignore-next-line */
        $resource = (bool)$this->option('resource');
        /** @phpstan-ignore-next-line */
        $force = (bool)$this->option('force');

        // Ensure name ends with DTO
        if (!Str::endsWith($name, 'DTO')) {
            $name .= 'DTO';
        }

        // Get path
        $path = $this->getPath($name);

        // Check if file exists
        if ($files->exists($path) && !$force) {
            /** @phpstan-ignore-next-line */
            $this->error(sprintf('DTO [%s] already exists!', $name));
            /** @phpstan-ignore-next-line */
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

        /** @phpstan-ignore-next-line */
        $this->info(sprintf('DTO [%s] created successfully.', $name));
        /** @phpstan-ignore-next-line */
        $this->info('Location: ' . $path);

        return self::SUCCESS;
    }

    /** Get the destination path for the DTO. */
    protected function getPath(string $name): string
    {
        /** @phpstan-ignore-next-line */
        return $this->laravel->basePath('app') . '/DTOs/' . $name . '.php';
    }

    /** Get the root namespace for the application. */
    protected function rootNamespace(): string
    {
        /** @phpstan-ignore-next-line */
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

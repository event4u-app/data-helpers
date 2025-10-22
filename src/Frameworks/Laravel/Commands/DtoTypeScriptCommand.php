<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Laravel\Commands;

use event4u\DataHelpers\SimpleDTO\Config\TypeScriptGeneratorOptions;
use event4u\DataHelpers\SimpleDTO\Enums\TypeScriptExportType;
use event4u\DataHelpers\SimpleDTO\TypeScriptGenerator;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Throwable;

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
        }
    }
} elseif (!class_exists('event4u\DataHelpers\Frameworks\Laravel\Commands\Command')) {
    class_alias('Illuminate\Console\Command', 'event4u\DataHelpers\Frameworks\Laravel\Commands\Command');
}

/**
 * Artisan command to generate TypeScript interfaces from SimpleDTOs.
 *
 * Usage:
 *   php artisan dto:typescript
 *   php artisan dto:typescript --output=resources/js/types/dtos.ts
 *   php artisan dto:typescript --path=app/DTOs
 *   php artisan dto:typescript --watch
 *
 */
class DtoTypeScriptCommand extends Command
{
    public $laravel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dto:typescript
                            {--output= : Output file path (default: resources/js/types/dtos.ts)}
                            {--path= : Path to scan for DTOs (default: app/DTOs)}
                            {--export=export : Export type (export, declare, or empty)}
                            {--no-comments : Disable comments in generated interfaces}
                            {--sort : Sort properties alphabetically}
                            {--watch : Watch for changes and regenerate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate TypeScript interfaces from SimpleDTOs';

    /** Execute the console command. */
    public function handle(Filesystem $files): int
    {
        /** @phpstan-ignore-next-line */
        $output = $this->option('output') ?? 'resources/js/types/dtos.ts';
        /** @phpstan-ignore-next-line */
        $path = $this->option('path') ?? 'app/DTOs';
        /** @phpstan-ignore-next-line */
        $exportType = (string)($this->option('export') ?? 'export');
        /** @phpstan-ignore-next-line */
        $includeComments = !$this->option('no-comments');
        /** @phpstan-ignore-next-line */
        $sort = (bool)$this->option('sort');
        /** @phpstan-ignore-next-line */
        $watch = (bool)$this->option('watch');

        // Get full paths
        /** @phpstan-ignore-next-line */
        $outputPath = $this->laravel->basePath($output);
        /** @phpstan-ignore-next-line */
        $scanPath = $this->laravel->basePath($path);

        if ($watch) {
            return $this->watchMode($files, $scanPath, $outputPath, $exportType, $includeComments, $sort);
        }

        return $this->generateOnce($files, $scanPath, $outputPath, $exportType, $includeComments, $sort);
    }

    /** Generate TypeScript interfaces once. */
    protected function generateOnce(
        Filesystem $files,
        string $scanPath,
        string $outputPath,
        string $exportType,
        bool $includeComments,
        bool $sort
    ): int {
        /** @phpstan-ignore-next-line */
        $this->info('Scanning for DTOs...');

        // Find all DTO classes
        $dtoClasses = $this->findDtoClasses($scanPath);

        if ([] === $dtoClasses) {
            /** @phpstan-ignore-next-line */
            $this->warn('No DTO classes found in ' . $scanPath);

            return self::FAILURE;
        }

        /** @phpstan-ignore-next-line */
        $this->info('Found ' . count($dtoClasses) . ' DTO classes');

        // Generate TypeScript
        $generator = new TypeScriptGenerator();
        /** @var array<class-string> $dtoClassStrings */
        $dtoClassStrings = $dtoClasses;

        // Build options from command parameters
        $options = new TypeScriptGeneratorOptions(
            exportType: TypeScriptExportType::fromString($exportType) ?? TypeScriptExportType::None,
            includeComments: $includeComments,
            sortProperties: $sort,
        );

        $typescript = $generator->generate($dtoClassStrings, $options);

        // Ensure directory exists
        $directory = dirname($outputPath);
        if (!$files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, true);
        }

        // Write file
        $files->put($outputPath, $typescript);

        /** @phpstan-ignore-next-line */
        $this->info("✅  TypeScript interfaces generated successfully!");
        /** @phpstan-ignore-next-line */
        $this->info('📄 Output: ' . $outputPath);
        /** @phpstan-ignore-next-line */
        $this->info("📊 Size: " . $files->size($outputPath) . " bytes");

        return self::SUCCESS;
    }

    /** Watch mode - regenerate on file changes. */
    protected function watchMode(
        Filesystem $files,
        string $scanPath,
        string $outputPath,
        string $exportType,
        bool $includeComments,
        bool $sort
    ): int {
        /** @phpstan-ignore-next-line */
        $this->info('👀 Watching for changes...');
        /** @phpstan-ignore-next-line */
        $this->info('📁 Scanning: ' . $scanPath);
        /** @phpstan-ignore-next-line */
        $this->info('📄 Output: ' . $outputPath);
        /** @phpstan-ignore-next-line */
        $this->info('Press Ctrl+C to stop');
        /** @phpstan-ignore-next-line */
        $this->newLine();

        $lastHash = '';

        /** @phpstan-ignore-next-line */
        while (true) {
            // Get current hash of all DTO files
            $currentHash = $this->getDirectoryHash($scanPath);

            if ($currentHash !== $lastHash) {
                /** @phpstan-ignore-next-line */
                $this->info('[' . date('H:i:s') . '] Change detected, regenerating...');

                $result = $this->generateOnce($files, $scanPath, $outputPath, $exportType, $includeComments, $sort);

                if (self::SUCCESS === $result) {
                    /** @phpstan-ignore-next-line */
                    $this->info('[' . date('H:i:s') . '] ✅ Regenerated successfully');
                } else {
                    /** @phpstan-ignore-next-line */
                    $this->error('[' . date('H:i:s') . '] ❌ Regeneration failed');
                }

                /** @phpstan-ignore-next-line */
                $this->newLine();
                $lastHash = $currentHash;
            }

            sleep(1);
        }
    }

    /**
     * Find all DTO classes in a directory.
     *
     * @return list<string>
     */
    protected function findDtoClasses(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        $dtoClasses = [];

        foreach ($finder as $file) {
            $className = $this->getClassNameFromFile($file->getPathname());

            if (null === $className) {
                continue;
            }

            // Check if class uses SimpleDTOTrait
            if ($this->isSimpleDto($className)) {
                $dtoClasses[] = $className;
            }
        }

        return $dtoClasses;
    }

    /** Get class name from file. */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            return null;
        }

        // Extract namespace
        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            return null;
        }

        $namespace = $namespaceMatches[1];

        // Extract class name
        if (!preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            return null;
        }

        $className = $classMatches[1];
        $fullClassName = $namespace . '\\' . $className;

        // Try to load the class
        if (!class_exists($fullClassName)) {
            require_once $filePath;
        }

        return $fullClassName;
    }

    /** Check if class is a SimpleDTO. */
    protected function isSimpleDto(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);
            $traits = $this->getAllTraits($reflection);

            return in_array('event4u\DataHelpers\SimpleDTO\SimpleDTOTrait', $traits, true);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get all traits used by a class (including parent classes).
     *
     * @param ReflectionClass<object> $reflection
     * @return array<string>
     */
    protected function getAllTraits(ReflectionClass $reflection): array
    {
        $traits = [];

        // Get traits from current class
        $traits = array_merge($traits, $reflection->getTraitNames());

        // Get traits from parent classes
        $parent = $reflection->getParentClass();
        while (false !== $parent) {
            $traits = array_merge($traits, $parent->getTraitNames());
            $parent = $parent->getParentClass();
        }

        return array_unique($traits);
    }

    /** Get hash of all files in directory. */
    protected function getDirectoryHash(string $path): string
    {
        if (!is_dir($path)) {
            return '';
        }

        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        $hashes = [];

        foreach ($finder as $file) {
            /** @phpstan-ignore-next-line */
            $hashes[] = hash_file('sha256', $file->getPathname());
        }

        /** @phpstan-ignore-next-line */
        return hash('sha256', implode('', $hashes));
    }
}

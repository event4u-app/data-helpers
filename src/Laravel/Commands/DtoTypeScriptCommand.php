<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Laravel\Commands;

use event4u\DataHelpers\SimpleDTO\TypeScriptGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Artisan command to generate TypeScript interfaces from SimpleDTOs.
 *
 * Usage:
 *   php artisan dto:typescript
 *   php artisan dto:typescript --output=resources/js/types/dtos.ts
 *   php artisan dto:typescript --path=app/DTOs
 *   php artisan dto:typescript --watch
 */
class DtoTypeScriptCommand extends Command
{
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
        $output = $this->option('output') ?? 'resources/js/types/dtos.ts';
        $path = $this->option('path') ?? 'app/DTOs';
        $exportType = $this->option('export') ?? 'export';
        $includeComments = !$this->option('no-comments');
        $sort = $this->option('sort');
        $watch = $this->option('watch');

        // Get full paths
        $outputPath = $this->laravel->basePath($output);
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
        $this->info('Scanning for DTOs...');

        // Find all DTO classes
        $dtoClasses = $this->findDtoClasses($scanPath);

        if (empty($dtoClasses)) {
            $this->warn("No DTO classes found in {$scanPath}");

            return self::FAILURE;
        }

        $this->info('Found ' . count($dtoClasses) . ' DTO classes');

        // Generate TypeScript
        $generator = new TypeScriptGenerator();
        $typescript = $generator->generate($dtoClasses, [
            'exportType' => $exportType,
            'includeComments' => $includeComments,
            'sortProperties' => $sort,
        ]);

        // Ensure directory exists
        $directory = dirname($outputPath);
        if (!$files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, true);
        }

        // Write file
        $files->put($outputPath, $typescript);

        $this->info("✅  TypeScript interfaces generated successfully!");
        $this->info("📄 Output: {$outputPath}");
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
        $this->info('👀 Watching for changes...');
        $this->info("📁 Scanning: {$scanPath}");
        $this->info("📄 Output: {$outputPath}");
        $this->info('Press Ctrl+C to stop');
        $this->newLine();

        $lastHash = '';

        while (true) {
            // Get current hash of all DTO files
            $currentHash = $this->getDirectoryHash($scanPath);

            if ($currentHash !== $lastHash) {
                $this->info('[' . date('H:i:s') . '] Change detected, regenerating...');

                $result = $this->generateOnce($files, $scanPath, $outputPath, $exportType, $includeComments, $sort);

                if (self::SUCCESS === $result) {
                    $this->info('[' . date('H:i:s') . '] ✅ Regenerated successfully');
                } else {
                    $this->error('[' . date('H:i:s') . '] ❌ Regeneration failed');
                }

                $this->newLine();
                $lastHash = $currentHash;
            }

            sleep(1);
        }

        return self::SUCCESS;
    }

    /**
     * Find all DTO classes in a directory.
     *
     * @return array<class-string>
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
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get all traits used by a class (including parent classes).
     *
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
            $hashes[] = md5_file($file->getPathname());
        }

        return md5(implode('', $hashes));
    }
}


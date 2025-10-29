<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Console;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Support\ConstructorMetadata;
use event4u\DataHelpers\Support\Cache\CacheManager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Throwable;

/**
 * Command to warm up the persistent cache for SimpleDtos.
 *
 * This command scans directories for SimpleDto classes and pre-generates
 * their metadata cache. This is useful for:
 * - Production deployments (warm cache before serving traffic)
 * - CI/CD pipelines (warm cache before running tests)
 * - Development (speed up first request)
 *
 * Usage:
 *   php bin/warm-cache.php [directories...]
 *   php bin/warm-cache.php src/Dtos tests/Fixtures
 *
 * Features:
 * - Auto-discovers SimpleDto classes
 * - Generates persistent cache entries
 * - Shows progress and statistics
 * - Validates cache after generation
 */
final class WarmCacheCommand
{
    private int $classesFound = 0;
    private int $classesWarmed = 0;
    private int $classesSkipped = 0;
    private int $classesFailed = 0;
    /** @var array<string> */
    private array $errors = [];

    /**
     * Execute the cache warming command.
     *
     * @param array<string> $directories Directories to scan for DTOs
     * @param bool $verbose Show detailed output
     * @param bool $validate Validate cache after warming
     */
    public function execute(array $directories, bool $verbose = false, bool $validate = true): int
    {
        $this->printHeader();

        if ([] === $directories) {
            $this->printError('No directories specified. Usage: php bin/warm-cache.php [directories...]');

            return 1;
        }

        // Scan directories for DTO classes
        $classes = $this->scanDirectories($directories, $verbose);

        if ([] === $classes) {
            $this->printWarning('No SimpleDto classes found in specified directories.');

            return 0;
        }

        $this->printInfo(sprintf('Found %d SimpleDto classes', count($classes)));
        echo "\n";

        // Warm cache for each class
        foreach ($classes as $class) {
            $this->warmClass($class, $verbose);
        }

        echo "\n";

        // Validate cache if requested
        if ($validate) {
            $this->validateCache($classes, $verbose);
        }

        // Print summary
        $this->printSummary();

        return 0 < $this->classesFailed ? 1 : 0;
    }

    /**
     * Scan directories for SimpleDto classes.
     *
     * @param array<string> $directories
     * @return array<class-string>
     */
    private function scanDirectories(array $directories, bool $verbose): array
    {
        $classes = [];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                $this->printWarning(sprintf('Directory not found: %s', $directory));
                continue;
            }

            if ($verbose) {
                $this->printInfo(sprintf('Scanning directory: %s', $directory));
            }

            $foundClasses = $this->scanDirectory($directory);
            $classes = array_merge($classes, $foundClasses);

            if ($verbose) {
                $this->printInfo(sprintf('  Found %d classes', count($foundClasses)));
            }
        }

        return array_unique($classes);
    }

    /**
     * Scan a single directory for PHP files and extract class names.
     *
     * @return array<class-string>
     */
    private function scanDirectory(string $directory): array
    {
        $classes = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if (!is_object($file) || !method_exists($file, 'isFile') || !method_exists($file, 'getExtension')) {
                    continue;
                }
                if (!$file->isFile() || 'php' !== $file->getExtension()) {
                    continue;
                }

                if (!method_exists($file, 'getPathname')) {
                    continue;
                }
                $class = $this->extractClassFromFile($file->getPathname());

                if (null !== $class && $this->isSimpleDto($class)) {
                    $classes[] = $class;
                    $this->classesFound++;
                }
            }
        } catch (Throwable $throwable) {
            $this->printError(sprintf('Error scanning directory %s: %s', $directory, $throwable->getMessage()));
        }

        return $classes;
    }

    /**
     * Extract class name from PHP file.
     *
     * @return class-string|null
     */
    private function extractClassFromFile(string $file): ?string
    {
        $content = file_get_contents($file);

        if (false === $content) {
            return null;
        }

        // Extract namespace
        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            return null;
        }

        $namespace = trim($namespaceMatch[1]);

        // Extract class name (class, final class, abstract class, readonly class)
        if (!preg_match('/(?:final\s+|abstract\s+|readonly\s+)*class\s+(\w+)/', $content, $classMatch)) {
            return null;
        }

        $className = trim($classMatch[1]);

        $fullClassName = $namespace . '\\' . $className;

        // Verify it's a valid class string
        if (!class_exists($fullClassName)) {
            return null;
        }

        /** @var class-string $fullClassName */
        return $fullClassName;
    }

    /** Check if class is a SimpleDto.
     * @param class-string $class
     */
    private function isSimpleDto(string $class): bool
    {
        try {
            if (!class_exists($class)) {
                return false;
            }

            $reflection = new ReflectionClass($class);

            return $reflection->isSubclassOf(SimpleDto::class) && !$reflection->isAbstract();
        } catch (Throwable) {
            return false;
        }
    }

    /** Warm cache for a single class.
     * @param class-string $class
     */
    private function warmClass(string $class, bool $verbose): void
    {
        try {
            // Check if class file exists and can be tracked for invalidation
            $reflection = new ReflectionClass($class);
            $sourceFile = $reflection->getFileName();

            if (false === $sourceFile || !file_exists($sourceFile)) {
                $this->classesSkipped++;

                if ($verbose) {
                    $this->printWarning(sprintf('  ⚠️  Skipped: %s (no source file for invalidation)', $class));
                }

                return;
            }

            // Get metadata (this will cache it)
            $metadata = ConstructorMetadata::get($class);

            if (empty($metadata['parameters'])) {
                $this->classesSkipped++;

                if ($verbose) {
                    $this->printWarning(sprintf('  ⚠️  Skipped: %s (no constructor parameters)', $class));
                }

                return;
            }

            $this->classesWarmed++;

            if ($verbose) {
                $paramCount = count($metadata['parameters']);
                $this->printSuccess(sprintf('  ✅  Warmed: %s (%d parameters)', $class, $paramCount));
            } else {
                echo '.';
            }
        } catch (Throwable $throwable) {
            $this->classesFailed++;
            $this->errors[] = sprintf('%s: %s', $class, $throwable->getMessage());

            if ($verbose) {
                $this->printError(sprintf('  ❌  Failed: %s - %s', $class, $throwable->getMessage()));
            } else {
                echo 'F';
            }
        }
    }

    /** Validate that cache entries were created.
     * @param array<class-string> $classes
     */
    private function validateCache(array $classes, bool $verbose): void
    {
        if ($verbose) {
            echo "\n";
            $this->printInfo('Validating cache entries...');
        }

        $validated = 0;
        $missing = 0;

        foreach ($classes as $class) {
            $cacheKey = 'dto_metadata_' . str_replace('\\', '_', $class);

            if (CacheManager::has($cacheKey)) {
                $validated++;
            } else {
                $missing++;

                if ($verbose) {
                    $this->printWarning(sprintf('  ⚠️  Cache missing: %s', $class));
                }
            }
        }

        if ($verbose) {
            $this->printSuccess(sprintf('  ✅  Validated: %d cache entries', $validated));

            if (0 < $missing) {
                $this->printWarning(sprintf('  ⚠️  Missing: %d cache entries', $missing));
            }
        }
    }

    /** Print summary statistics. */
    private function printSummary(): void
    {
        echo "\n";
        $this->printHeader('Summary');

        echo sprintf("  Classes found:   %d\n", $this->classesFound);
        echo sprintf("  Classes warmed:  %s\n", $this->colorize($this->classesWarmed, 'green'));
        echo sprintf("  Classes skipped: %s\n", $this->colorize($this->classesSkipped, 'yellow'));
        echo sprintf("  Classes failed:  %s\n", $this->colorize($this->classesFailed, 'red'));

        if ([] !== $this->errors) {
            echo "\n";
            $this->printHeader('Errors');

            foreach ($this->errors as $error) {
                $this->printError('  ' . $error);
            }
        }

        echo "\n";

        if (0 < $this->classesFailed) {
            $this->printError('❌  Cache warming completed with errors');
        } else {
            $this->printSuccess('✅  Cache warming completed successfully');
        }

        echo "\n";
    }

    private function printHeader(string $text = 'Data Helpers - Cache Warming'): void
    {
        echo "\n";
        echo $this->colorize(str_repeat('━', 60), 'blue') . "\n";
        echo $this->colorize('  ' . $text, 'blue') . "\n";
        echo $this->colorize(str_repeat('━', 60), 'blue') . "\n";
        echo "\n";
    }

    private function printSuccess(string $message): void
    {
        echo $this->colorize($message, 'green') . "\n";
    }

    private function printInfo(string $message): void
    {
        echo $this->colorize($message, 'blue') . "\n";
    }

    private function printWarning(string $message): void
    {
        echo $this->colorize($message, 'yellow') . "\n";
    }

    private function printError(string $message): void
    {
        echo $this->colorize($message, 'red') . "\n";
    }

    private function colorize(string|int $text, string $color): string
    {
        $colors = [
            'red' => "\033[0;31m",
            'green' => "\033[0;32m",
            'yellow' => "\033[0;33m",
            'blue' => "\033[0;34m",
            'reset' => "\033[0m",
        ];

        return ($colors[$color] ?? '') . $text . $colors['reset'];
    }
}

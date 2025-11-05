<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Checks for duplicate DTO class names in test files.
 *
 * Duplicate DTO class names can cause test failures with unclear error messages
 * because PHP will throw "Cannot declare class X, because the name is already in use".
 */
class DuplicateDtoChecker
{
    /**
     * Check for duplicate DTO class names in test files.
     *
     * @param string $testsDirectory The tests directory to scan
     * @param bool $throwOnDuplicate Whether to throw an exception on duplicates (default: true)
     * @return array<string, array<string>> Map of class names to file paths
     * @throws RuntimeException If duplicates are found and $throwOnDuplicate is true
     */
    public static function check(string $testsDirectory, bool $throwOnDuplicate = true): array
    {
        $dtoClasses = self::scanForDtoClasses($testsDirectory);
        $duplicates = self::findDuplicates($dtoClasses);

        if ([] !== $duplicates) {
            $message = self::formatDuplicatesMessage($duplicates);

            if ($throwOnDuplicate) {
                throw new RuntimeException($message);
            }

            echo "\n" . $message . "\n";
        }

        return $duplicates;
    }

    /**
     * Scan all PHP files in the tests directory for DTO class definitions.
     *
     * @param string $testsDirectory The tests directory to scan
     * @return array<string, array<string>> Map of class names to file paths
     */
    private static function scanForDtoClasses(string $testsDirectory): array
    {
        $dtoClasses = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($testsDirectory)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getPathname();

                // Skip Fixtures directories - they are allowed to have duplicate names
                if (str_contains($filePath, '/Fixtures/') || str_contains($filePath, '\\Fixtures\\')) {
                    continue;
                }

                // Skip the DtoTestHelper and this checker itself
                if (str_contains($filePath, 'DtoTestHelper.php') || str_contains(
                    $filePath,
                    'DuplicateDtoChecker.php'
                )) {
                    continue;
                }

                // Skip the DuplicateDtoCheckerTest itself (it creates temporary DTOs)
                if (str_contains($filePath, 'DuplicateDtoCheckerTest.php')) {
                    continue;
                }

                $classes = self::extractDtoClasses($filePath);

                foreach ($classes as $className) {
                    if (!isset($dtoClasses[$className])) {
                        $dtoClasses[$className] = [];
                    }
                    $dtoClasses[$className][] = $filePath;
                }
            }
        }

        return $dtoClasses;
    }

    /**
     * Extract DTO class names from a PHP file.
     *
     * @param string $filePath The file path to scan
     * @return array<string> List of DTO class names (with namespace if present)
     */
    private static function extractDtoClasses(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            return [];
        }

        $classes = [];
        $namespace = self::extractNamespace($content);

        // Find all classes that extend SimpleDto or LiteDto
        // Pattern: class ClassName extends SimpleDto|LiteDto
        $pattern = '/class\s+(\w+)\s+extends\s+(?:.*\\\\)?(SimpleDto|LiteDto)\b/';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $className) {
                // Build full class name with namespace
                $fullClassName = $namespace ? $namespace . '\\' . $className : $className;
                $classes[] = $fullClassName;
            }
        }

        return $classes;
    }

    /**
     * Extract namespace from PHP file content.
     *
     * @param string $content The file content
     * @return string|null The namespace or null if not found
     */
    private static function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([\w\\\\]+);/', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Find duplicate class names.
     *
     * @param array<string, array<string>> $dtoClasses Map of class names to file paths
     * @return array<string, array<string>> Map of duplicate class names to file paths
     */
    private static function findDuplicates(array $dtoClasses): array
    {
        $duplicates = [];

        foreach ($dtoClasses as $className => $files) {
            if (count($files) > 1) {
                $duplicates[$className] = $files;
            }
        }

        return $duplicates;
    }

    /**
     * Format duplicates message for output.
     *
     * @param array<string, array<string>> $duplicates Map of duplicate class names to file paths
     * @return string Formatted message
     */
    private static function formatDuplicatesMessage(array $duplicates): string
    {
        $message = "\n";
        $message .= "⚠️  DUPLICATE DTO CLASSES FOUND!\n";
        $message .= "\n";
        $message .= "The following DTO classes are defined in multiple test files:\n";
        $message .= "\n";

        foreach ($duplicates as $className => $files) {
            $message .= "📦 {$className}:\n";
            foreach ($files as $file) {
                // Make path relative to project root for better readability
                $relativePath = str_replace(dirname(__DIR__, 2) . '/', '', $file);
                $message .= sprintf('   - %s%s', $relativePath, PHP_EOL);
            }
            $message .= "\n";
        }

        $message .= "⚠️  This can cause test failures with unclear error messages.\n";
        $message .= "Please rename the DTOs to make them unique (e.g., SimpleDtoUserDto, SymfonyUserDto, etc.)\n";
        $message .= "\n";

        return $message . "To disable this check, set the environment variable: SKIP_DUPLICATE_DTO_CHECK=1\n";
    }
}

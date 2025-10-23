<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Documentation Tests - Examples Directory
 *
 * These tests ensure that all code examples in the examples/ directory
 * are valid and executable without errors.
 */
describe('Examples Directory', function(): void {
    beforeEach(function(): void {
        // Clean up any previous test state
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    });

    it('has examples directory', function(): void {
        expect(__DIR__ . '/../../examples')->toBeDirectory();
    });

    it('executes all example files without errors', function(): void {
        $examplesDir = __DIR__ . '/../../examples';

        // Recursively find all PHP files in examples directory
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($examplesDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        // Filter out debug files
        $files = array_filter($files, fn(string $file): bool => ! str_starts_with(basename($file), 'debug-'));

        // Sort files
        sort($files);

        expect($files)->not->toBeEmpty('Examples directory should contain PHP files');

        $failures = [];
        $skipped = [];
        $passed = 0;

        foreach ($files as $filepath) {
            $filename = basename($filepath);

            // Skip Laravel/Symfony examples if dependencies not available
            if (str_contains($filename, 'laravel') && ! class_exists(ServiceProvider::class)) {
                $skipped[] = $filename . ' (Laravel dependencies not available)';
                continue;
            }

            if (str_contains($filename, 'symfony') && ! class_exists(Kernel::class)) {
                $skipped[] = $filename . ' (Symfony dependencies not available)';
                continue;
            }

            if (str_contains($filename, 'doctrine') && ! class_exists(EntityManager::class)) {
                $skipped[] = $filename . ' (Doctrine dependencies not available)';
                continue;
            }

            // Execute the example file in a separate process to avoid conflicts
            // Change to repository root directory so relative paths work correctly
            $repoRoot = __DIR__ . '/../..';
            $command = sprintf('cd %s && php %s 2>&1', escapeshellarg($repoRoot), escapeshellarg($filepath));
            exec($command, $output, $returnCode);

            $outputText = implode("\n", $output);

            // Check if example was skipped (e.g., missing dependencies)
            if (str_starts_with($outputText, 'Skipping:') || str_contains($outputText, 'Skipping:')) {
                // Extract the reason from the output
                if (preg_match('/Skipping:\s*(.+)/', $outputText, $matches)) {
                    $skipped[] = $filename . ' (' . trim($matches[1]) . ')';
                } else {
                    $skipped[] = $filename . ' (skipped)';
                }
                continue;
            }

            // Check if example produces output
            $hasOutput = ! empty(trim($outputText));

            // Examples that demonstrate error handling may exit with non-zero code
            // but should still produce meaningful output
            $isValidationExample = str_contains($filename, 'validation') ||
                                   str_contains($filename, 'pipeline') ||
                                   str_contains($filename, 'error');

            if (0 !== $returnCode) {
                // If it's a validation/error example and has output, it's OK
                if ($isValidationExample && $hasOutput) {
                    // Check if the output shows expected error handling
                    $hasExpectedError = str_contains($outputText, 'Validation failed') ||
                                       str_contains($outputText, 'ValidationException') ||
                                       str_contains($outputText, '❌') ||
                                       str_contains($outputText, 'Error:') ||
                                       str_contains($outputText, 'Exception:');

                    if ($hasExpectedError) {
                        $passed++;
                    } else {
                        $failures[] = sprintf(
                            "%s: Exit code %d but no expected error output\n  Output: %s",
                            $filename,
                            $returnCode,
                            substr($outputText, 0, 500)
                        );
                    }
                } else {
                    $failures[] = sprintf(
                        "%s: Exit code %d\n  Output: %s",
                        $filename,
                        $returnCode,
                        substr($outputText, -2000) // Last 2000 chars to see the error
                    );
                }
            } elseif (! str_contains($filename, 'performance') && ! str_contains($filename, 'benchmarking')) {
                // Check that example produces some output (most examples do)
                if (! $hasOutput) {
                    $failures[] = $filename . ': Example should produce output';
                } else {
                    $passed++;
                }
            } else {
                $passed++;
            }
        }

        // Report results
        $total = count($files);
        $message = sprintf(
            "Examples: %d passed, %d failed, %d skipped (total: %d)",
            $passed,
            count($failures),
            count($skipped),
            $total
        );

        if ([] !== $failures) {
            $message .= "\n\nFailures:\n" . implode("\n\n", $failures);
        }

        if ([] !== $skipped) {
            $message .= "\n\nSkipped:\n" . implode("\n", $skipped);
        }

        expect($failures)->toBeEmpty($message);
    });
});

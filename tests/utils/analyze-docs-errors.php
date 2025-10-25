<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Tests\Unit\Docs\DocumentationExampleExtractor;

$docsPath = __DIR__ . '/../../starlight/src/content/docs';
$files = DocumentationExampleExtractor::findMarkdownFiles($docsPath);
$allExamples = DocumentationExampleExtractor::extractFromFiles($files);

$fileStats = [];

foreach ($allExamples as $filePath => $examples) {
    $fileName = basename($filePath);
    $fileExecuted = 0;
    $fileSkipped = 0;
    $fileFailed = 0;
    $fileErrors = [];

    foreach ($examples as $index => $example) {
        $code = $example['code'];

        // Skip examples
        $trimmedCode = trim($code);
        $lines = explode("\n", $trimmedCode);
        $hasOnlyProperties = true;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#[') || str_starts_with($line, '//')) {
                continue;
            }
            if (!preg_match('/^(public|private|protected)\s/', $line)) {
                $hasOnlyProperties = false;
                break;
            }
        }

        // Skip incomplete array definitions
        $isIncompleteArray = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/=>\s*$/', $line) || preg_match('/^\[.*=>\s*$/', $line)) {
                $isIncompleteArray = true;
                break;
            }
        }

        // Skip examples with missing classes
        $hasMissingClasses = str_contains($code, 'Spatie\\') ||
                            str_contains($code, 'class User') ||
                            str_contains($code, 'class ValidationAttribute');

        if (str_contains($code, '...') ||
            str_contains($code, '// ...') ||
            preg_match('/^(class|interface|trait|enum)\s+\w+/', $trimmedCode) ||
            $hasOnlyProperties ||
            $isIncompleteArray ||
            $hasMissingClasses ||
            str_contains($filePath, 'architecture.md') ||
            str_contains($filePath, 'contributing.md') ||
            str_contains($filePath, 'migration-from-spatie.md') ||
            str_contains($code, 'extends Model') ||
            str_contains($code, 'extends SimpleDTO') ||
            str_contains($code, 'implements FilterInterface') ||
            str_contains($code, 'TrimStrings') ||
            str_contains($code, 'LowercaseEmails') ||
            str_contains($code, 'SkipEmptyValues')) {
            $fileSkipped++;
            continue;
        }

        try {
            $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($code, true);

            $testFunction = function () use ($executableCode) {
                set_error_handler(function ($errno, $errstr) {
                    if ($errno === E_WARNING || $errno === E_NOTICE) {
                        throw new \RuntimeException("Warning: $errstr");
                    }
                    return false;
                });

                eval(substr($executableCode, 5));

                restore_error_handler();
            };

            $testFunction();
            $fileExecuted++;
        } catch (\Throwable $e) {
            restore_error_handler();
            $fileFailed++;
            $errorMsg = $e->getMessage();

            // Categorize error
            if (str_contains($errorMsg, 'syntax error, unexpected token "public"')) {
                $errorType = 'Property without class';
            } elseif (str_contains($errorMsg, 'syntax error, unexpected token "}"')) {
                $errorType = 'Incomplete snippet';
            } elseif (str_contains($errorMsg, 'Class "') && str_contains($errorMsg, '" not found')) {
                preg_match('/Class "([^"]+)" not found/', $errorMsg, $matches);
                $errorType = 'Missing class: ' . ($matches[1] ?? 'unknown');
            } elseif (str_contains($errorMsg, 'Undefined variable')) {
                preg_match('/Undefined variable \$(\w+)/', $errorMsg, $matches);
                $errorType = 'Undefined var: $' . ($matches[1] ?? 'unknown');
            } else {
                $errorType = substr($errorMsg, 0, 50);
            }

            $fileErrors[] = $errorType;
        }
    }

    if ($fileFailed > 0) {
        $fileStats[$fileName] = [
            'executed' => $fileExecuted,
            'skipped' => $fileSkipped,
            'failed' => $fileFailed,
            'errors' => $fileErrors,
        ];
    }
}

// Sort by number of failures
uasort($fileStats, fn($a, $b) => $b['failed'] <=> $a['failed']);

echo "📊 Files with most failures:\n\n";
foreach (array_slice($fileStats, 0, 20) as $file => $stats) {
    echo sprintf(
        "%s: %d failed, %d executed, %d skipped\n",
        $file,
        $stats['failed'],
        $stats['executed'],
        $stats['skipped']
    );

    // Show top 3 error types
    $errorCounts = array_count_values($stats['errors']);
    arsort($errorCounts);
    foreach (array_slice($errorCounts, 0, 3) as $error => $count) {
        echo sprintf("  - %dx %s\n", $count, $error);
    }
    echo "\n";
}


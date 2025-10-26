<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Tests\Unit\Docs\DocumentationExampleExtractor;

$docsPath = __DIR__ . '/../../starlight/src/content/docs';
$files = DocumentationExampleExtractor::findMarkdownFiles($docsPath);
$allExamples = DocumentationExampleExtractor::extractFromFiles($files);

echo "🔍 Validating all documentation examples...\n\n";

$totalFiles = 0;
$totalExamples = 0;
$totalExecuted = 0;
$totalSkipped = 0;
$totalFailed = 0;
$failedExamples = [];

foreach ($allExamples as $filePath => $examples) {
    $totalFiles++;
    $fileExecuted = 0;
    $fileSkipped = 0;
    $fileFailed = 0;

    foreach ($examples as $index => $example) {
        $totalExamples++;
        $code = $example['code'];
        $line = $example['line'];

        // Skip examples with placeholders or incomplete code
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

        // Skip incomplete array definitions (lines ending with =>)
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
            $totalSkipped++;
            continue;
        }

        // Prepare code for execution with assertions
        try {
            $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($code, true);

            // Execute the code directly (not in a function) so use statements work
            set_error_handler(function($errno, $errstr): false {
                if (E_WARNING === $errno || E_NOTICE === $errno) {
                    throw new RuntimeException('Warning: ' . $errstr);
                }
                return false;
            });

            /** @phpstan-ignore-next-line disallowed.eval, ergebnis.noEval */
            eval(substr($executableCode, 5));

            restore_error_handler();

            $fileExecuted++;
            $totalExecuted++;
        } catch (Throwable $e) {
            restore_error_handler();
            $fileFailed++;
            $totalFailed++;
            $failedExamples[] = [
                'file' => basename($filePath),
                'line' => $line,
                'index' => $index + 1,
                'error' => substr($e->getMessage(), 0, 200),
                'code' => substr($code, 0, 150),
                'fullCode' => $code, // Add full code for debugging
            ];
        }
    }

    if (0 < $fileExecuted || 0 < $fileFailed) {
        $status = 0 < $fileFailed ? '❌' : '✅';
        echo sprintf(
            "%s %s: %d executed, %d skipped, %d failed\n",
            $status,
            basename($filePath),
            $fileExecuted,
            $fileSkipped,
            $fileFailed
        );
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "📊 Summary:\n";
echo sprintf("  Files: %d\n", $totalFiles);
echo sprintf("  Total examples: %d\n", $totalExamples);
echo sprintf("  ✅ Executed: %d\n", $totalExecuted);
echo sprintf("  ⏭️  Skipped: %d\n", $totalSkipped);
echo sprintf("  ❌ Failed: %d\n", $totalFailed);
echo str_repeat('=', 80) . "\n\n";

if ([] !== $failedExamples) {
    echo "❌ Failed Examples (first 20):\n\n";
    foreach (array_slice($failedExamples, 0, 20) as $i => $failed) {
        echo sprintf(
            "%d. %s (line %d, example #%d)\n   Error: %s\n   Code: %s...\n\n",
            $i + 1,
            $failed['file'],
            $failed['line'],
            $failed['index'],
            $failed['error'],
            str_replace("\n", ' ', $failed['code'])
        );

        // Debug: Show full code if requested
        if (isset($argv[1]) && '--debug' === $argv[1]) {
            echo "   Full Code:\n";
            echo "   " . str_replace("\n", "\n   ", $failed['fullCode']) . "\n\n";
        }
    }

    if (count($failedExamples) > 20) {
        echo sprintf("... and %d more failures\n\n", count($failedExamples) - 20);
    }

    // Group by error type
    echo "\n📋 Errors by type:\n";
    $errorTypes = [];
    foreach ($failedExamples as $failed) {
        $errorType = substr($failed['error'], 0, 80);
        if (!isset($errorTypes[$errorType])) {
            $errorTypes[$errorType] = 0;
        }
        $errorTypes[$errorType]++;
    }
    arsort($errorTypes);
    foreach (array_slice($errorTypes, 0, 10) as $error => $count) {
        echo sprintf("  %dx: %s\n", $count, $error);
    }
}

exit(0 < $totalFailed ? 1 : 0);

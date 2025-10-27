<?php

declare(strict_types=1);

use Tests\Utils\Helpers\DocumentationExampleExtractor;

describe('Starlight All Documentation Examples', function(): void {
    it('validates all documentation examples', function(): void {
        $docsPath = __DIR__ . '/../../starlight/src/content/docs';
        $files = DocumentationExampleExtractor::findMarkdownFiles($docsPath);
        $allExamples = DocumentationExampleExtractor::extractFromFiles($files);

        expect($allExamples)->not->toBeEmpty('No documentation files found');

        $totalFiles = 0;
        $totalExamples = 0;
        $totalExecuted = 0;
        $totalSkipped = 0;
        $totalFailed = 0;
        $failedExamples = [];

        foreach ($allExamples as $filePath => $examples) {
            $totalFiles++;

            foreach ($examples as $index => $example) {
                $totalExamples++;
                $code = $example['code'];
                $line = $example['line'];

                // Skip examples with placeholders or incomplete code
                $trimmedCode = trim($code);
                $lines = explode("\n", $trimmedCode);
                $hasOnlyProperties = true;
                foreach ($lines as $codeLine) {
                    $codeLine = trim($codeLine);
                    if (empty($codeLine) || str_starts_with($codeLine, '#[') || str_starts_with($codeLine, '//')) {
                        continue;
                    }
                    if (!preg_match('/^(public|private|protected)\s/', $codeLine)) {
                        $hasOnlyProperties = false;
                        break;
                    }
                }

                // Skip incomplete array definitions (lines ending with =>)
                $isIncompleteArray = false;
                foreach ($lines as $codeLine) {
                    $codeLine = trim($codeLine);
                    if (preg_match('/=>\s*$/', $codeLine) || preg_match('/^\[.*=>\s*$/', $codeLine)) {
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

                    $totalExecuted++;
                } catch (Throwable $e) {
                    restore_error_handler();
                    $totalFailed++;
                    $failedExamples[] = [
                        'file' => basename($filePath),
                        'line' => $line,
                        'index' => $index + 1,
                        'error' => substr($e->getMessage(), 0, 200),
                        'code' => substr($code, 0, 150),
                    ];
                }
            }
        }

        // Build summary message
        $message = sprintf(
            "\n📊 Documentation Examples Summary:\n" .
            "  Files: %d\n" .
            "  Total examples: %d\n" .
            "  ✅ Executed: %d\n" .
            "  ⏭️  Skipped: %d\n" .
            "  ❌ Failed: %d\n",
            $totalFiles,
            $totalExamples,
            $totalExecuted,
            $totalSkipped,
            $totalFailed
        );

        // Add failed examples details
        if ([] !== $failedExamples) {
            $message .= "\n❌ Failed Examples (first 10):\n\n";
            $displayCount = min(10, count($failedExamples));
            for ($i = 0; $i < $displayCount; $i++) {
                $failed = $failedExamples[$i];
                $message .= sprintf(
                    "%d. %s (line %d, example #%d)\n" .
                    "   Error: %s\n" .
                    "   Code: %s...\n\n",
                    $i + 1,
                    $failed['file'],
                    $failed['line'],
                    $failed['index'],
                    $failed['error'],
                    $failed['code']
                );
            }

            if (count($failedExamples) > 10) {
                $message .= sprintf("... and %d more failures\n", count($failedExamples) - 10);
            }
        }

        // Expect no failures
        expect($totalFailed)->toBe(0, $message);
        expect($totalExecuted)->toBeGreaterThan(0, 'No examples were executed');
    })->group('docs', 'starlight', 'all');
});

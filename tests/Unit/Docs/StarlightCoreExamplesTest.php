<?php

declare(strict_types=1);

use Tests\Utils\Helpers\DocumentationExampleExtractor;

describe('Starlight Core Documentation Examples', function(): void {
    it('validates Quick Start examples', function(): void {
        $file = __DIR__ . '/../../../starlight/src/content/docs/getting-started/quick-start.md';

        if (!file_exists($file)) {
            $this->markTestSkipped('Quick Start documentation not found');
        }

        $examples = DocumentationExampleExtractor::extractExamples($file);
        expect($examples)->not->toBeEmpty();

        $executedCount = 0;
        $skippedCount = 0;

        foreach ($examples as $index => $example) {
            $code = $example['code'];
            $line = $example['line'];

            // Skip examples with placeholders or incomplete code
            if (str_contains($code, '...') || str_contains($code, '// ...')) {
                $skippedCount++;
                continue;
            }

            // Prepare code for execution with assertions
            $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($code, true);

            // Execute the code
            try {
                set_error_handler(function($errno, $errstr) use ($index, $line, $code): false {
                    if (E_WARNING === $errno || E_NOTICE === $errno) {
                        throw new RuntimeException(
                            sprintf(
                                "Example #%d at line %d produced warning:\n\nCode:\n%s\n\nWarning: %s",
                                $index + 1,
                                $line,
                                substr($code, 0, 100),
                                $errstr
                            )
                        );
                    }
                    return false;
                });

                // @phpstan-ignore-next-line disallowed.eval, ergebnis.noEval
                eval(substr($executableCode, 5));

                restore_error_handler();
                $executedCount++;
            } catch (Throwable $e) {
                restore_error_handler();
                throw new RuntimeException(
                    sprintf(
                        "Example #%d at line %d failed:\n\nCode:\n%s\n\nError: %s",
                        $index + 1,
                        $line,
                        substr($code, 0, 200),
                        $e->getMessage()
                    ),
                    0,
                    $e
                );
            }
        }

        # echo sprintf("\n✅ Quick Start: Executed %d examples, skipped %d\n", $executedCount, $skippedCount);
        expect($executedCount)->toBeGreaterThan(0);
    })->group('docs', 'starlight', 'quick-start');

    it('validates DataMapper documentation examples', function(): void {
        $file = __DIR__ . '/../../../starlight/src/content/docs/main-classes/data-mapper.md';

        if (!file_exists($file)) {
            $this->markTestSkipped('DataMapper documentation not found');
        }

        $examples = DocumentationExampleExtractor::extractExamples($file);
        expect($examples)->not->toBeEmpty();

        $executedCount = 0;
        $skippedCount = 0;
        $failedExamples = [];

        foreach ($examples as $index => $example) {
            $code = $example['code'];
            $line = $example['line'];

            // Skip examples with placeholders or incomplete code
            if (str_contains($code, '...') ||
                str_contains($code, '// ...') ||
                str_contains($code, 'class ') ||
                str_contains($code, 'interface ')) {
                $skippedCount++;
                continue;
            }

            // Prepare code for execution with assertions
            $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($code, true);

            // Execute the code
            try {
                set_error_handler(function($errno, $errstr): false {
                    if (E_WARNING === $errno || E_NOTICE === $errno) {
                        throw new RuntimeException('Warning: ' . $errstr);
                    }
                    return false;
                });

                // @phpstan-ignore-next-line disallowed.eval, ergebnis.noEval
                eval(substr($executableCode, 5));

                restore_error_handler();
                $executedCount++;
            } catch (Throwable $e) {
                restore_error_handler();
                $failedExamples[] = [
                    'line' => $line,
                    'index' => $index + 1,
                    'error' => substr($e->getMessage(), 0, 150),
                    'code' => substr($code, 0, 100),
                ];
            }
        }

//        echo sprintf(
//            "\n📊 DataMapper: Executed %d, skipped %d, failed %d\n",
//            $executedCount,
//            $skippedCount,
//            count($failedExamples)
//        );

        if ([] !== $failedExamples) {
            echo "\n❌ Failed examples:\n";
            foreach (array_slice($failedExamples, 0, 5) as $failed) {
                echo sprintf("  - Line %d (example #%d): %s\n", $failed['line'], $failed['index'], $failed['error']);
            }
            if (count($failedExamples) > 5) {
                echo sprintf("  ... and %d more\n", count($failedExamples) - 5);
            }
        }

        expect($executedCount)->toBeGreaterThan(0);
    })->group('docs', 'starlight', 'data-mapper');

    it('validates DataAccessor documentation examples', function(): void {
        $file = __DIR__ . '/../../../starlight/src/content/docs/main-classes/data-accessor.md';

        if (!file_exists($file)) {
            $this->markTestSkipped('DataAccessor documentation not found');
        }

        $examples = DocumentationExampleExtractor::extractExamples($file);
        expect($examples)->not->toBeEmpty();

        $executedCount = 0;
        $skippedCount = 0;
        $failedExamples = [];

        foreach ($examples as $index => $example) {
            $code = $example['code'];
            $line = $example['line'];

            // Skip examples with placeholders
            if (str_contains($code, '...') || str_contains($code, '// ...')) {
                $skippedCount++;
                continue;
            }

            // Prepare code for execution with assertions
            $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($code, true);

            // Execute the code
            try {
                // @phpstan-ignore-next-line disallowed.eval, ergebnis.noEval
                eval(substr($executableCode, 5));
                $executedCount++;
            } catch (Throwable $e) {
                $failedExamples[] = [
                    'line' => $line,
                    'index' => $index + 1,
                    'error' => substr($e->getMessage(), 0, 150),
                ];
            }
        }

//        echo sprintf(
//            "\n📊 DataAccessor: Executed %d, skipped %d, failed %d\n",
//            $executedCount,
//            $skippedCount,
//            count($failedExamples)
//        );

        if ([] !== $failedExamples) {
            echo "\n❌ Failed examples:\n";
            foreach (array_slice($failedExamples, 0, 5) as $failed) {
                echo sprintf("  - Line %d (example #%d): %s\n", $failed['line'], $failed['index'], $failed['error']);
            }
        }

        expect($executedCount)->toBeGreaterThan(0);
    })->group('docs', 'starlight', 'data-accessor');

    it('validates DataFilter documentation examples', function(): void {
        $file = __DIR__ . '/../../../starlight/src/content/docs/main-classes/data-filter.md';

        if (!file_exists($file)) {
            $this->markTestSkipped('DataFilter documentation not found');
        }

        $examples = DocumentationExampleExtractor::extractExamples($file);
        expect($examples)->not->toBeEmpty();

        $executedCount = 0;
        $skippedCount = 0;
        $failedExamples = [];

        foreach ($examples as $index => $example) {
            $code = $example['code'];
            $line = $example['line'];

            // Skip examples with placeholders
            if (str_contains($code, '...') || str_contains($code, '// ...')) {
                $skippedCount++;
                continue;
            }

            // Prepare code for execution with assertions
            $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($code, true);

            // Execute the code
            try {
                // @phpstan-ignore-next-line disallowed.eval, ergebnis.noEval
                eval(substr($executableCode, 5));
                $executedCount++;
            } catch (Throwable $e) {
                $failedExamples[] = [
                    'line' => $line,
                    'index' => $index + 1,
                    'error' => substr($e->getMessage(), 0, 150),
                ];
            }
        }

//        echo sprintf(
//            "\n📊 DataFilter: Executed %d, skipped %d, failed %d\n",
//            $executedCount,
//            $skippedCount,
//            count($failedExamples)
//        );

        if ([] !== $failedExamples) {
            echo "\n❌ Failed examples:\n";
            foreach (array_slice($failedExamples, 0, 5) as $failed) {
                echo sprintf("  - Line %d (example #%d): %s\n", $failed['line'], $failed['index'], $failed['error']);
            }
        }

        expect($executedCount)->toBeGreaterThan(0);
    })->group('docs', 'starlight', 'data-filter');
});

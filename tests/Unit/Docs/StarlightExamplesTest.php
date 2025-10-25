<?php

declare(strict_types=1);

use Tests\Unit\Docs\DocumentationExampleExtractor;

describe('Starlight Documentation Examples', function (): void {
    beforeEach(function (): void {
        $this->docsPath = __DIR__ . '/../../../starlight/src/content/docs';
        $this->markdownFiles = DocumentationExampleExtractor::findMarkdownFiles($this->docsPath);
        $this->allExamples = DocumentationExampleExtractor::extractFromFiles($this->markdownFiles);
    });

    it('finds markdown files in documentation', function (): void {
        expect($this->markdownFiles)->toBeArray();
        expect($this->markdownFiles)->not->toBeEmpty();
    });

    it('extracts examples from all documentation files', function (): void {
        expect($this->allExamples)->toBeArray();
        expect($this->allExamples)->not->toBeEmpty();

        $totalExamples = 0;
        foreach ($this->allExamples as $examples) {
            $totalExamples += count($examples);
        }

        expect($totalExamples)->toBeGreaterThan(0);
    });

    it('executes all documentation examples successfully', function (): void {
        expect($this->allExamples)->not->toBeEmpty();

        $executedCount = 0;
        $skippedCount = 0;

        foreach ($this->allExamples as $filePath => $examples) {
            foreach ($examples as $index => $example) {
                $code = $example['code'];
                $line = $example['line'];

                // Skip examples that are just class declarations or incomplete code
                if (shouldSkipDocExample($code, $filePath)) {
                    $skippedCount++;
                    continue;
                }

                // Add mock data if needed
                $mockSetup = DocumentationExampleExtractor::generateMockDataSetup($code);
                $fullCode = $mockSetup . $code;

                // Prepare code for execution
                $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($fullCode);

                // Execute the code
                try {
                    eval(substr($executableCode, 5)); // Remove <?php tag for eval
                    $executedCount++;
                } catch (\Throwable $e) {
                    throw new \RuntimeException(
                        sprintf(
                            "Example #%d at line %d in %s failed:\n\nCode:\n%s\n\nError: %s\n\nTrace:\n%s",
                            $index + 1,
                            $line,
                            basename($filePath),
                            $code,
                            $e->getMessage(),
                            $e->getTraceAsString()
                        ),
                        0,
                        $e
                    );
                }
            }
        }

        expect($executedCount)->toBeGreaterThan(0, "No examples were executed");
    })->group('docs', 'starlight');

    it('validates DataMapper examples', function (): void {
        $dataMapperFile = $this->docsPath . '/main-classes/data-mapper.md';

        if (!file_exists($dataMapperFile)) {
            $this->markTestSkipped('DataMapper documentation not found');
        }

        $examples = DocumentationExampleExtractor::extractExamples($dataMapperFile);
        expect($examples)->not->toBeEmpty();

        // Test at least one example
        $foundExecutableExample = false;
        foreach ($examples as $example) {
            if (!shouldSkipDocExample($example['code'], $dataMapperFile)) {
                $foundExecutableExample = true;
                break;
            }
        }

        expect($foundExecutableExample)->toBeTrue('No executable examples found in DataMapper documentation');
    })->group('docs', 'starlight', 'data-mapper');

    it('validates DataAccessor examples', function (): void {
        $dataAccessorFile = $this->docsPath . '/main-classes/data-accessor.md';

        if (!file_exists($dataAccessorFile)) {
            $this->markTestSkipped('DataAccessor documentation not found');
        }

        $examples = DocumentationExampleExtractor::extractExamples($dataAccessorFile);
        expect($examples)->not->toBeEmpty();
    })->group('docs', 'starlight', 'data-accessor');

    it('validates Quick Start examples', function (): void {
        $quickStartFile = $this->docsPath . '/getting-started/quick-start.md';

        if (!file_exists($quickStartFile)) {
            $this->markTestSkipped('Quick Start documentation not found');
        }

        $examples = DocumentationExampleExtractor::extractExamples($quickStartFile);
        expect($examples)->not->toBeEmpty();
    })->group('docs', 'starlight', 'quick-start');
});

/**
 * Check if documentation example should be skipped.
 */
function shouldSkipDocExample(string $code, string $filePath): bool
{
    $trimmed = trim($code);

    // Skip class declarations without instantiation
    if (preg_match('/^class\s+\w+/', $trimmed) && !str_contains($code, 'new ') && !str_contains($code, '::')) {
        return true;
    }

    // Skip interface/trait/enum declarations
    if (preg_match('/^(interface|trait|enum)\s+\w+/', $trimmed)) {
        return true;
    }

    // Skip examples that are just method signatures
    if (preg_match('/^(public|private|protected)\s+function/', $trimmed) && !str_contains($code, '{')) {
        return true;
    }

    // Skip examples with placeholders
    if (str_contains($code, '...') || str_contains($code, '// ...')) {
        return true;
    }

    // Skip examples that reference undefined classes without mocking
    if (str_contains($code, 'UserDTO::fromArray') && !str_contains($code, 'class UserDTO')) {
        return true;
    }

    // Skip examples with incomplete syntax
    if (str_contains($code, '<?php') && strlen($trimmed) < 20) {
        return true;
    }

    // Skip architecture examples (they often contain pseudo-code)
    if (str_contains($filePath, 'architecture.md')) {
        return true;
    }

    // Skip contributing examples (they often contain test examples)
    if (str_contains($filePath, 'contributing.md')) {
        return true;
    }

    // Skip examples that use Laravel/Symfony specific features without framework
    if (!function_exists('collect') && str_contains($code, 'collect(')) {
        return true;
    }

    // Skip examples that reference Eloquent models
    if (str_contains($code, 'extends Model') || str_contains($code, 'Eloquent')) {
        return true;
    }

    // Skip examples that reference Doctrine entities
    if (str_contains($code, '@Entity') || str_contains($code, 'EntityManager')) {
        return true;
    }

    return false;
}


<?php

declare(strict_types=1);

use Tests\Unit\Docs\DocumentationExampleExtractor;

describe('README.md Examples', function (): void {
    beforeEach(function (): void {
        $this->readmePath = __DIR__ . '/../../../README.md';
        $this->examples = DocumentationExampleExtractor::extractExamples($this->readmePath);
    });

    it('extracts examples from README', function (): void {
        expect($this->examples)->toBeArray();
        expect($this->examples)->not->toBeEmpty();
    });

    it('executes all README examples successfully', function (): void {
        expect($this->examples)->not->toBeEmpty();

        $skippedCount = 0;
        foreach ($this->examples as $index => $example) {
            $code = $example['code'];
            $line = $example['line'];

            // Skip examples that are just class declarations or incomplete code
            if (shouldSkipExample($code)) {
                $skippedCount++;
                echo sprintf("\nSkipped example #%d at line %d\n", $index + 1, $line);
                continue;
            }

            // Add mock data if needed
            $mockSetup = DocumentationExampleExtractor::generateMockDataSetup($code);
            $fullCode = $mockSetup . $code;

            // Prepare code for execution with assertions
            $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($fullCode, true);

            // Execute the code
            try {
                // Suppress warnings and capture them
                set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($index, $line, $code, $example) {
                    if ($errno === E_WARNING || $errno === E_NOTICE) {
                        throw new \RuntimeException(
                            sprintf(
                                "Example #%d at line %d produced warning:\n%s\n\nCode:\n%s\n\nWarning: %s",
                                $index + 1,
                                $line,
                                $example['file'],
                                $code,
                                $errstr
                            )
                        );
                    }
                    return false;
                });

                eval(substr($executableCode, 5)); // Remove <?php tag for eval

                restore_error_handler();
            } catch (\Throwable $e) {
                restore_error_handler();
                throw new \RuntimeException(
                    sprintf(
                        "Example #%d at line %d failed:\n%s\n\nCode:\n%s\n\nError: %s",
                        $index + 1,
                        $line,
                        $example['file'],
                        $code,
                        $e->getMessage()
                    ),
                    0,
                    $e
                );
            }
        }
    })->group('docs', 'readme');

    it('validates specific README examples', function (): void {
        // Test the main banner example
        $bannerExample = <<<'PHP'
$apiResponse = [
    'data' => [
        'departments' => [
            ['users' => [['email' => 'alice@example.com'], ['email' => 'bob@example.com']]],
            ['users' => [['email' => 'charlie@example.com']]],
        ],
    ],
];

$accessor = new \event4u\DataHelpers\DataAccessor($apiResponse);
$emails = $accessor->get('data.departments.*.users.*.email');
PHP;

        $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($bannerExample);
        eval(substr($executableCode, 5));

        expect(array_values($emails))->toBe(['alice@example.com', 'bob@example.com', 'charlie@example.com']);
    })->group('docs', 'readme');
});

/**
 * Check if example should be skipped.
 */
function shouldSkipExample(string $code): bool
{
    $trimmed = trim($code);

    // Skip class declarations without instantiation (but allow if there's usage after the class)
    if (preg_match('/^class\s+\w+/', $trimmed)) {
        // Check if there's any code after the class definition
        if (!preg_match('/}\s*\n\s*\$/', $code)) {
            return true;
        }
    }

    // Skip interface/trait declarations
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

    return false;
}


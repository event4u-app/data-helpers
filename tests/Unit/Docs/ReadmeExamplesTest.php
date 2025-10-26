<?php

declare(strict_types=1);

use Tests\Unit\Docs\DocumentationExampleExtractor;

describe('README.md Examples', function(): void {
    beforeEach(function(): void {
        $this->readmePath = __DIR__ . '/../../../README.md';
        $this->examples = DocumentationExampleExtractor::extractExamples($this->readmePath);
    });

    it('extracts examples from README', function(): void {
        expect($this->examples)->toBeArray();
        expect($this->examples)->not->toBeEmpty();
    });

    it('executes all README examples successfully', function(): void {
        expect($this->examples)->not->toBeEmpty();

        $skippedCount = 0;
        $failedExamples = [];

        foreach ($this->examples as $index => $example) {
            $code = $example['code'];
            $line = $example['line'];

            // Skip examples that are just class declarations or incomplete code
            if (shouldSkipExample($code)) {
                $skippedCount++;
                # echo sprintf("\nSkipped example #%d at line %d\n", $index + 1, $line);
                continue;
            }

            // Add mock data if needed
            $mockSetup = DocumentationExampleExtractor::generateMockDataSetup($code);
            $fullCode = $mockSetup . $code;

            // Prepare code for execution with assertions
            $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($fullCode, true);

            // Execute the code in a separate process to avoid class redeclaration issues
            $tempFile = tempnam(sys_get_temp_dir(), 'readme_test_');

            // Add autoloader after <?php and declare(strict_types=1)
            $autoloaderPath = __DIR__ . '/../../../vendor/autoload.php';
            $codeWithoutPhpTag = substr($executableCode, 5); // Remove <?php

            // Check if code has declare(strict_types=1)
            if (preg_match('/^(\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;)/m', $codeWithoutPhpTag, $matches)) {
                // Insert autoloader after declare statement
                $codeWithAutoloader = "<?php\n\n" . $matches[1] . "\n\nrequire_once " . var_export(
                    $autoloaderPath,
                    true
                ) . ";\n\n" . substr(
                    $codeWithoutPhpTag,
                    strlen($matches[1])
                );
            } else {
                // No declare statement, add autoloader at the beginning
                $codeWithAutoloader = "<?php\n\nrequire_once " . var_export(
                    $autoloaderPath,
                    true
                ) . ";\n\n" . $codeWithoutPhpTag;
            }

            file_put_contents($tempFile, $codeWithAutoloader);

            try {
                // Execute in separate PHP process
                $output = [];
                $returnCode = 0;
                exec(sprintf('php %s 2>&1', escapeshellarg($tempFile)), $output, $returnCode);

                if (0 !== $returnCode) {
                    $failedExamples[] = [
                        'index' => $index + 1,
                        'line' => $line,
                        'file' => $example['file'],
                        'code' => $code,
                        'error' => implode("\n", $output),
                    ];
                }
            } finally {
                @unlink($tempFile);
            }
        }

        // Report all failures at once
        if ([] !== $failedExamples) {
            $errorMessage = "Failed examples:\n\n";
            foreach ($failedExamples as $failed) {
                $errorMessage .= sprintf(
                    "Example #%d at line %d:\n%s\n\nCode:\n%s\n\nError:\n%s\n\n%s\n\n",
                    $failed['index'],
                    $failed['line'],
                    $failed['file'],
                    substr($failed['code'], 0, 200) . (strlen($failed['code']) > 200 ? '...' : ''),
                    $failed['error'],
                    str_repeat('-', 80)
                );
            }
            throw new RuntimeException($errorMessage);
        }
    })->group('docs', 'readme');

    it('validates specific README examples', function(): void {
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
        /** @phpstan-ignore-next-line disallowed.eval, ergebnis.noEval */
        eval(substr($executableCode, 5));

        /** @phpstan-ignore-next-line variable.undefined */
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
    // Check if there's any code after the class definition
    if (preg_match('/^class\s+\w+/', $trimmed) && !preg_match('/}\s*\n\s*\$/', $code)) {
        return true;
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
    return str_contains($code, 'UserDTO::fromArray') && !str_contains($code, 'class UserDTO');
}

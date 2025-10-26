<?php

declare(strict_types=1);

namespace Tests\Unit\Docs;

/**
 * Extracts PHP code examples from Markdown documentation files.
 */
class DocumentationExampleExtractor
{
    /**
     * Extract all PHP code blocks from a markdown file.
     *
     * @return array<int, array{code: string, line: int, file: string}>
     */
    public static function extractExamples(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $examples = [];
        $lines = explode("\n", $content);
        $inCodeBlock = false;
        $currentCode = [];
        $startLine = 0;
        $language = '';
        $skipTest = false;
        $previousLine = '';

        foreach ($lines as $lineNumber => $line) {
            // Check for skip-test HTML comment before code block
            if (preg_match('/<!--\s*skip-test/', $previousLine)) {
                $skipTest = true;
            }

            // Check for code block start
            if (preg_match('/^```(php|PHP)(\s+skip-test)?/', $line, $matches)) {
                $inCodeBlock = true;
                $language = strtolower($matches[1]);
                // Also check for inline skip-test marker (for backwards compatibility)
                if (isset($matches[2]) && trim($matches[2]) === 'skip-test') {
                    $skipTest = true;
                }
                $startLine = $lineNumber + 1;
                $currentCode = [];
                continue;
            }

            // Check for code block end
            if ($inCodeBlock && preg_match('/^```/', $line)) {
                $inCodeBlock = false;
                if ($language === 'php' && !empty($currentCode) && !$skipTest) {
                    $code = implode("\n", $currentCode);
                    // Only include if it's actual executable code (not just comments or declarations)
                    if (self::isExecutableCode($code)) {
                        $examples[] = [
                            'code' => $code,
                            'line' => $startLine,
                            'file' => $filePath,
                        ];
                    }
                }
                // Reset skip flag after code block ends
                $skipTest = false;
                continue;
            }

            // Collect code lines
            if ($inCodeBlock) {
                $currentCode[] = $line;
            }

            // Remember previous line for skip-test detection
            $previousLine = $line;
        }

        return $examples;
    }

    /**
     * Check if code is executable (not just class/interface declarations or comments).
     */
    private static function isExecutableCode(string $code): bool
    {
        $trimmed = trim($code);

        // Skip empty code
        if (empty($trimmed)) {
            return false;
        }

        // Skip pure comments
        if (preg_match('/^(\/\/|\/\*|\*)/', $trimmed)) {
            return false;
        }

        // Skip code that only contains class/interface/trait definitions
        if (preg_match('/^(class|interface|trait|enum)\s+\w+/', $trimmed)) {
            // Check if there's any code that's not a class definition
            $lines = explode("\n", $code);
            $hasExecutableCode = false;
            $inClass = false;
            $braceCount = 0;

            foreach ($lines as $line) {
                $line = trim($line);

                // Track class definition
                if (preg_match('/^(class|interface|trait|enum)\s+/', $line)) {
                    $inClass = true;
                }

                // Count braces
                $braceCount += substr_count($line, '{') - substr_count($line, '}');

                // If we're outside class definitions and have non-empty code
                if ($braceCount === 0 && $inClass) {
                    $inClass = false;
                }

                // Check for executable code outside class definitions
                if (!$inClass && $braceCount === 0 && !empty($line) &&
                    !preg_match('/^(class|interface|trait|enum|use|namespace|\/\/|\/\*|\*)/', $line)) {
                    $hasExecutableCode = true;
                    break;
                }
            }

            if (!$hasExecutableCode) {
                return false;
            }
        }

        // Skip namespace declarations
        if (preg_match('/^namespace\s+/', $trimmed)) {
            return false;
        }

        // Skip use statements only
        if (preg_match('/^use\s+/', $trimmed) && !str_contains($code, ';') && !str_contains($code, '$')) {
            return false;
        }

        // Skip PHPDoc blocks
        if (preg_match('/^\/\*\*/', $trimmed)) {
            return false;
        }

        // Skip code with class definitions that include use statements (causes syntax errors in eval)
        if (preg_match('/class\s+\w+.*\{/s', $code) && preg_match('/use\s+event4u\\\\DataHelpers/', $code)) {
            return false;
        }

        // Skip Laravel/Symfony specific code
        if (self::hasUnavailableClasses($code)) {
            return false;
        }

        // Skip code that uses Laravel helper functions
        if (preg_match('/\b(now|today|config|env|app|route|url|asset|view|redirect|response|request|session|cache|auth|bcrypt|collect|dd|dump|logger|optional|rescue|retry|tap|throw_if|throw_unless|validator|old)\s*\(/', $code)) {
            return false;
        }

        // Skip TypeScript Generator examples (requires file system access)
        if (preg_match('/TypeScript\\\\Generator/', $code)) {
            return false;
        }

        return true;
    }

    /**
     * Check if code uses classes that are not available in the test environment.
     */
    private static function hasUnavailableClasses(string $code): bool
    {
        // Extract all use statements
        preg_match_all('/use\s+([^;]+);/', $code, $matches);

        if (empty($matches[1])) {
            return false;
        }

        $unavailablePatterns = [
            'Illuminate\\',
            'Symfony\\',
            'Doctrine\\',
            'Laravel\\',
            'App\\',
        ];

        foreach ($matches[1] as $useStatement) {
            $className = trim($useStatement);

            // Check if it's a framework class
            foreach ($unavailablePatterns as $pattern) {
                if (str_starts_with($className, $pattern)) {
                    return true;
                }
            }
        }

        // Also check for class references without use statements
        if (preg_match('/\b(Request|Controller|Model|Migration|Seeder)\b/', $code)) {
            // Check if these are Laravel/Symfony classes
            if (preg_match('/extends\s+(Controller|Model)/', $code)) {
                return true;
            }
            if (preg_match('/(Request|Response)\s+\$/', $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if code uses undefined variables.
     */
    private static function usesUndefinedVariables(string $code): bool
    {
        // Common patterns of undefined variables
        $undefinedPatterns = [
            '/\$source\)/' => '\$source', // DataMapper::from($source) without $source definition
            '/\$mapping\)/' => '\$mapping', // ->template($mapping) without $mapping definition
        ];

        foreach ($undefinedPatterns as $pattern => $varName) {
            if (preg_match($pattern, $code)) {
                // Check if variable is defined before use
                if (!preg_match('/' . preg_quote($varName, '/') . '\s*=\s*[^=]/', $code)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract expected results from comments in the code.
     * Returns array of ['variable' => 'expectedValue'] pairs.
     */
    public static function extractExpectedResults(string $code): array
    {
        $expectations = [];
        $lines = explode("\n", $code);

        foreach ($lines as $i => $line) {
            // Pattern: // $variable = [...]
            if (preg_match('/\/\/\s*\$(\w+)\s*=\s*(.+)$/', $line, $match)) {
                $varName = $match[1];
                $expectedValue = trim($match[2]);

                // Skip comments that are not result expectations
                if (str_contains($line, 'Execute') ||
                    str_contains($line, 'Remove') ||
                    str_contains($line, 'Check') ||
                    str_contains($line, 'Skip') ||
                    str_contains($line, 'Validate') ||
                    str_contains($line, 'Normalize')) {
                    continue;
                }

                if ($varName && $expectedValue) {
                    $expectations[$varName] = $expectedValue;
                }
            }
        }

        return $expectations;
    }

    /**
     * Prepare code for execution by wrapping it in a test context.
     */
    public static function prepareCodeForExecution(string $code, bool $withAssertions = false): string
    {
        $trimmed = trim($code);

        // If code already has <?php tag, remove it
        $trimmed = preg_replace('/^<\?php\s*/', '', $trimmed);

        // Fix namespace case (event4u -> event4u)
        $trimmed = preg_replace('/use\s+event4u\\\\/', 'use event4u\\', $trimmed);

        // Extract expected results before removing comments
        $expectations = [];
        if ($withAssertions) {
            $expectations = self::extractExpectedResults($trimmed);
        }

        // Remove result comments from code
        $trimmed = preg_replace('/\/\/\s*\[.*?\].*$/m', '', $trimmed);
        $trimmed = preg_replace('/\/\/\s*Result:.*$/m', '', $trimmed);
        $trimmed = preg_replace('/\/\/\s*\$\w+\s*=.*$/m', '', $trimmed);

        // Extract use statements from the code
        $useStatements = [];
        preg_match_all('/use\s+([^;]+);/', $trimmed, $matches);
        if (!empty($matches[0])) {
            $useStatements = $matches[0];
            // Remove use statements from the code (they'll be added at the top)
            $trimmed = preg_replace('/use\s+[^;]+;\s*/', '', $trimmed);
            $trimmed = trim($trimmed);
        }

        // Auto-discover and add missing use statements based on class usage in code
        $autoDiscoveredUseStatements = self::autoDiscoverUseStatements($trimmed);

        // Combine extracted use statements with auto-discovered ones
        $allUseStatements = array_merge($useStatements, $autoDiscoveredUseStatements);

        // Remove duplicates based on the class name (not just the full string)
        // This prevents "Cannot use X as X because the name is already in use" errors
        $uniqueUseStatements = [];
        $usedClasses = [];
        foreach ($allUseStatements as $useStatement) {
            // Extract the class name from "use Namespace\ClassName;"
            if (preg_match('/use\s+(.+?)(?:\s+as\s+(\w+))?\s*;/', $useStatement, $matches)) {
                $fullClassName = $matches[1];
                $alias = $matches[2] ?? basename(str_replace('\\', '/', $fullClassName));

                // Only add if this alias hasn't been used yet
                if (!isset($usedClasses[$alias])) {
                    $uniqueUseStatements[] = $useStatement;
                    $usedClasses[$alias] = $fullClassName;
                }
            }
        }

        $useStatementsStr = implode("\n", $uniqueUseStatements);

        // Build assertions for expected results
        $assertionsCode = '';
        if ($withAssertions && !empty($expectations)) {
            foreach ($expectations as $varName => $expectedValue) {
                // Parse the expected value
                $parsedValue = self::parseExpectedValue($expectedValue);
                if ($parsedValue !== null) {
                    $assertionsCode .= "\n// Validate expected result for \${$varName}\n";
                    $assertionsCode .= "if (!array_key_exists('{$varName}', get_defined_vars())) {\n";
                    $assertionsCode .= "    throw new \\RuntimeException('Variable \${$varName} is not defined');\n";
                    $assertionsCode .= "}\n";
                    $assertionsCode .= "\$expected_{$varName} = {$parsedValue};\n";
                    $assertionsCode .= "\$actual_{$varName} = \${$varName};\n";
                    $assertionsCode .= "// Deep comparison for arrays\n";
                    $assertionsCode .= "if (is_array(\$actual_{$varName}) && is_array(\$expected_{$varName})) {\n";
                    $assertionsCode .= "    // Check if both are associative or both are indexed\n";
                    $assertionsCode .= "    \$actualIsAssoc = array_keys(\$actual_{$varName}) !== range(0, count(\$actual_{$varName}) - 1);\n";
                    $assertionsCode .= "    \$expectedIsAssoc = array_keys(\$expected_{$varName}) !== range(0, count(\$expected_{$varName}) - 1);\n";
                    $assertionsCode .= "    // If expected is indexed but actual is associative, convert actual to indexed\n";
                    $assertionsCode .= "    if (!\$expectedIsAssoc && \$actualIsAssoc) {\n";
                    $assertionsCode .= "        \$actual_{$varName} = array_values(\$actual_{$varName});\n";
                    $assertionsCode .= "    }\n";
                    $assertionsCode .= "}\n";
                    $assertionsCode .= "if (\$actual_{$varName} !== \$expected_{$varName}) {\n";
                    $assertionsCode .= "    throw new \\RuntimeException(\n";
                    $assertionsCode .= "        sprintf(\n";
                    $assertionsCode .= "            'Expected result mismatch for \${$varName}. Expected: %s, Got: %s',\n";
                    $assertionsCode .= "            var_export(\$expected_{$varName}, true),\n";
                    $assertionsCode .= "            var_export(\$actual_{$varName}, true)\n";
                    $assertionsCode .= "        )\n";
                    $assertionsCode .= "    );\n";
                    $assertionsCode .= "}\n";
                }
            }
        }

        // Wrap in try-catch to capture errors
        $template = <<<'PHP'
<?php

declare(strict_types=1);

{USE_STATEMENTS}

// Execute the example code
try {
    {CODE}
    {ASSERTIONS}
} catch (\Throwable $e) {
    throw new \RuntimeException(
        "Example code failed: " . $e->getMessage(),
        0,
        $e
    );
}
PHP;

        return str_replace(
            ['{USE_STATEMENTS}', '{CODE}', '{ASSERTIONS}'],
            [$useStatementsStr, $trimmed, $assertionsCode],
            $template
        );
    }

    /**
     * Parse expected value from comment string to PHP code.
     */
    private static function parseExpectedValue(string $value): ?string
    {
        $value = trim($value);

        // Already valid PHP array syntax - check for balanced brackets
        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            // Count brackets to ensure they're balanced
            $openCount = substr_count($value, '[');
            $closeCount = substr_count($value, ']');
            if ($openCount === $closeCount) {
                // Escape single quotes in array values for proper PHP syntax
                return $value;
            }
        }

        // String value
        if (preg_match('/^["\'].*["\']$/', $value)) {
            return $value;
        }

        // Number
        if (is_numeric($value)) {
            return $value;
        }

        // Boolean
        if (in_array(strtolower($value), ['true', 'false'], true)) {
            return strtolower($value);
        }

        // null
        if (strtolower($value) === 'null') {
            return 'null';
        }

        return null;
    }

    /**
     * Extract all examples from multiple files.
     *
     * @param array<int, string> $filePaths
     * @return array<string, array<int, array{code: string, line: int, file: string}>>
     */
    public static function extractFromFiles(array $filePaths): array
    {
        $allExamples = [];

        foreach ($filePaths as $filePath) {
            $examples = self::extractExamples($filePath);
            if (!empty($examples)) {
                $allExamples[$filePath] = $examples;
            }
        }

        return $allExamples;
    }

    /**
     * Find all markdown files in a directory recursively.
     *
     * @return array<int, string>
     */
    public static function findMarkdownFiles(string $directory): array
    {
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Check if code needs mock data setup.
     */
    public static function needsMockData(string $code): bool
    {
        // Check for common patterns that need mock data
        $patterns = [
            '/\$user\s*=/',
            '/\$company\s*=/',
            '/\$products\s*=/',
            '/\$orders\s*=/',
            '/\$jsonData\s*=/',
            '/\$apiResponse\s*=/',
            '/\$source\s*=/',
            '/\$data\s*=/',
            '/new\s+User\(\)/',
            '/new\s+Company\(\)/',
            '/UserDTO::class/',
            '/Mappings::find/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate mock data setup for code that needs it.
     */
    public static function generateMockDataSetup(string $code): string
    {
        $setup = [];

        // Add common mock classes
        if (str_contains($code, 'UserDTO::class') || str_contains($code, 'new User()')) {
            $setup[] = <<<'PHP'
// Mock User class
class User {
    public function __construct(
        public string $name = 'John Doe',
        public string $email = 'john@example.com',
        public int $age = 30
    ) {}
}
PHP;
        }

        if (str_contains($code, 'new Company()')) {
            $setup[] = <<<'PHP'
// Mock Company class
class Company {
    public string $name = '';
    public array $departments = [];
}
PHP;
        }

        if (str_contains($code, 'Mappings::find')) {
            $setup[] = <<<'PHP'
// Mock Mappings class
class Mappings {
    public static function find(int $id): object {
        return (object)['template' => ['name' => '{{ user.name }}']];
    }
}
PHP;
        }

        return empty($setup) ? '' : implode("\n\n", $setup) . "\n\n";
    }

    /**
     * Auto-discover use statements based on class usage in code.
     *
     * @return array<int, string>
     */
    private static function autoDiscoverUseStatements(string $code): array
    {
        $useStatements = [];

        // Map of class names to their full namespaces
        $classMap = [
            // Main classes
            'DataAccessor' => 'event4u\DataHelpers\DataAccessor',
            'DataMutator' => 'event4u\DataHelpers\DataMutator',
            'DataMapper' => 'event4u\DataHelpers\DataMapper',
            'DataFilter' => 'event4u\DataHelpers\DataFilter',
            'SimpleDTO' => 'event4u\DataHelpers\SimpleDTO',

            // Helpers
            'MathHelper' => 'event4u\DataHelpers\Helpers\MathHelper',
            'EnvHelper' => 'event4u\DataHelpers\Helpers\EnvHelper',
            'DotPathHelper' => 'event4u\DataHelpers\Helpers\DotPathHelper',
            'ObjectHelper' => 'event4u\DataHelpers\Helpers\ObjectHelper',
            'ConfigHelper' => 'event4u\DataHelpers\Helpers\ConfigHelper',
            'CallbackHelper' => 'event4u\DataHelpers\Support\CallbackHelper',

            // DataMapper
            'Hooks' => 'event4u\DataHelpers\DataMapper\Hooks',
            'DataMapperHook' => 'event4u\DataHelpers\Enums\DataMapperHook',

            // SimpleDTO Pipeline
            'DTOPipeline' => 'event4u\DataHelpers\SimpleDTO\Pipeline\DTOPipeline',
            'PipelineStageInterface' => 'event4u\DataHelpers\SimpleDTO\Pipeline\PipelineStageInterface',
            'TransformerStage' => 'event4u\DataHelpers\SimpleDTO\Pipeline\TransformerStage',
            'NormalizerStage' => 'event4u\DataHelpers\SimpleDTO\Pipeline\NormalizerStage',
            'ValidationStage' => 'event4u\DataHelpers\SimpleDTO\Pipeline\ValidationStage',
            'CallbackStage' => 'event4u\DataHelpers\SimpleDTO\Pipeline\CallbackStage',

            // SimpleDTO Transformers (both namespaces for backwards compatibility)
            'TrimStringsTransformer' => 'event4u\DataHelpers\SimpleDTO\Transformers\TrimStringsTransformer',
            'RemoveNullValuesTransformer' => 'event4u\DataHelpers\SimpleDTO\Transformers\RemoveNullValuesTransformer',
            'LowercaseKeysTransformer' => 'event4u\DataHelpers\SimpleDTO\Transformers\LowercaseKeysTransformer',
            'LowerCaseTransformer' => 'event4u\DataHelpers\SimpleDTO\Transformers\LowerCaseTransformer',
            'UpperCaseTransformer' => 'event4u\DataHelpers\SimpleDTO\Transformers\UpperCaseTransformer',

            // SimpleDTO Normalizers
            'TypeNormalizer' => 'event4u\DataHelpers\SimpleDTO\Normalizers\TypeNormalizer',
            'CamelCaseNormalizer' => 'event4u\DataHelpers\SimpleDTO\Normalizers\CamelCaseNormalizer',
            'SnakeCaseNormalizer' => 'event4u\DataHelpers\SimpleDTO\Normalizers\SnakeCaseNormalizer',
            'DefaultValuesNormalizer' => 'event4u\DataHelpers\SimpleDTO\Normalizers\DefaultValuesNormalizer',

            // Test utilities
            'TrimStrings' => 'Tests\Utils\Docu\TrimStrings',
            'LowercaseEmails' => 'Tests\Utils\Docu\LowercaseEmails',
            'SkipEmptyValues' => 'Tests\Utils\Docu\SkipEmptyValues',

            // Test DTOs
            'UserDTO' => 'Tests\Docu\DTOs\UserDTO',
            'UserWithEmailsDTO' => 'Tests\Docu\DTOs\UserWithEmailsDTO',
            'UserWithRolesDTO' => 'Tests\Docu\DTOs\UserWithRolesDTO',
            'AddressDTO' => 'Tests\Docu\DTOs\AddressDTO',
            'EmailDTO' => 'Tests\Docu\DTOs\EmailDTO',
            'EmployeeDTO' => 'Tests\Docu\DTOs\EmployeeDTO',
            'DepartmentDTO' => 'Tests\Docu\DTOs\DepartmentDTO',
            'OrderDTO' => 'Tests\Docu\DTOs\OrderDTO',
            'ProfileDTO' => 'Tests\Utils\DTOs\ProfileDTO',
        ];

        // Find all class usages in the code
        foreach ($classMap as $className => $fullNamespace) {
            // Check for class usage patterns:
            // - ClassName::method()
            // - new ClassName()
            // - ClassName extends
            // - implements ClassName
            // - : ClassName (type hints)
            $patterns = [
                '/\b' . preg_quote($className, '/') . '::/m',
                '/\bnew\s+' . preg_quote($className, '/') . '\b/m',
                '/\bextends\s+' . preg_quote($className, '/') . '\b/m',
                '/\bimplements\s+' . preg_quote($className, '/') . '\b/m',
                '/:\s*' . preg_quote($className, '/') . '\b/m',
                '/\b' . preg_quote($className, '/') . '\s*\(/m', // Function calls
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $code)) {
                    $useStatements[] = "use {$fullNamespace};";
                    break; // Only add once per class
                }
            }
        }

        return array_unique($useStatements);
    }
}


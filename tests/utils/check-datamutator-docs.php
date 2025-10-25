<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Tests\Unit\Docs\DocumentationExampleExtractor;

$file = 'starlight/src/content/docs/main-classes/data-mutator.md';

if (!file_exists($file)) {
    echo "File not found: $file\n";
    exit(1);
}

$extractor = new DocumentationExampleExtractor();
$examples = $extractor->extractExamples($file);

echo "📄 File: $file\n";
echo "📊 Total examples: " . count($examples) . "\n\n";

$executed = 0;
$skipped = 0;
$failed = 0;

foreach ($examples as $i => $example) {
    $code = $example['code'];
    $line = $example['line'];

    // Skip if marked as skip
    if (str_contains($code, '// skip-test')) {
        $skipped++;
        continue;
    }

    // Skip property-only definitions
    $trimmedCode = trim($code);
    if (preg_match('/^(public|private|protected)\s+(readonly\s+)?[\w\\\\]+\s+\$\w+;?$/', $trimmedCode)) {
        $skipped++;
        continue;
    }

    try {
        $executableCode = DocumentationExampleExtractor::prepareCodeForExecution($code, true);

        ob_start();
        eval(substr($executableCode, 5)); // Remove <?php
        $output = ob_get_clean();
        $executed++;
    } catch (Throwable $e) {
        ob_end_clean();
        $failed++;
        echo "❌ Example #" . ($i + 1) . " (line $line) failed:\n";
        echo "   Error: " . $e->getMessage() . "\n";
        echo "   Code:\n";
        echo "   " . str_replace("\n", "\n   ", substr($code, 0, 200)) . "\n\n";
    }
}

echo "\n📊 Summary:\n";
echo "  ✅ Executed: $executed\n";
echo "  ⏭️  Skipped: $skipped\n";
echo "  ❌ Failed: $failed\n";


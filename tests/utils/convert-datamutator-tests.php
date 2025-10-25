<?php

declare(strict_types=1);

// Script to convert DataMutator tests from static API to fluent API

$testFiles = [
    'tests/Unit/DataMutator/DataMutatorTest.php',
    'tests/Unit/DataMutator/DataMutatorDoctrineTest.php',
    'tests/Unit/DataMutator/DataMutatorLaravelTest.php',
];

foreach ($testFiles as $file) {
    if (!file_exists($file)) {
        echo "⏭️  Skipping $file (not found)\n";
        continue;
    }

    $content = file_get_contents($file);
    $originalContent = $content;

    // Pattern 1: DataMutator::set($data, ...) -> DataMutator::make($data)->set(...)
    // Match: $result = DataMutator::set($data, 'path', 'value');
    $content = preg_replace(
        '/(\$\w+)\s*=\s*DataMutator::set\((\$\w+),\s*([^,]+),\s*([^)]+)\);/',
        '$1 = DataMutator::make($2)->set($3, $4)->toArray();',
        $content
    );

    // Pattern 2: DataMutator::set($data, ['path' => 'value']) -> DataMutator::make($data)->setMultiple([...])
    $content = preg_replace(
        '/(\$\w+)\s*=\s*DataMutator::set\((\$\w+),\s*(\[[^\]]+\])\);/',
        '$1 = DataMutator::make($2)->setMultiple($3)->toArray();',
        $content
    );

    // Pattern 3: DataMutator::merge($data, ...) -> DataMutator::make($data)->merge(...)
    $content = preg_replace(
        '/(\$\w+)\s*=\s*DataMutator::merge\((\$\w+),\s*([^,]+),\s*([^)]+)\);/',
        '$1 = DataMutator::make($2)->merge($3, $4)->toArray();',
        $content
    );

    // Pattern 4: DataMutator::merge($data, ['path' => 'value']) -> DataMutator::make($data)->setMultiple([...])
    $content = preg_replace(
        '/(\$\w+)\s*=\s*DataMutator::merge\((\$\w+),\s*(\[[^\]]+\])\);/',
        '$1 = DataMutator::make($2)->setMultiple($3)->toArray();',
        $content
    );

    // Pattern 5: DataMutator::unset($data, 'path') -> DataMutator::make($data)->unset('path')
    $content = preg_replace(
        '/(\$\w+)\s*=\s*DataMutator::unset\((\$\w+),\s*([^)]+)\);/',
        '$1 = DataMutator::make($2)->unset($3)->toArray();',
        $content
    );

    // Pattern 6: DataMutator::set($model, ...) without assignment (for models)
    $content = preg_replace(
        '/DataMutator::set\((\$\w+),\s*([^,]+),\s*([^)]+)\);/',
        'DataMutator::make($1)->set($2, $3);',
        $content
    );

    // Pattern 7: DataMutator::unset($model, ...) without assignment (for models)
    $content = preg_replace(
        '/DataMutator::unset\((\$\w+),\s*([^)]+)\);/',
        'DataMutator::make($1)->unset($2);',
        $content
    );

    // Pattern 8: Chained calls: $result1 = DataMutator::set($model, ...); $result2 = DataMutator::set($result1, ...);
    // This needs manual fixing, but we can detect it
    if (preg_match('/\$result\d+\s*=\s*DataMutator::/', $content)) {
        echo "⚠️  $file contains chained calls that need manual fixing\n";
    }

    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✅ Converted $file\n";
    } else {
        echo "⏭️  No changes needed for $file\n";
    }
}

echo "\n✅ Done! Please review the changes and fix any remaining issues manually.\n";


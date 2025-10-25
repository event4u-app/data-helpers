<?php

declare(strict_types=1);

// Script to convert DataMutator Doctrine tests from old API to fluent API

$file = 'tests/Unit/DataMutator/DataMutatorDoctrineTest.php';

if (!file_exists($file)) {
    echo "⏭️  File not found: $file\n";
    exit(1);
}

$content = file_get_contents($file);
$originalContent = $content;

// Pattern 1: Remove `$mutator = new DataMutator();` lines
$content = preg_replace('/\s*\$mutator = new DataMutator\(\);\s*\n/', '', $content);

// Pattern 2: $result = $mutator->set($collection, 'path', 'value'); -> $result = DataMutator::make($collection)->set('path', 'value')->toArray();
$content = preg_replace(
    '/(\$\w+)\s*=\s*\$mutator->set\((\$\w+),\s*([^,]+),\s*([^)]+)\);/',
    '$1 = DataMutator::make($2)->set($3, $4)->toArray();',
    $content
);

// Pattern 3: $result = $mutator->set($collection, 'path', 'value', true); -> $result = DataMutator::make($collection)->set('path', 'value', true)->toArray();
$content = preg_replace(
    '/(\$\w+)\s*=\s*\$mutator->set\((\$\w+),\s*([^,]+),\s*([^,]+),\s*true\);/',
    '$1 = DataMutator::make($2)->set($3, $4, true)->toArray();',
    $content
);

// Pattern 4: $result = $mutator->unset($collection, 'path'); -> $result = DataMutator::make($collection)->unset('path')->toArray();
$content = preg_replace(
    '/(\$\w+)\s*=\s*\$mutator->unset\((\$\w+),\s*([^)]+)\);/',
    '$1 = DataMutator::make($2)->unset($3)->toArray();',
    $content
);

if ($content !== $originalContent) {
    file_put_contents($file, $content);
    echo "✅ Converted $file\n";
} else {
    echo "⏭️  No changes needed for $file\n";
}

echo "\n✅ Done!\n";


<?php

declare(strict_types=1);

// Script to convert DataMutator documentation and examples from static API to fluent API

function convertToFluent(string $content): string
{
    // Pattern 1: Simple assignment with single set
    // $target = DataMutator::set($target, 'path', 'value');
    // -> DataMutator::make($target)->set('path', 'value');
    $content = preg_replace(
        '/(\$\w+)\s*=\s*DataMutator::set\(\1,\s*([^,]+),\s*([^)]+)\);/',
        'DataMutator::make($1)->set($2, $3);',
        $content
    );

    // Pattern 2: Assignment with merge
    // $target = DataMutator::merge($target, 'path', ['value']);
    // -> DataMutator::make($target)->merge('path', ['value']);
    $content = preg_replace(
        '/(\$\w+)\s*=\s*DataMutator::merge\(\1,\s*([^,]+),\s*(.+?)\);/s',
        'DataMutator::make($1)->merge($2, $3);',
        $content
    );

    // Pattern 3: Assignment with unset
    // $data = DataMutator::unset($data, 'path');
    // -> DataMutator::make($data)->unset('path');
    $content = preg_replace(
        '/(\$\w+)\s*=\s*DataMutator::unset\(\1,\s*([^)]+)\);/',
        'DataMutator::make($1)->unset($2);',
        $content
    );

    // Pattern 4: Multiple chained operations (detect and mark for manual review)
    // This is tricky because we need to combine multiple lines into one chain
    
    return $content;
}

// Find all documentation files
$docFiles = glob('starlight/src/content/docs/**/*.md');
$exampleFiles = glob('examples/**/*.php');

$allFiles = array_merge($docFiles ?: [], $exampleFiles ?: []);

$converted = 0;
$unchanged = 0;

foreach ($allFiles as $file) {
    if (!file_exists($file)) {
        continue;
    }

    $content = file_get_contents($file);
    $originalContent = $content;

    $newContent = convertToFluent($content);

    if ($newContent !== $originalContent) {
        file_put_contents($file, $newContent);
        echo "✅ Converted: $file\n";
        $converted++;
    } else {
        $unchanged++;
    }
}

echo "\n📊 Summary:\n";
echo "  ✅ Converted: $converted files\n";
echo "  ⏭️  Unchanged: $unchanged files\n";
echo "\n✅ Done! Please review the changes.\n";


<?php

declare(strict_types=1);

// Script to automatically fix API documentation files by adding missing variable definitions

$files = [
    'starlight/src/content/docs/api/data-accessor.md' => [
        'class' => 'DataAccessor',
        'var' => '$accessor',
        'data' => "['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]]",
    ],
    'starlight/src/content/docs/api/data-mutator.md' => [
        'class' => 'DataMutator',
        'var' => '$mutator',
        'data' => "['user' => ['name' => 'John', 'age' => 25]]",
    ],
    'starlight/src/content/docs/api/data-filter.md' => [
        'class' => 'DataFilter',
        'var' => '$filter',
        'data' => "[['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]]",
    ],
    'starlight/src/content/docs/api/data-mapper.md' => [
        'class' => 'DataMapper',
        'var' => '$mapper',
        'data' => "['user' => ['name' => 'John', 'email' => 'john@example.com']]",
    ],
    'starlight/src/content/docs/api/simple-dto.md' => [
        'class' => 'UserDTO',
        'var' => '$dto',
        'data' => "['name' => 'John', 'email' => 'john@example.com']",
    ],
];

foreach ($files as $file => $config) {
    if (!file_exists($file)) {
        echo "⏭️  Skipping $file (not found)\n";
        continue;
    }

    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $newLines = [];
    $inCodeBlock = false;
    $codeBlockContent = [];
    $codeBlockStart = 0;

    foreach ($lines as $i => $line) {
        if (str_starts_with($line, '```php')) {
            $inCodeBlock = true;
            $codeBlockContent = [];
            $codeBlockStart = $i;
            $newLines[] = $line;
            continue;
        }

        if ($inCodeBlock && str_starts_with($line, '```')) {
            $inCodeBlock = false;
            
            // Check if this code block needs fixing
            $code = implode("\n", $codeBlockContent);
            $needsFix = str_contains($code, $config['var']) && 
                       !str_contains($code, "new {$config['class']}") &&
                       !str_contains($code, "{$config['class']}::make") &&
                       !str_contains($code, "\$data = ");

            if ($needsFix) {
                // Add data and variable definition
                $newLines[] = "use event4u\\DataHelpers\\{$config['class']};";
                $newLines[] = "";
                $newLines[] = "\$data = {$config['data']};";
                
                if ($config['class'] === 'DataFilter') {
                    $newLines[] = "{$config['var']} = {$config['class']}::query(\$data);";
                } else {
                    $newLines[] = "{$config['var']} = new {$config['class']}(\$data);";
                }
                
                foreach ($codeBlockContent as $codeLine) {
                    $newLines[] = $codeLine;
                }
            } else {
                foreach ($codeBlockContent as $codeLine) {
                    $newLines[] = $codeLine;
                }
            }
            
            $newLines[] = $line;
            continue;
        }

        if ($inCodeBlock) {
            $codeBlockContent[] = $line;
        } else {
            $newLines[] = $line;
        }
    }

    $newContent = implode("\n", $newLines);
    file_put_contents($file, $newContent);
    echo "✅ Fixed $file\n";
}

echo "\n✅ Done!\n";


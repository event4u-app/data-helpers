<?php

declare(strict_types=1);

// Script to fix more documentation files

$files = [
    'starlight/src/content/docs/helpers/config-helper.md' => [
        'var' => '$config',
        'init' => "\$config = \\event4u\\DataHelpers\\Config\\ConfigHelper::getInstance();\n\$config->initialize(['app' => ['name' => 'MyApp', 'debug' => true], 'database' => ['host' => 'localhost', 'port' => 3306]]);",
    ],
    'starlight/src/content/docs/advanced/query-builder.md' => [
        'var' => '$query',
        'init' => "\$data = [['id' => 1, 'name' => 'Product 1', 'category' => 'Electronics', 'price' => 150], ['id' => 2, 'name' => 'Product 2', 'category' => 'Furniture', 'price' => 80]];\n\$query = \\event4u\\DataHelpers\\DataFilter::query(\$data);",
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

    foreach ($lines as $i => $line) {
        if (str_starts_with($line, '```php')) {
            $inCodeBlock = true;
            $codeBlockContent = [];
            $newLines[] = $line;
            continue;
        }

        if ($inCodeBlock && str_starts_with($line, '```')) {
            $inCodeBlock = false;

            // Check if this code block needs fixing
            $code = implode("\n", $codeBlockContent);
            $needsFix = str_contains($code, $config['var']) &&
                       !str_contains($code, $config['var'] . ' =');

            if ($needsFix) {
                // Add variable definition
                $initLines = explode("\n", $config['init']);
                foreach ($initLines as $initLine) {
                    $newLines[] = $initLine;
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


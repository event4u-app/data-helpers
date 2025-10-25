<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$files = [
    'src/DataMapper/Support/TemplateResolver.php',
    'src/DataMapper/Support/MappingEngine.php',
];

foreach ($files as $file) {
    echo "📄 Processing: $file\n";
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Pattern 1: $target = DataMutator::set($target, $path, $value);
    // Replace with: DataMutator::make($target)->set($path, $value);
    $pattern1 = '/\$(\w+)\s*=\s*DataMutator::set\(\$\1,\s*([^,]+),\s*([^)]+)\);/';
    $replacement1 = 'DataMutator::make($\1)->set(\2, \3);';
    $content = preg_replace($pattern1, $replacement1, $content);
    
    // Pattern 2: $target = DataMutator::set(expression, $path, $value);
    // Replace with: $temp = expression; DataMutator::make($temp)->set($path, $value); $target = $temp;
    $pattern2 = '/\$(\w+)\s*=\s*DataMutator::set\(([^$][^,]+),\s*([^,]+),\s*([^)]+)\);/';
    $matches = [];
    if (preg_match_all($pattern2, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        // Process from end to start to preserve offsets
        foreach (array_reverse($matches) as $match) {
            $fullMatch = $match[0][0];
            $offset = $match[0][1];
            $varName = $match[1][0];
            $expression = $match[2][0];
            $path = $match[3][0];
            $value = $match[4][0];
            
            $replacement = "\$temp_{$varName} = {$expression};\n" .
                          str_repeat(' ', strlen($match[0][0]) - strlen(ltrim($match[0][0]))) .
                          "DataMutator::make(\$temp_{$varName})->set({$path}, {$value});\n" .
                          str_repeat(' ', strlen($match[0][0]) - strlen(ltrim($match[0][0]))) .
                          "\${$varName} = \$temp_{$varName};";
            
            $content = substr_replace($content, $replacement, $offset, strlen($fullMatch));
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "  ✅ Updated\n";
    } else {
        echo "  ⏭️  No changes needed\n";
    }
}

echo "\n✅ Done!\n";


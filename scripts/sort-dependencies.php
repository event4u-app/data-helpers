#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Sort composer.json dependencies alphabetically.
 *
 * - Extensions (php, ext-*) are sorted alphabetically at the top of "require"
 * - Other packages in "require" are sorted alphabetically after extensions
 * - All packages in "require-dev" are sorted alphabetically
 */

$composerFile = __DIR__ . '/../composer.json';

if (!file_exists($composerFile)) {
    echo "❌  composer.json not found!\n";
    exit(1);
}

echo "📦 Sorting composer.json dependencies...\n\n";

// Read composer.json
$content = file_get_contents($composerFile);
if (false === $content) {
    echo "❌  Failed to read composer.json!\n";
    exit(1);
}

$data = json_decode($content, true);
if (null === $data || !is_array($data)) {
    echo "❌  Failed to parse composer.json!\n";
    exit(1);
}

/** @var array<string, mixed> $data */

$extensionsCount = 0;
$packagesCount = 0;
$devCount = 0;

// Sort require section
if (isset($data['require']) && is_array($data['require'])) {
    $extensions = [];
    $packages = [];

    foreach ($data['require'] as $name => $version) {
        // Check if it's an extension (php or ext-*)
        if ('php' === $name || str_starts_with((string)$name, 'ext-')) {
            $extensions[$name] = $version;
        } else {
            $packages[$name] = $version;
        }
    }

    // Sort both arrays alphabetically
    ksort($extensions, SORT_STRING);
    ksort($packages, SORT_STRING);

    // Merge: extensions first, then packages
    $data['require'] = array_merge($extensions, $packages);

    $extensionsCount = count($extensions);
    $packagesCount = count($packages);

    echo "✅  Sorted 'require' section:\n";
    echo "   - Extensions: {$extensionsCount} packages\n";
    echo "   - Other: {$packagesCount} packages\n\n";
}

// Sort require-dev section
if (isset($data['require-dev']) && is_array($data['require-dev'])) {
    ksort($data['require-dev'], SORT_STRING);

    $devCount = count($data['require-dev']);

    echo "✅  Sorted 'require-dev' section:\n";
    echo "   - Total: {$devCount} packages\n\n";
}

// Convert empty arrays back to objects for suggest and conflict
if (isset($data['suggest']) && is_array($data['suggest']) && empty($data['suggest'])) {
    $data['suggest'] = new stdClass();
}
if (isset($data['conflict']) && is_array($data['conflict']) && empty($data['conflict'])) {
    $data['conflict'] = new stdClass();
}

// Encode with pretty print
$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (false === $json) {
    echo "❌  Failed to encode composer.json!\n";
    exit(1);
}

// Replace 4-space indentation with 2-space indentation to match original format
$json = preg_replace_callback(
    '/^(    )+/m',
    fn($matches) => str_repeat('  ', (int)(strlen($matches[0]) / 4)),
    $json
);

// Write back to file
$result = file_put_contents($composerFile, $json . "\n");
if (false === $result) {
    echo "❌  Failed to write composer.json!\n";
    exit(1);
}

echo "🎉 composer.json dependencies sorted successfully!\n";
echo "\n";
echo "📖 Review changes with: git diff composer.json\n";

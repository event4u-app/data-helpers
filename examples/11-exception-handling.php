<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\Exceptions\CollectedExceptionsException;
use event4u\DataHelpers\Exceptions\UndefinedSourceValueException;

echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                         Exception Handling Examples                          ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// Example 1: Default Mode (Production) - Collect Exceptions
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Example 1: Default Mode (Production) - Collect Exceptions\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

MapperExceptions::reset(); // Start with defaults

$source = [
    'users' => [
        ['name' => 'John', 'email' => 'john@example.com'],
        ['name' => 'Jane'],  // Missing email
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ],
];

$mapping = [
    'contacts' => [
        '*' => [
            'name' => '{{ users.*.name }}',
            'email' => '{{ users.*.email }}',
        ],
    ],
];

echo "Source data:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . "\n\n";

echo "Settings:\n";
echo "  - Exceptions enabled: " . (MapperExceptions::isExceptionsEnabled() ? 'true' : 'false') . "\n";
echo "  - Collect exceptions: " . (MapperExceptions::isCollectExceptionsEnabled() ? 'true' : 'false') . "\n";
echo "  - Throw on undefined source: " . (MapperExceptions::isThrowOnUndefinedSourceEnabled() ? 'true' : 'false') . "\n\n";

$result = DataMapper::source($source)
    ->target([])
    ->template($mapping)
    ->map()
    ->getTarget();

echo "Result:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "✅  Mapping completed successfully (missing values were skipped)\n\n";

// ============================================================================
// Example 2: Strict Mode (Development) - Throw Immediately
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Example 2: Strict Mode (Development) - Throw Immediately\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

MapperExceptions::reset();
MapperExceptions::setCollectExceptionsEnabled(false);  // Throw immediately
MapperExceptions::setThrowOnUndefinedSourceEnabled(true);  // Validate source

$source = ['name' => 'John'];
$mapping = [
    'user' => [
        'name' => '{{ name }}',
        'email' => '{{ email }}',  // Missing in source
    ],
];

echo "Source data:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . "\n\n";

echo "Settings:\n";
echo "  - Collect exceptions: " . (MapperExceptions::isCollectExceptionsEnabled() ? 'true' : 'false') . "\n";
echo "  - Throw on undefined source: " . (MapperExceptions::isThrowOnUndefinedSourceEnabled() ? 'true' : 'false') . "\n\n";

try {
    $result = DataMapper::source($source)
        ->target([])
        ->template($mapping)
        ->map()
        ->getTarget();
    echo "Result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
} catch (UndefinedSourceValueException $undefinedSourceValueException) {
    echo "❌  Exception caught (as expected):\n";
    echo "    Type: " . $undefinedSourceValueException::class . "\n";
    echo "    Message: " . $undefinedSourceValueException->getMessage() . "\n";
    echo "    Path: " . $undefinedSourceValueException->getPath() . "\n\n";
}

// ============================================================================
// Example 3: Collect Multiple Exceptions
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Example 3: Collect Multiple Exceptions\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

MapperExceptions::reset();
MapperExceptions::setCollectExceptionsEnabled(true);  // Collect exceptions
MapperExceptions::setThrowOnUndefinedSourceEnabled(true);  // Validate source

$source = [
    'users' => [
        ['name' => 'John'],  // Missing email and phone
        ['name' => 'Jane'],  // Missing email and phone
    ],
];

$mapping = [
    'contacts' => [
        '*' => [
            'name' => '{{ users.*.name }}',
            'email' => '{{ users.*.email }}',  // Missing
            'phone' => '{{ users.*.phone }}',  // Missing
        ],
    ],
];

echo "Source data:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . "\n\n";

echo "Settings:\n";
echo "  - Collect exceptions: " . (MapperExceptions::isCollectExceptionsEnabled() ? 'true' : 'false') . "\n";
echo "  - Throw on undefined source: " . (MapperExceptions::isThrowOnUndefinedSourceEnabled() ? 'true' : 'false') . "\n\n";

try {
    $result = DataMapper::source($source)
        ->target([])
        ->template($mapping)
        ->map()
        ->getTarget();
    echo "Result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
} catch (CollectedExceptionsException $collectedExceptionsException) {
    echo "❌  Multiple exceptions collected:\n";
    echo "    Total: " . count($collectedExceptionsException->getExceptions()) . " exceptions\n\n";
    foreach ($collectedExceptionsException->getExceptions() as $i => $exception) {
        echo "    Exception " . ($i + 1) . ":\n";
        echo "      Type: " . $exception::class . "\n";
        echo "      Message: " . $exception->getMessage() . "\n";
        if ($exception instanceof UndefinedSourceValueException) {
            echo "      Path: " . $exception->getPath() . "\n";
        }
        echo "\n";
    }
}

// ============================================================================
// Example 4: Silent Mode - Suppress All Exceptions
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Example 4: Silent Mode - Suppress All Exceptions\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

MapperExceptions::reset();
MapperExceptions::setExceptionsEnabled(false);  // Silent mode

$source = [
    'users' => [
        ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
        ['name' => 'Jane'],  // Missing email and age
        ['email' => 'bob@example.com'],  // Missing name
    ],
];

$mapping = [
    'contacts' => [
        '*' => [
            'name' => '{{ users.*.name }}',
            'email' => '{{ users.*.email }}',
            'age' => '{{ users.*.age }}',
        ],
    ],
];

echo "Source data:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . "\n\n";

echo "Settings:\n";
echo "  - Exceptions enabled: " . (MapperExceptions::isExceptionsEnabled() ? 'true' : 'false') . "\n\n";

$result = DataMapper::source($source)
    ->target([])
    ->template($mapping)
    ->map()
    ->getTarget();

echo "Result (missing values silently skipped):\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "✅  Mapping completed without exceptions (best-effort processing)\n\n";

// Re-enable exceptions for normal operations
MapperExceptions::setExceptionsEnabled(true);

// ============================================================================
// Example 5: Using Default Values to Avoid Exceptions
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Example 5: Using Default Values to Avoid Exceptions\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

MapperExceptions::reset();
MapperExceptions::setThrowOnUndefinedSourceEnabled(true);  // Strict mode

$source = ['name' => 'John'];

$mapping = [
    'user' => [
        'name' => '{{ name }}',
        'email' => '{{ email ?? "no-email@example.com" }}',  // Default value
        'role' => '{{ role ?? "USER" }}',  // Default value
    ],
];

echo "Source data:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . "\n\n";

echo "Settings:\n";
echo "  - Throw on undefined source: " . (MapperExceptions::isThrowOnUndefinedSourceEnabled() ? 'true' : 'false') . "\n\n";

echo "Mapping with default values:\n";
echo "  - email: {{ email ?? \"no-email@example.com\" }}\n";
echo "  - role: {{ role ?? \"USER\" }}\n\n";

$result = DataMapper::source($source)
    ->target([])
    ->template($mapping)
    ->map()
    ->getTarget();

echo "Result:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "✅  No exceptions thrown (default values used for missing fields)\n\n";

// ============================================================================
// Example 6: Checking for Collected Exceptions
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Example 6: Checking for Collected Exceptions\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

MapperExceptions::reset();
MapperExceptions::setCollectExceptionsEnabled(true);

$source = ['name' => 'John'];
$mapping = ['email' => '{{ email }}'];  // Missing field

echo "Source data:\n";
echo json_encode($source, JSON_PRETTY_PRINT) . "\n\n";

$result = DataMapper::source($source)
    ->target([])
    ->template($mapping)
    ->map()
    ->getTarget();

echo "Result:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

if (MapperExceptions::hasExceptions()) {
    echo "⚠️  Exceptions were collected during mapping:\n";
    $exceptions = MapperExceptions::getExceptions();
    echo "    Total: " . count($exceptions) . " exception(s)\n\n";

    foreach ($exceptions as $i => $exception) {
        echo "    Exception " . ($i + 1) . ": " . $exception->getMessage() . "\n";
    }
    echo "\n";

    // Clear exceptions for next operation
    MapperExceptions::clearExceptions();
    echo "✅  Exceptions cleared\n\n";
} else {
    echo "✅  No exceptions collected\n\n";
}

echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                              Summary                                         ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "Exception Handling Modes:\n\n";
echo "  1. Default (Production):  Collect exceptions, continue processing\n";
echo "  2. Strict (Development):  Throw immediately, fail fast\n";
echo "  3. Silent (Migration):    Suppress all exceptions, best-effort\n";
echo "  4. Partial Strict:        Collect + validate source/target\n\n";

echo "Key Methods:\n\n";
echo "  MapperExceptions::setExceptionsEnabled(bool)         - Master switch\n";
echo "  MapperExceptions::setCollectExceptionsEnabled(bool)  - Collect vs throw\n";
echo "  MapperExceptions::setThrowOnUndefinedSourceEnabled() - Validate source\n";
echo "  MapperExceptions::setThrowOnUndefinedTargetEnabled() - Validate target\n";
echo "  MapperExceptions::reset()                            - Reset to defaults\n";
echo "  MapperExceptions::hasExceptions()                    - Check for errors\n";
echo "  MapperExceptions::getExceptions()                    - Get collected errors\n";
echo "  MapperExceptions::clearExceptions()                  - Clear errors\n\n";

echo "Best Practices:\n\n";
echo "  ✓ Use default mode in production (collect exceptions)\n";
echo "  ✓ Use strict mode in development (throw immediately)\n";
echo "  ✓ Use default values ({{ field ?? \"default\" }}) when possible\n";
echo "  ✓ Always reset() between different contexts\n";
echo "  ✓ Log collected exceptions even in production\n";
echo "  ✓ Re-enable exceptions after using silent mode\n\n";


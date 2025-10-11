# Exception Handling

The DataMapper provides flexible exception handling with options to collect exceptions or throw them immediately. This allows you to handle errors gracefully in production while getting detailed feedback during development.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Exception Types](#exception-types)
- [Collecting vs. Throwing Immediately](#collecting-vs-throwing-immediately)
- [Undefined Value Handling](#undefined-value-handling)
- [API Reference](#api-reference)
- [Examples](#examples)

## Overview

The exception handling system provides four main features:

1. **Master Exception Switch**: Globally enable or disable all exception handling
2. **Exception Collection**: Collect multiple exceptions during mapping and throw them all at once
3. **Immediate Throwing**: Throw exceptions immediately when they occur
4. **Undefined Value Detection**: Optionally throw exceptions when source or target paths don't exist

All exception handling is managed through the `MapperExceptions` class, which is accessed via static methods on `DataMapper`.

## Configuration

### Default Settings

By default, the DataMapper is configured for production use:

```php
use event4u\DataHelpers\DataMapper;

// Default settings (no need to set explicitly)
DataMapper::setExceptionsEnabled(true);               // Exception handling enabled
DataMapper::setCollectExceptionsEnabled(true);        // Collect exceptions
DataMapper::setThrowOnUndefinedSourceEnabled(false);  // Don't throw on missing source
DataMapper::setThrowOnUndefinedTargetEnabled(false);  // Don't throw on missing target
```

### Silent Mode (No Exceptions)

For scenarios where you want to completely suppress all exceptions:

```php
// Silent mode: ignore all exceptions
DataMapper::setExceptionsEnabled(false);
```

When exceptions are disabled globally, all exceptions are silently ignored - they are neither thrown nor collected. This is useful for:
- Data migration scripts where you want to process as much as possible
- Logging/monitoring where missing data should not stop processing
- Best-effort data synchronization

**Note:** When `setExceptionsEnabled(false)`, all other exception settings are ignored.

### Development Settings

For development, you might want stricter error checking:

```php
// Strict mode: throw immediately on any error
DataMapper::setExceptionsEnabled(true);               // Ensure exceptions are enabled
DataMapper::setCollectExceptionsEnabled(false);       // Throw immediately
DataMapper::setThrowOnUndefinedSourceEnabled(true);   // Validate source data
DataMapper::setThrowOnUndefinedTargetEnabled(true);   // Validate target structure
```

### Resetting to Defaults

```php
// Reset all settings to defaults
DataMapper::reset();
```

## Exception Types

The DataMapper uses custom exception types for different error scenarios:

### Core Exceptions

- **`InvalidMappingException`** - Invalid mapping configuration
  - Extends: `InvalidArgumentException`
  - Example: Invalid template syntax, malformed mapping structure

- **`ConversionException`** - Data conversion errors
  - Extends: `RuntimeException`
  - Example: Cannot convert array to XML, JSON encoding failed

- **`ConfigurationException`** - Configuration errors
  - Extends: `RuntimeException`
  - Example: Invalid filter configuration, missing required settings

### Undefined Value Exceptions

- **`UndefinedSourceValueException`** - Source path doesn't exist
  - Extends: `RuntimeException`
  - Only thrown when `setThrowOnUndefinedSourceEnabled(true)`
  - Contains: source path and source data

- **`UndefinedTargetValueException`** - Target parent path doesn't exist
  - Extends: `RuntimeException`
  - Only thrown when `setThrowOnUndefinedTargetEnabled(true)`
  - Contains: target path and target data

### Collected Exceptions

- **`CollectedExceptionsException`** - Wrapper for multiple exceptions
  - Extends: `RuntimeException`
  - Contains: array of collected exceptions
  - Only thrown when multiple exceptions are collected

**Note:** If only one exception is collected, it's thrown directly without wrapping.

## Collecting vs. Throwing Immediately

### Exception Collection (Default)

When exception collection is enabled, exceptions are collected during mapping and thrown at the end:

```php
DataMapper::setCollectExceptionsEnabled(true);

$source = ['name' => 'John'];
$target = [];
$mapping = [
    'result1' => '{{ invalid.path }}',  // Would cause exception
    'result2' => '{{ another.invalid }}',  // Would cause another exception
];

try {
    $result = DataMapper::map($source, $target, $mapping);
} catch (CollectedExceptionsException $e) {
    // Multiple exceptions collected
    foreach ($e->getExceptions() as $exception) {
        echo $exception->getMessage() . "\n";
    }
} catch (Throwable $e) {
    // Single exception thrown directly
    echo $e->getMessage() . "\n";
}
```

**Benefits:**
- Continue processing even when errors occur
- Get all errors at once instead of one at a time
- Better for production environments

### Immediate Throwing

When exception collection is disabled, exceptions are thrown immediately:

```php
DataMapper::setCollectExceptionsEnabled(false);

$source = ['name' => 'John'];
$target = [];
$mapping = [
    'result1' => '{{ invalid.path }}',  // Throws immediately
    'result2' => '{{ another.invalid }}',  // Never reached
];

try {
    $result = DataMapper::map($source, $target, $mapping);
} catch (Throwable $e) {
    // First exception thrown immediately
    echo $e->getMessage() . "\n";
}
```

**Benefits:**
- Fail fast on first error
- Easier debugging (clear stack trace)
- Better for development environments

## Undefined Value Handling

### Undefined Source Values

By default, missing source values are treated as `null` and skipped (when `skipNull=true`):

```php
$source = ['name' => 'John'];
$mapping = ['email' => '{{ user.email }}'];  // user.email doesn't exist

// Default: no exception, value is skipped
$result = DataMapper::map($source, [], $mapping);
// Result: [] (empty, because null was skipped)
```

Enable strict checking:

```php
DataMapper::setThrowOnUndefinedSourceEnabled(true);

try {
    $result = DataMapper::map($source, [], $mapping);
} catch (UndefinedSourceValueException $e) {
    echo "Missing source path: " . $e->getPath() . "\n";
    // Output: Missing source path: user.email
}
```

**Exception:** No exception is thrown if:
- A default value is provided: `'{{ user.email ?? "default@example.com" }}'`
- Filters are applied: `'{{ user.email | upper }}'`

### Undefined Target Values

By default, missing target parent paths are automatically created:

```php
$source = ['city' => 'Berlin'];
$target = [];  // Empty target
$mapping = ['user.address.city' => '{{ city }}'];

// Default: parent paths are created automatically
$result = DataMapper::map($source, $target, $mapping);
// Result: ['user' => ['address' => ['city' => 'Berlin']]]
```

Enable strict checking:

```php
DataMapper::setThrowOnUndefinedTargetEnabled(true);

try {
    $result = DataMapper::map($source, [], $mapping);
} catch (UndefinedTargetValueException $e) {
    echo "Missing target path: " . $e->getPath() . "\n";
    // Output: Missing target path: user.address
}
```

**Note:** The exception is thrown for the **parent path**, not the final field. In the example above, `user.address` must exist, but `city` will be created.

## API Reference

### Configuration Methods

```php
// Master exception switch (globally enable/disable all exceptions)
DataMapper::setExceptionsEnabled(bool $enabled): void
DataMapper::isExceptionsEnabled(): bool

// Exception collection
DataMapper::setCollectExceptionsEnabled(bool $enabled): void
DataMapper::isCollectExceptionsEnabled(): bool

// Undefined source value handling
DataMapper::setThrowOnUndefinedSourceEnabled(bool $enabled): void
DataMapper::isThrowOnUndefinedSourceEnabled(): bool

// Undefined target value handling
DataMapper::setThrowOnUndefinedTargetEnabled(bool $enabled): void
DataMapper::isThrowOnUndefinedTargetEnabled(): bool

// Reset all settings
DataMapper::reset(): void
DataMapper::resetExceptions(): void  // Alias for reset()
```

### Exception Inspection

```php
// Check if exceptions were collected
DataMapper::hasExceptions(): bool

// Get collected exceptions
DataMapper::getExceptions(): array

// Clear collected exceptions
DataMapper::clearExceptions(): void
```

## Examples

### Example 1: Production Mode (Graceful Error Handling)

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\Exceptions\CollectedExceptionsException;

// Production settings
DataMapper::setCollectExceptionsEnabled(true);
DataMapper::setThrowOnUndefinedSourceEnabled(false);
DataMapper::setThrowOnUndefinedTargetEnabled(false);

$source = [
    'users' => [
        ['name' => 'John', 'email' => 'john@example.com'],
        ['name' => 'Jane'],  // Missing email
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

try {
    $result = DataMapper::map($source, [], $mapping);

    // Check if there were any warnings
    if (DataMapper::hasExceptions()) {
        $exceptions = DataMapper::getExceptions();
        // Log exceptions but continue
        foreach ($exceptions as $e) {
            error_log($e->getMessage());
        }
    }

    // Use result even if some values were missing
    return $result;

} catch (Throwable $e) {
    // Handle critical errors
    error_log("Critical mapping error: " . $e->getMessage());
    return [];
}
```

### Example 2: Development Mode (Strict Validation)

```php
use event4u\DataHelpers\DataMapper;

// Development settings
DataMapper::setCollectExceptionsEnabled(false);
DataMapper::setThrowOnUndefinedSourceEnabled(true);
DataMapper::setThrowOnUndefinedTargetEnabled(true);

$source = ['name' => 'John'];
$target = [];
$mapping = [
    'user.profile.name' => '{{ name }}',
    'user.profile.email' => '{{ email }}',  // Missing in source
];

try {
    $result = DataMapper::map($source, $target, $mapping);
} catch (UndefinedSourceValueException $e) {
    // Fail fast on first error
    echo "Missing required field: " . $e->getPath() . "\n";
    echo "Source data: " . json_encode($e->getSource()) . "\n";
    exit(1);
} catch (UndefinedTargetValueException $e) {
    echo "Target structure incomplete: " . $e->getPath() . "\n";
    exit(1);
}
```

### Example 3: Partial Strict Mode

```php
use event4u\DataHelpers\DataMapper;

// Collect exceptions but validate source data
DataMapper::setCollectExceptionsEnabled(true);
DataMapper::setThrowOnUndefinedSourceEnabled(true);
DataMapper::setThrowOnUndefinedTargetEnabled(false);

$source = [
    'users' => [
        ['name' => 'John', 'email' => 'john@example.com'],
        ['name' => 'Jane', 'email' => 'jane@example.com'],
    ],
];

$mapping = [
    'contacts' => [
        '*' => [
            'name' => '{{ users.*.name }}',
            'email' => '{{ users.*.email }}',
            'phone' => '{{ users.*.phone }}',  // Missing in all users
        ],
    ],
];

try {
    $result = DataMapper::map($source, [], $mapping);
} catch (CollectedExceptionsException $e) {
    echo "Found " . count($e->getExceptions()) . " errors:\n";
    foreach ($e->getExceptions() as $exception) {
        echo "- " . $exception->getMessage() . "\n";
    }
} catch (UndefinedSourceValueException $e) {
    echo "Missing required source field: " . $e->getPath() . "\n";
}
```

### Example 4: Using Default Values to Avoid Exceptions

```php
use event4u\DataHelpers\DataMapper;

// Strict mode
DataMapper::setThrowOnUndefinedSourceEnabled(true);

$source = ['name' => 'John'];

// This would throw an exception
// $mapping = ['email' => '{{ email }}'];

// This won't throw because of default value
$mapping = ['email' => '{{ email ?? "no-email@example.com" }}'];

$result = DataMapper::map($source, [], $mapping);
// Result: ['email' => 'no-email@example.com']
```

### Example 5: Resetting Between Operations

```php
use event4u\DataHelpers\DataMapper;

// First operation: strict mode
DataMapper::setCollectExceptionsEnabled(false);
DataMapper::setThrowOnUndefinedSourceEnabled(true);

try {
    $result1 = DataMapper::map($source1, [], $mapping1);
} catch (Throwable $e) {
    // Handle error
}

// Reset to defaults for next operation
DataMapper::reset();

// Second operation: default mode
$result2 = DataMapper::map($source2, [], $mapping2);
```

### Example 6: Silent Mode for Data Migration

```php
use event4u\DataHelpers\DataMapper;

// Disable all exceptions for best-effort migration
DataMapper::setExceptionsEnabled(false);

$users = [
    ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
    ['name' => 'Jane'],  // Missing email and age
    ['email' => 'bob@example.com'],  // Missing name
];

$mapping = [
    'contacts' => [
        '*' => [
            'fullname' => '{{ users.*.name }}',
            'email' => '{{ users.*.email }}',
            'age' => '{{ users.*.age }}',
        ],
    ],
];

// Process all users, skip missing values silently
$result = DataMapper::map(['users' => $users], [], $mapping);

// Result will contain only available data:
// [
//   'contacts' => [
//     ['fullname' => 'John', 'email' => 'john@example.com', 'age' => 30],
//     ['fullname' => 'Jane'],
//     ['email' => 'bob@example.com'],
//   ]
// ]

// Re-enable exceptions for normal operations
DataMapper::setExceptionsEnabled(true);
```

## Best Practices

1. **Use defaults in production**: Exception collection is safer for production environments
2. **Enable strict mode in tests**: Catch missing data early in development
3. **Use silent mode sparingly**: Only disable exceptions for specific use cases like migrations
4. **Reset between operations**: Always reset settings when switching contexts
5. **Use default values**: Prefer `{{ field ?? "default" }}` over exception handling
6. **Log collected exceptions**: Even in production, log collected exceptions for monitoring
7. **Test both modes**: Test your mappings with both strict and lenient settings
8. **Re-enable after silent mode**: Always re-enable exceptions after using silent mode

## See Also

- [Data Mapper](data-mapper.md) - Main DataMapper documentation
- [Template Expressions](template-expressions.md) - Template syntax and default values
- [Configuration](configuration.md) - Global configuration options

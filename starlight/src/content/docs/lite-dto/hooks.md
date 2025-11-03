---
title: Lifecycle Hooks
description: Lifecycle hooks for LiteDto to customize behavior at different stages
---

LiteDto provides **lifecycle hooks** similar to Laravel Model events. These hooks allow you to customize behavior at different stages of the DTO lifecycle without modifying the core logic.

## Overview

Hooks are **protected methods** that you can override in your DTO classes. When not overridden, they have **minimal overhead** (just an empty method call), ensuring zero performance impact when not used.

## Available Hooks

### Creation Hooks

#### `beforeCreate(array &$data): void`

Called **before** the DTO instance is created. Allows you to modify the input data.

**Use cases:**
- Normalize input data
- Add default values
- Transform data structure

```php
use event4u\DataHelpers\LiteDto\LiteDto;

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
    ) {}

    protected function beforeCreate(array &$data): void
    {
        // Normalize email to lowercase
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        // Add default name if not provided
        if (!isset($data['name'])) {
            $data['name'] = 'Guest';
        }
    }
}

$user = UserDto::from(['email' => 'JOHN@EXAMPLE.COM']);
// email: 'john@example.com', name: 'Guest'
```

#### `afterCreate(): void`

Called **after** the DTO instance is created.

**Use cases:**
- Logging
- Event dispatching
- Post-creation validation

```php
class OrderDto extends LiteDto
{
    public function __construct(
        public readonly int $orderId,
        public readonly float $total,
    ) {}

    protected function afterCreate(): void
    {
        // Log order creation
        error_log("Order {$this->orderId} created with total {$this->total}");
    }
}
```

### Mapping Hooks

#### `beforeMapping(array &$data): void`

Called **before** property mapping begins.

**Use cases:**
- Transform data structure
- Rename keys
- Add computed values

```php
class ProductDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
    ) {}

    protected function beforeMapping(array &$data): void
    {
        // Convert cents to dollars
        if (isset($data['price_cents'])) {
            $data['price'] = $data['price_cents'] / 100;
            unset($data['price_cents']);
        }
    }
}

$product = ProductDto::from(['name' => 'Widget', 'price_cents' => 1999]);
// price: 19.99
```

#### `afterMapping(): void`

Called **after** property mapping is complete.

**Use cases:**
- Post-mapping validation
- Logging
- Trigger side effects

```php
class ConfigDto extends LiteDto
{
    public function __construct(
        public readonly string $environment,
        public readonly bool $debug,
    ) {}

    protected function afterMapping(): void
    {
        // Warn if debug is enabled in production
        if ($this->environment === 'production' && $this->debug) {
            error_log('WARNING: Debug mode enabled in production!');
        }
    }
}
```

### Casting Hooks

#### `beforeCasting(string $property, mixed &$value): void`

Called **before** casting a property value.

**Use cases:**
- Custom pre-cast transformations
- Logging
- Validation

```php
class DateDto extends LiteDto
{
    public function __construct(
        public readonly \DateTime $createdAt,
    ) {}

    protected function beforeCasting(string $property, mixed &$value): void
    {
        if ($property === 'createdAt' && is_string($value)) {
            // Convert 'now' to current timestamp
            if ($value === 'now') {
                $value = time();
            }
        }
    }
}
```

#### `afterCasting(string $property, mixed $value): void`

Called **after** casting a property value.

**Use cases:**
- Logging
- Validation
- Trigger side effects

```php
class MetricsDto extends LiteDto
{
    public function __construct(
        public readonly int $views,
        public readonly float $conversionRate,
    ) {}

    protected function afterCasting(string $property, mixed $value): void
    {
        // Log unusual values
        if ($property === 'conversionRate' && $value > 0.5) {
            error_log("High conversion rate detected: {$value}");
        }
    }
}
```

### Validation Hooks

#### `beforeValidation(array &$data): void`

Called **before** validation begins.

**Use cases:**
- Normalize data before validation
- Add default values
- Transform data structure

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\Validation\Attributes\Email;
use event4u\DataHelpers\Validation\Attributes\Min;

class RegistrationDto extends LiteDto
{
    public function __construct(
        #[Email]
        public readonly string $email,
        #[Min(8)]
        public readonly string $password,
    ) {}

    protected function beforeValidation(array &$data): void
    {
        // Trim whitespace before validation
        if (isset($data['email'])) {
            $data['email'] = trim($data['email']);
        }
    }
}
```

#### `afterValidation(ValidationResult $result): void`

Called **after** validation completes.

**Use cases:**
- Logging validation results
- Custom error handling
- Metrics collection

```php
use event4u\DataHelpers\Validation\ValidationResult;

class FormDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}

    protected function afterValidation(ValidationResult $result): void
    {
        if (!$result->isValid()) {
            error_log('Validation failed: ' . json_encode($result->getErrors()));
        }
    }
}
```

### Serialization Hooks

#### `beforeSerialization(array &$data): void`

Called **before** serialization to array/JSON.

**Use cases:**
- Add computed fields
- Remove sensitive data
- Transform output structure

```php
class ApiResponseDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly \DateTime $createdAt,
    ) {}

    protected function beforeSerialization(array &$data): void
    {
        // Add API version
        $data['api_version'] = '1.0';

        // Format datetime
        if (isset($data['createdAt']) && $data['createdAt'] instanceof \DateTime) {
            $data['createdAt'] = $data['createdAt']->format('Y-m-d H:i:s');
        }
    }
}
```

#### `afterSerialization(array $data): array`

Called **after** serialization to array/JSON. Can modify and return the output.

**Use cases:**
- Final output transformations
- Add metadata
- Wrap response

```php
class PaginatedDto extends LiteDto
{
    public function __construct(
        public readonly array $items,
        public readonly int $total,
    ) {}

    protected function afterSerialization(array $data): array
    {
        // Wrap in metadata
        return [
            'data' => $data,
            'meta' => [
                'timestamp' => time(),
                'count' => count($data['items']),
            ],
        ];
    }
}

$dto = PaginatedDto::from(['items' => [1, 2, 3], 'total' => 3]);
$array = $dto->toArray();
// ['data' => ['items' => [1, 2, 3], 'total' => 3], 'meta' => ['timestamp' => ..., 'count' => 3]]
```

## Hook Execution Order

Hooks are called in the following order during the DTO lifecycle:

### 1. Creation Flow
```
beforeCreate → beforeMapping → property mapping → 
beforeCasting (per property) → afterCasting (per property) → 
afterMapping → afterCreate
```

### 2. Validation Flow
```
beforeValidation → validation rules → afterValidation
```

### 3. Serialization Flow
```
beforeSerialization → toArray/toJson → afterSerialization
```

## Performance Notes

- **Zero Overhead**: When hooks are not overridden, they have minimal performance impact (just an empty method call)
- **Reflection**: Hooks use PHP Reflection API to call protected methods, which has negligible overhead
- **Caching**: Hook existence is checked once per DTO class and cached
- **UltraFast Mode**: Hooks work seamlessly with `#[UltraFast]` mode without performance degradation

## Best Practices

1. **Keep hooks lightweight**: Avoid heavy computations in hooks
2. **Use appropriate hooks**: Choose the right hook for your use case
3. **Document custom hooks**: Add comments explaining why you're using a hook
4. **Avoid side effects**: Be cautious with side effects in hooks (logging, events, etc.)
5. **Test hooks**: Write tests for custom hook behavior

## Example: Complete Lifecycle

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\Validation\ValidationResult;

class CompleteDto extends LiteDto
{
    public function __construct(
        public readonly string $email,
        public readonly int $age,
    ) {}

    protected function beforeCreate(array &$data): void
    {
        error_log('1. beforeCreate');
        $data['email'] = strtolower($data['email'] ?? '');
    }

    protected function beforeMapping(array &$data): void
    {
        error_log('2. beforeMapping');
    }

    protected function beforeCasting(string $property, mixed &$value): void
    {
        error_log("3. beforeCasting: {$property}");
    }

    protected function afterCasting(string $property, mixed $value): void
    {
        error_log("4. afterCasting: {$property}");
    }

    protected function afterMapping(): void
    {
        error_log('5. afterMapping');
    }

    protected function afterCreate(): void
    {
        error_log('6. afterCreate');
    }

    protected function beforeSerialization(array &$data): void
    {
        error_log('7. beforeSerialization');
        $data['_timestamp'] = time();
    }

    protected function afterSerialization(array $data): array
    {
        error_log('8. afterSerialization');
        return $data;
    }
}

$dto = CompleteDto::from(['email' => 'JOHN@EXAMPLE.COM', 'age' => 30]);
$array = $dto->toArray();
```


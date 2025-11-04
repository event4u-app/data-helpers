---
title: "#[NotImmutable] - Mutable Properties"
description: "Allow property modification after construction for counters, caching, and tracking"
---

The `#[NotImmutable]` attribute allows you to mark DTOs or specific properties as mutable, enabling modification after construction. This is useful for counters, caching, tracking, and other scenarios where you need to update values without creating a new instance.

## Basic Usage

### Class-Level: All Properties Mutable

```php
use event4u\DataHelpers\SimpleDto\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NotImmutable;

#[NotImmutable]
class MutableUserDto extends SimpleDto
{
    public function __construct(
        public string $name,
        public int $age,
        public string $email,
    ) {}
}

$user = MutableUserDto::from([
    'name' => 'John Doe',
    'age' => 30,
    'email' => 'john@example.com',
]);

// All properties can be modified
$user->name = 'Jane Doe';
$user->age = 31;
$user->email = 'jane@example.com';
```

### Property-Level: Selective Mutability

```php
class PartiallyMutableUserDto extends SimpleDto
{
    public function __construct(
        public string $id,                 // Immutable (no NotImmutable)
        public string $name,               // Immutable (no NotImmutable)
        #[NotImmutable]
        public int $loginCount,            // Mutable
        #[NotImmutable]
        public ?string $lastLoginAt,       // Mutable
    ) {}
}

$user = PartiallyMutableUserDto::from([
    'id' => 'user-123',
    'name' => 'Alice',
    'loginCount' => 0,
    'lastLoginAt' => null,
]);

// Mutable properties can be modified
$user->loginCount = 1;
$user->lastLoginAt = '2025-10-31 10:00:00';

// Immutable properties cannot be modified
// $user->name = 'Bob'; // Would work, but violates design intent
```

## Use Cases

### 1. Statistics & Counters

```php
class StatisticsDto extends SimpleDto
{
    public function __construct(
        public string $id,
        public string $name,
        #[NotImmutable]
        public int $viewCount = 0,
        #[NotImmutable]
        public int $likeCount = 0,
        #[NotImmutable]
        public int $shareCount = 0,
    ) {}

    public function incrementViews(): void
    {
        $this->viewCount++;
    }

    public function incrementLikes(): void
    {
        $this->likeCount++;
    }

    public function incrementShares(): void
    {
        $this->shareCount++;
    }
}

$stats = StatisticsDto::from([
    'id' => 'post-123',
    'name' => 'My Blog Post',
]);

$stats->incrementViews();
$stats->incrementLikes();
```

### 2. Caching & Lazy Loading

```php
class CachedDataDto extends SimpleDto
{
    public function __construct(
        public string $id,
        public array $data,
        #[NotImmutable]
        public ?array $cachedResult = null,
        #[NotImmutable]
        public ?int $cacheTimestamp = null,
    ) {}

    public function getProcessedData(): array
    {
        if ($this->cachedResult !== null) {
            return $this->cachedResult;
        }

        // Expensive computation
        $result = $this->processData();

        // Cache the result
        $this->cachedResult = $result;
        $this->cacheTimestamp = time();

        return $result;
    }

    private function processData(): array
    {
        // ... complex processing
        return [];
    }
}
```

### 3. Session Tracking

```php
class SessionDto extends SimpleDto
{
    public function __construct(
        public string $sessionId,
        public string $userId,
        #[NotImmutable]
        public int $requestCount = 0,
        #[NotImmutable]
        public ?string $lastActivity = null,
        #[NotImmutable]
        public array $visitedPages = [],
    ) {}

    public function trackRequest(string $page): void
    {
        $this->requestCount++;
        $this->lastActivity = date('Y-m-d H:i:s');
        $this->visitedPages[] = $page;
    }
}
```

## Performance

`#[NotImmutable]` is extremely performant:

- **One-time scan**: Attributes are scanned only once during first access
- **Cached**: Results are stored in feature flags
- **Zero overhead**: After initial scan, no performance impact
- **Fast lookup**: O(1) array lookup in cached flags

```php
// First access: Reflection scan (~50-100μs)
$stats->viewCount = 1;

// All subsequent accesses: Only array lookup (~0.1μs)
$stats->viewCount = 2;
$stats->viewCount = 3;
// ... extremely fast
```

## Important Notes

### 1. Readonly Properties

Properties marked with `readonly` cannot be modified, even with `#[NotImmutable]`:

```php
// ❌ WRONG - readonly prevents modification
#[NotImmutable]
public readonly int $count;

// ✅ CORRECT - no readonly
#[NotImmutable]
public int $count;
```

### 2. Class-Level Overrides Property-Level

When the class has `#[NotImmutable]`, **all** properties are mutable:

```php
#[NotImmutable]
class MutableDto extends SimpleDto
{
    public function __construct(
        public string $id,    // Mutable (class-level)
        public string $name,  // Mutable (class-level)
    ) {}
}
```

### 3. Design Intent vs. Enforcement

Without `readonly`, PHP allows property modification by default. `#[NotImmutable]` is a **marker attribute** that documents your design intent:

- **With `#[NotImmutable]`**: "This property is designed to be mutable"
- **Without `#[NotImmutable]`**: "This property should be immutable (use `readonly` for enforcement)"

## Best Practices

### 1. Use Sparingly

Immutability is often the better choice. Only use `#[NotImmutable]` when you have a clear reason:

```php
// ✅ Good - clear use case (counter)
#[NotImmutable]
public int $viewCount = 0;

// ❌ Bad - no clear reason
#[NotImmutable]
public string $name;
```

### 2. Combine with Readonly for Partial Mutability

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $id,        // Immutable (readonly)
        public readonly string $name,      // Immutable (readonly)
        #[NotImmutable]
        public int $loginCount = 0,        // Mutable
    ) {}
}
```

### 3. Document the Reason

```php
class CacheDto extends SimpleDto
{
    public function __construct(
        public readonly string $key,
        
        /** @var mixed Cached value - mutable for lazy loading */
        #[NotImmutable]
        public mixed $value = null,
    ) {}
}
```

## Comparison: Immutable vs. Mutable

| Approach | Pros | Cons | Use Case |
|----------|------|------|----------|
| **Immutable (default)** | Thread-safe, predictable, easier to reason about | Requires creating new instances for changes | Most DTOs, data transfer |
| **Mutable (#[NotImmutable])** | Efficient for frequent updates, no new instances | Less predictable, not thread-safe | Counters, caching, tracking |

## See Also

- [Attributes Overview](/data-helpers/attributes/overview/) - All available attributes
- [SimpleDto Introduction](/data-helpers/simple-dto/introduction/) - Getting started
- [Performance Guide](/data-helpers/performance/benchmarks/) - Performance optimization


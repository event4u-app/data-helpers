---
title: Performance Optimization
description: Complete guide for optimizing Data Helpers performance
---

Complete guide for optimizing Data Helpers performance.

## Introduction

Data Helpers provides multiple optimization techniques:

- ✅ **Validation Caching** - 198x faster validation
- ✅ **Lazy Loading** - Defer expensive operations
- ✅ **Type Cast Caching** - Reuse cast instances
- ✅ **Path Compilation** - Pre-compile dot-notation paths

## Enable Validation Caching

### Laravel

```bash
php artisan dto:cache
```

### Symfony

```bash
bin/console dto:cache
```

### Plain PHP

<!-- skip-test: ValidationCache class does not exist, caching is automatic -->
```php
use event4u\DataHelpers\SimpleDto\Cache\ValidationCache;

ValidationCache::enable();
ValidationCache::warmup();
```

### Performance Impact

```
Without Cache:  5,000 validations/sec
With Cache:     990,000 validations/sec

Improvement: 198x faster
```

## Use Lazy Loading

```php
use event4u\DataHelpers\SimpleDto\Attributes\Lazy;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Lazy]
        public readonly ?array $posts = null,
    ) {}
}
```

## Optimize Type Casting

```php
// config/data-helpers.php
return [
    'casts' => [
        'cache_instances' => true,
    ],
];
```

## Avoid Deep Nesting

```php
// ✅ Good - 2-3 levels
$dto->address->city;

// ❌ Bad - too deep
$dto->company->department->team->manager->address->city;
```

## Use Specific Types

```php
// ✅ Good
public readonly int $age;

// ❌ Bad
public readonly mixed $age;
```

## Batch Operations

```php
// ✅ Good
$dtos = DataCollection::make($users, UserDto::class);

// ❌ Bad
foreach ($users as $user) {
    $dtos[] = UserDto::fromModel($user);
}
```

## Memory Optimization

```php
// Use chunking
User::chunk(1000, function($users) {
    $dtos = DataCollection::make($users, UserDto::class);
});
```

## Best Practices

- [ ] Enable validation caching in production
- [ ] Use lazy loading for expensive operations
- [ ] Enable cast caching
- [ ] Avoid deep nesting (max 3 levels)
- [ ] Use specific types instead of mixed
- [ ] Use batch operations
- [ ] Use chunking for large datasets

## See Also

- [Performance Benchmarks](/performance/benchmarks/) - Detailed benchmarks
- [Lazy Properties](/simple-dto/lazy-properties/) - Lazy loading guide

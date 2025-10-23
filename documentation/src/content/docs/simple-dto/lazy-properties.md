---
title: Lazy Properties
description: Learn how to defer expensive operations until they're actually needed using lazy properties
---

Learn how to defer expensive operations until they're actually needed using lazy properties.

## What are Lazy Properties?

Lazy properties are properties that are only evaluated when accessed, not when the DTO is created:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[Lazy]
        public readonly ?array $posts = null,  // Only loaded when accessed
    ) {}
}

$dto = UserDTO::fromModel($user);
// Posts are NOT loaded yet

$posts = $dto->posts;
// Posts are loaded NOW
```

## Basic Usage

### Using #[Lazy] Attribute

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\Lazy;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[Lazy]
        public readonly ?array $posts = null,

        #[Lazy]
        public readonly ?array $comments = null,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'posts' => fn() => Post::where('user_id', 1)->get(),
    'comments' => fn() => Comment::where('user_id', 1)->get(),
]);
```

## With Closures

### Lazy Loading from Database

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,

        #[Lazy]
        public readonly ?array $posts = null,
    ) {}
}

$dto = UserDTO::fromArray([
    'userId' => 1,
    'name' => 'John Doe',
    'posts' => fn() => Post::where('user_id', 1)->get()->toArray(),
]);

// Posts are NOT loaded yet
echo $dto->name; // No database query

// Posts are loaded NOW
$posts = $dto->posts; // Database query executed
```

### Lazy Expensive Calculations

```php
class ReportDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        #[Lazy]
        public readonly ?array $statistics = null,
    ) {}
}

$dto = ReportDTO::fromArray([
    'title' => 'Monthly Report',
    'statistics' => fn() => [
        'total' => Order::sum('total'),
        'count' => Order::count(),
        'average' => Order::avg('total'),
    ],
]);
```

## Combining with Other Features

### Lazy + Conditional

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[Lazy, WhenAuth]
        public readonly ?array $privateData = null,
    ) {}
}
```

### Lazy + Computed

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $userId,

        #[Lazy]
        public readonly ?array $posts = null,
    ) {}

    #[Computed, Lazy]
    public function postCount(): int
    {
        return count($this->posts ?? []);
    }
}
```

## Best Practices

### Use Lazy for Expensive Operations

```php
// ✅ Good - lazy for expensive operations
#[Lazy]
public readonly ?array $statistics = null;

// ❌ Bad - eager loading expensive data
public readonly array $statistics;
```

### Use Closures for Lazy Values

```php
// ✅ Good - closure for lazy evaluation
posts: fn() => $user->posts()->get()

// ❌ Bad - eager evaluation
posts: $user->posts()->get()
```

### Document Lazy Properties

```php
/**
 * @property-read array|null $posts Lazy-loaded user posts
 * @property-read array|null $followers Lazy-loaded followers
 */
class UserDTO extends SimpleDTO
{
    // ...
}
```

## See Also

- [Computed Properties](/simple-dto/computed-properties/) - Calculate values on-the-fly
- [Conditional Properties](/simple-dto/conditional-properties/) - Dynamic visibility
- [Collections](/simple-dto/collections/) - Work with collections

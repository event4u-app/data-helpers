---
title: DataFilter API
description: Complete API reference for DataFilter
---

Complete API reference for DataFilter.

## Static Methods

### `make(array $data): self`

Create a new instance.

```php
$filter = DataFilter::make($data);
```

## Filter Methods

### `only(array $keys): self`

Keep only specified keys.

```php
$filter->only(['name', 'email']);
```

### `except(array $keys): self`

Remove specified keys.

```php
$filter->except(['password', 'token']);
```

### `where(string $key, mixed $value): self`

Filter by key-value.

```php
$filter->where('status', 'active');
```

### `whereIn(string $key, array $values): self`

Filter by key in values.

```php
$filter->whereIn('role', ['admin', 'moderator']);
```

### `whereNotNull(string $key): self`

Filter where key is not null.

```php
$filter->whereNotNull('email');
```

## Transform Methods

### `map(callable $callback): self`

Transform each item.

```php
$filter->map(fn($item) => strtoupper($item));
```

### `filter(callable $callback): self`

Filter items by callback.

```php
$filter->filter(fn($item) => $item['active']);
```

## Result Methods

### `toArray(): array`

Get filtered array.

```php
$result = $filter->toArray();
```

### `first(): mixed`

Get first item.

```php
$first = $filter->first();
```

### `count(): int`

Count items.

```php
$count = $filter->count();
```

## See Also

- [DataFilter Guide](/main-classes/data-filter/) - Complete guide


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
use event4u\DataHelpers\DataFilter;

$data = [['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]];
$filter = DataFilter::query($data);
$filter->only(['name', 'email']);
```

### `except(array $keys): self`

Remove specified keys.

```php
use event4u\DataHelpers\DataFilter;

$data = [['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]];
$filter = DataFilter::query($data);
$filter->except(['password', 'token']);
```

### `where(string $key, mixed $value): self`

Filter by key-value.

```php
use event4u\DataHelpers\DataFilter;

$data = [['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]];
$filter = DataFilter::query($data);
$filter->where('status', 'active');
```

### `whereIn(string $key, array $values): self`

Filter by key in values.

```php
use event4u\DataHelpers\DataFilter;

$data = [['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]];
$filter = DataFilter::query($data);
$filter->whereIn('role', ['admin', 'moderator']);
```

### `whereNotNull(string $key): self`

Filter where key is not null.

```php
use event4u\DataHelpers\DataFilter;

$data = [['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]];
$filter = DataFilter::query($data);
$filter->whereNotNull('email');
```

## Transform Methods

### `map(callable $callback): self`

Transform each item.

```php
use event4u\DataHelpers\DataFilter;

$data = [['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]];
$filter = DataFilter::query($data);
$filter->map(fn($item) => strtoupper($item));
```

### `filter(callable $callback): self`

Filter items by callback.

```php
use event4u\DataHelpers\DataFilter;

$data = [['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]];
$filter = DataFilter::query($data);
$filter->filter(fn($item) => $item['active']);
```

## Result Methods

### `toArray(): array`

Get filtered array.

```php
use event4u\DataHelpers\DataFilter;

$data = [['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]];
$filter = DataFilter::query($data);
$result = $filter->toArray();
```

### `first(): mixed`

Get first item.

```php
use event4u\DataHelpers\DataFilter;

$data = [['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]];
$filter = DataFilter::query($data);
$first = $filter->first();
```

### `count(): int`

Count items.

```php
use event4u\DataHelpers\DataFilter;

$data = [['id' => 1, 'name' => 'John', 'age' => 25], ['id' => 2, 'name' => 'Jane', 'age' => 30]];
$filter = DataFilter::query($data);
$count = $filter->count();
```

## See Also

- [DataFilter Guide](/main-classes/data-filter/) - Complete guide


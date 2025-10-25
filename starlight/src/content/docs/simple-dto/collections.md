---
title: Collections
description: Learn how to work with collections of DTOs using DataCollection
---

Learn how to work with collections of DTOs using DataCollection.

## What are Collections?

Collections allow you to work with multiple DTO instances as a group:

```php
$users = UserDTO::collection($userArray);
// DataCollection of UserDTO instances

$users->filter(fn($user) => $user->age > 18);
$users->map(fn($user) => $user->name);
$users->first();
$users->count();
```

## Creating Collections

### From Array

```php
$data = [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
];

$users = UserDTO::collection($data);
```

### From Eloquent Collection

```php
$users = User::all();
$dtos = UserDTO::collection($users);
```

### Using DataCollection::make()

```php
use event4u\DataHelpers\SimpleDTO\DataCollection;

$collection = DataCollection::make($data, UserDTO::class);
```

## Collection Methods

### Filter

```php
$adults = $users->filter(fn($user) => $user->age >= 18);
```

### Map

```php
$names = $users->map(fn($user) => $user->name);
```

### First / Last

```php
$first = $users->first();
$last = $users->last();
```

### Count

```php
$count = $users->count();
```

### ToArray

```php
$array = $users->toArray();
```

## Pagination

### Basic Pagination

```php
$paginated = UserDTO::paginatedCollection($users, page: 1, perPage: 10);
// [
//     'data' => [...],
//     'meta' => [
//         'current_page' => 1,
//         'per_page' => 10,
//         'total' => 100,
//         'last_page' => 10,
//     ],
// ]
```

### Laravel Pagination

```php
$users = User::paginate(10);
$dtos = UserDTO::collection($users);
```

## Nested Collections

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly DataCollection $items,  // Collection of OrderItemDTO
    ) {}
}

$order = OrderDTO::fromArray([
    'orderId' => 123,
    'items' => [
        ['product' => 'Widget', 'quantity' => 2],
        ['product' => 'Gadget', 'quantity' => 1],
    ],
]);
```

## Best Practices

### Use Type Hints

```php
// ✅ Good - with type hint
public readonly DataCollection $items;

// ❌ Bad - no type hint
public readonly $items;
```

### Use Collection Methods

```php
// ✅ Good - use collection methods
$adults = $users->filter(fn($user) => $user->age >= 18);

// ❌ Bad - manual loop
$adults = [];
foreach ($users as $user) {
    if ($user->age >= 18) {
        $adults[] = $user;
    }
}
```


## Code Examples

The following working examples demonstrate this feature:

- [**Data Collection**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/collections/data-collection.php) - Working with collections
- [**DTO Sorting**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/collections/dto-sorting.php) - Sorting DTOs in collections

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [CollectionTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDTO/CollectionTest.php) - Collection tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=Collection
```

## See Also

- [Nested DTOs](/simple-dto/nested-dtos/) - Complex nested structures
- [Creating DTOs](/simple-dto/creating-dtos/) - Creation methods
- [Type Casting](/simple-dto/type-casting/) - Automatic type conversion

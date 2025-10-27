---
title: Collections
description: Learn how to work with collections of Dtos using DataCollection
---

Learn how to work with collections of Dtos using DataCollection.

## What are Collections?

Collections allow you to work with multiple Dto instances as a group:

<!-- skip-test: Code snippet example -->
```php
$users = UserDto::collection($userArray);
// DataCollection of UserDto instances

$users->filter(fn($user) => $user->age > 18);
$users->map(fn($user) => $user->name);
$users->first();
$users->count();
```

## Creating Collections

### From Array

```php
use Tests\Utils\Docu\Dtos\UserDto;

$data = [
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 30],
];

$users = UserDto::collection($data);
// Result: DataCollection of UserDto instances
```

### From Eloquent Collection

<!-- skip-test: Requires Laravel -->
```php
$users = User::all();
$dtos = UserDto::collection($users);
```

### Using DataCollection::make()

```php
use event4u\DataHelpers\SimpleDto\DataCollection;
use Tests\Utils\Docu\Dtos\UserDto;

$data = [
    ['name' => 'John', 'email' => 'john@example.com', 'age' => 30],
    ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25],
];
$collection = DataCollection::make($data, UserDto::class);
```

## Collection Methods

### Filter

```php
use Tests\Utils\Docu\Dtos\UserDto;

$data = [
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 17],
    ['name' => 'Bob', 'age' => 30],
];

$users = UserDto::collection($data);
$adults = $users->filter(fn($user) => $user->age >= 18);
// Result: DataCollection with 2 items (John and Bob)
```

### Map

```php
use Tests\Utils\Docu\Dtos\UserDto;

$data = [
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 17],
];

$users = UserDto::collection($data);
$names = $users->map(fn($user) => $user->name);
// Result: ['John', 'Jane']
```

### First / Last

```php
use Tests\Utils\Docu\Dtos\UserDto;

$data = [
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 30],
];

$users = UserDto::collection($data);
$first = $users->first();
$last = $users->last();
// Result: $first->name = 'John', $last->name = 'Jane'
```

### Count

```php
use Tests\Utils\Docu\Dtos\UserDto;

$data = [
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 30],
];

$users = UserDto::collection($data);
$count = $users->count();
// Result: 2
```

### ToArray

```php
use Tests\Utils\Docu\Dtos\UserDto;

$data = [
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 30],
];

$users = UserDto::collection($data);
$array = $users->toArray();
// Result: [['name' => 'John', 'age' => 25], ['name' => 'Jane', 'age' => 30]]
```

## Pagination

### Basic Pagination

<!-- skip-test: Requires external data -->
```php
$paginated = UserDto::paginatedCollection($users, page: 1, perPage: 10);
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

<!-- skip-test: Requires Laravel -->
```php
$users = User::paginate(10);
$dtos = UserDto::collection($users);
```

## Nested Collections

<!-- skip-test: Class definition example -->
```php
class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $orderId,
        public readonly DataCollection $items,  // Collection of OrderItemDto
    ) {}
}

$order = OrderDto::fromArray([
    'orderId' => 123,
    'items' => [
        ['product' => 'Widget', 'quantity' => 2],
        ['product' => 'Gadget', 'quantity' => 1],
    ],
]);
```

## Best Practices

### Use Type Hints

<!-- skip-test: Code snippet example -->
```php
// ✅ Good - with type hint
public readonly DataCollection $items;

// ❌ Bad - no type hint
public readonly $items;
```

### Use Collection Methods

<!-- skip-test: Code snippet example -->
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
- [**Dto Sorting**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/collections/dto-sorting.php) - Sorting Dtos in collections

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [CollectionTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/CollectionTest.php) - Collection tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=Collection
```

## See Also

- [Nested Dtos](/simple-dto/nested-dtos/) - Complex nested structures
- [Creating Dtos](/simple-dto/creating-dtos/) - Creation methods
- [Type Casting](/simple-dto/type-casting/) - Automatic type conversion

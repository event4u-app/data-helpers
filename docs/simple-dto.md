# SimpleDTO

A lightweight, immutable Data Transfer Object (DTO) implementation with JSON serialization support.

## Overview

The `SimpleDTO` class provides a simple base class for creating immutable DTOs with automatic array conversion and JSON serialization. It uses PHP 8.2+ readonly properties and named arguments for clean, type-safe data transfer.

## Features

- ✅ **Immutable by design** - Use readonly properties
- ✅ **Type-safe** - Full PHP type hinting support
- ✅ **JSON serializable** - Implements `JsonSerializable`
- ✅ **Array conversion** - Easy conversion to/from arrays
- ✅ **Named arguments** - Clean instantiation with `fromArray()`
- ✅ **Nested DTOs** - Support for complex object structures
- ✅ **Laravel-compatible casts** - Automatic type casting with built-in and custom casts
- ✅ **PHPStan Level 9** - Fully type-safe

## Basic Usage

### Creating a Simple DTO

```php
use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

// Create from array
$user = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Access properties
echo $user->name;  // 'John Doe'
echo $user->email; // 'john@example.com'
echo $user->age;   // 30

// Convert to array
$array = $user->toArray();
// ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30]

// Convert to JSON
$json = json_encode($user);
// {"name":"John Doe","email":"john@example.com","age":30}
```

### Optional Properties

```php
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly ?string $description = null,
        public readonly ?string $category = null,
    ) {}
}

// Create with optional properties
$product = ProductDTO::fromArray([
    'name' => 'Laptop',
    'price' => 999.99,
    'description' => 'High-performance laptop',
    // category is omitted, will be null
]);
```

### Nested DTOs

```php
class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $zipCode,
        public readonly string $country,
    ) {}
}

class CustomerDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly AddressDTO $address,
    ) {}
}

// Create nested structure
$address = AddressDTO::fromArray([
    'street' => '123 Main St',
    'city' => 'New York',
    'zipCode' => '10001',
    'country' => 'USA',
]);

$customer = new CustomerDTO(
    name: 'Jane Smith',
    email: 'jane@example.com',
    address: $address,
);

// Access nested properties
echo $customer->address->city; // 'New York'

// JSON serialization includes nested objects
$json = json_encode($customer);
```

## Integration with DataMapper

DTOs work seamlessly with the DataMapper for transforming API responses or database results:

```php
use event4u\DataHelpers\DataMapper;

$apiResponse = [
    'user' => [
        'full_name' => 'Alice Johnson',
        'email_address' => 'alice@example.com',
        'years_old' => 28,
    ],
];

// Map API response to DTO-compatible array
$mappedData = DataMapper::source($apiResponse)
    ->template([
        'name' => '{{ user.full_name }}',
        'email' => '{{ user.email_address }}',
        'age' => '{{ user.years_old }}',
    ])
    ->map()
    ->toArray();

// Create DTO from mapped data
$user = UserDTO::fromArray($mappedData);
```

## Working with Collections

### Array of DTOs

```php
$productsData = [
    ['name' => 'Keyboard', 'price' => 79.99],
    ['name' => 'Monitor', 'price' => 299.99],
    ['name' => 'Desk', 'price' => 199.99],
];

// Create array of DTOs
$products = array_map(
    fn(array $data) => ProductDTO::fromArray($data),
    $productsData
);

// Serialize to JSON
$json = json_encode($products);
```

### With Laravel Collections

```php
use Illuminate\Support\Collection;

$products = collect($productsData)
    ->map(fn(array $data) => ProductDTO::fromArray($data));

// Convert back to array
$array = $products->map(fn(ProductDTO $dto) => $dto->toArray())->all();
```

## Attribute Casting

SimpleDTOs support Laravel-compatible attribute casting, allowing you to automatically transform data when creating DTOs from arrays.

### Built-in Casts

```php
use event4u\DataHelpers\SimpleDTO;
use DateTimeImmutable;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly bool $is_active,
        public readonly array $roles,
        public readonly DateTimeImmutable $created_at,
    ) {}

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',  // Cast to boolean
            'roles' => 'array',        // Cast JSON string to array
            'created_at' => 'datetime', // Cast to DateTimeImmutable
        ];
    }
}

// Create DTO with automatic casting
$user = UserDTO::fromArray([
    'name' => 'John Doe',
    'is_active' => '1',  // String '1' → boolean true
    'roles' => '["admin","editor"]',  // JSON string → array
    'created_at' => '2024-01-15 10:30:00',  // String → DateTimeImmutable
]);
```

**Available built-in casts:**
- `boolean` / `bool` - Casts to boolean (supports: `'1'`, `'0'`, `'true'`, `'false'`, `'yes'`, `'no'`, `'on'`, `'off'`)
- `integer` / `int` - Casts to integer (supports: strings, floats)
- `float` / `double` - Casts to float (supports: strings, integers)
- `string` - Casts to string (supports: integers, floats, booleans, objects with `__toString()`)
- `array` - Casts JSON strings or objects to arrays
- `datetime` - Casts strings, timestamps, or DateTime objects to DateTimeImmutable
- `decimal:precision` - Casts to decimal string with fixed precision (e.g., `decimal:2` for monetary values)
- `json` - Casts JSON strings to arrays/objects and vice versa

### Custom Cast Classes

You can use custom cast classes for more control:

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Casts\BooleanCast;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly bool $in_stock,
        public readonly ?DateTimeImmutable $available_from = null,
    ) {}

    protected function casts(): array
    {
        return [
            'in_stock' => BooleanCast::class,
            'available_from' => DateTimeCast::class,
        ];
    }
}
```

### Cast with Parameters

Some casts support parameters for customization:

```php
class EventDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly DateTimeImmutable $event_date,
    ) {}

    protected function casts(): array
    {
        return [
            // Custom date format
            'event_date' => DateTimeCast::class . ':Y-m-d',
            // Alternative syntax
            // 'event_date' => 'datetime:Y-m-d',
        ];
    }
}

$event = EventDTO::fromArray([
    'title' => 'Conference',
    'event_date' => '2024-06-15',  // Will be parsed with Y-m-d format
]);
```

### Creating Custom Casts

You can create your own cast classes by implementing the `CastsAttributes` interface:

```php
use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;

class UpperCaseCast implements CastsAttributes
{
    public function get(mixed $value, array $attributes): ?string
    {
        return $value !== null ? strtoupper($value) : null;
    }

    public function set(mixed $value, array $attributes): ?string
    {
        return $value !== null ? strtoupper($value) : null;
    }
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
    ) {}

    protected function casts(): array
    {
        return [
            'name' => UpperCaseCast::class,
        ];
    }
}

$user = UserDTO::fromArray(['name' => 'john doe']);
echo $user->name; // 'JOHN DOE'
```

**Note:** Custom casts are compatible with Laravel's `CastsAttributes` interface, so you can reuse Laravel casts in SimpleDTOs!

## Advanced Usage

### Custom Methods

You can add custom methods to your DTOs:

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $subtotal,
        public readonly float $tax,
        public readonly float $shipping,
    ) {}

    public function getTotal(): float
    {
        return $this->subtotal + $this->tax + $this->shipping;
    }

    public function isExpensive(): bool
    {
        return $this->getTotal() > 1000;
    }
}
```

### Validation

While DTOs don't include built-in validation, you can add it in the constructor:

```php
class EmailDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $email,
    ) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$email}");
        }
    }
}
```

## Architecture

The SimpleDTO implementation consists of three components:

1. **DTOInterface** (`src/SimpleDTO/DTOInterface.php`)
   - Defines the contract for DTOs
   - Methods: `toArray()`, `fromArray()`

2. **SimpleDTOTrait** (`src/SimpleDTO/SimpleDTOTrait.php`)
   - Provides default implementations
   - Uses `get_object_vars()` for array conversion
   - Uses named arguments for `fromArray()`

3. **SimpleDTO** (`src/SimpleDTO.php`)
   - Abstract base class
   - Implements `DTOInterface` and `JsonSerializable`
   - Uses `SimpleDTOTrait`

## Best Practices

1. **Use readonly properties** - Ensures immutability
2. **Type everything** - Use strict types for all properties
3. **Keep DTOs simple** - Avoid complex logic in DTOs
4. **Use named arguments** - Makes instantiation clear and maintainable
5. **Document properties** - Add PHPDoc comments for complex types
6. **Validate in constructor** - Add validation logic if needed

## Performance

SimpleDTO provides excellent performance, especially when used with DataMapper.

<!-- DTO_BENCHMARK_RESULTS_START -->

### Benchmark 1: DTO Creation with DataMapper

| Approach | Avg Time | Ops/sec | Performance |
|----------|----------|---------|-------------|
| Traditional Mutable DTO | 245.98 μs | 4,065 | Baseline |
| SimpleDTO Immutable | 177.95 μs | 5,619 | **27.7% faster** ✅ |

### Benchmark 2: Simple DTO Creation (no DataMapper)

| Approach | Avg Time | Ops/sec | Performance |
|----------|----------|---------|-------------|
| Traditional: new + assign | 0.14 μs | 6,962,605 | Baseline |
| SimpleDTO: fromArray() | 0.28 μs | 3,518,689 | 97.9% slower ⚠️ |

### Benchmark 3: toArray() Conversion

| Approach | Avg Time | Ops/sec | Performance |
|----------|----------|---------|-------------|
| Traditional: toArray() | 0.11 μs | 8,697,238 | Baseline |
| SimpleDTO: toArray() | 0.12 μs | 8,434,114 | 3.1% slower ⚠️ |

### Summary

**Real-World Performance (what matters):**

- ✅ **SimpleDTO is 27.7% faster** for DataMapper integration (most common use case)
- ✅ **SimpleDTO is practically equal** for toArray() (3.1% difference is negligible)

**Synthetic Benchmark (unrealistic scenario):**

- ⚠️  Traditional DTO is 97.9% faster for manual property assignment (but nobody does this in real code)

**🏆 Winner: SimpleDTO** - Faster where it matters, with immutability and type safety as bonus!

<!-- DTO_BENCHMARK_RESULTS_END -->

## Examples

See `examples/23-simple-dto.php` for complete working examples including:
- Basic DTOs
- Optional properties
- Nested DTOs
- DataMapper integration
- Collections of DTOs

## Related Documentation

- [DataMapper](data-mapper.md) - For transforming data before creating DTOs
- [MappedDataModel](mapped-data-model.md) - Alternative approach with request binding
- [Types](types.md) - Type system and casting behavior


---
title: SimpleDto API
description: Complete API reference for SimpleDto
---

Complete API reference for SimpleDto.

## Creation Methods

### `from(mixed $data, ?array $template = null, ?array $filters = null, ?array $pipeline = null): static`

Create DTO from data with optional template, filters and pipeline.

**Parameters:**
- `$data` - Array, JSON string, XML string or object
- `$template` - Optional template for mapping (overrides DTO template)
- `$filters` - Optional property-specific filters (overrides DTO filters)
- `$pipeline` - Optional global filters (merged with DTO pipeline)

**Basic Usage:**

```php
$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = UserDto::from($data);
```

**With Template:**

```php
$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$dto = UserDto::from($data, [
    'name' => '{{ user.name }}',
    'email' => '{{ user.email }}',
]);
```

**With Filters:**

```php
use event4u\DataHelpers\Filters\TrimStrings;
use event4u\DataHelpers\Filters\LowercaseStrings;

$data = ['name' => '  JOHN  ', 'email' => 'JOHN@EXAMPLE.COM'];
$dto = UserDto::from($data, null, [
    'name' => new TrimStrings(),
    'email' => [new TrimStrings(), new LowercaseStrings()],
]);
```

**With Pipeline:**

```php
use event4u\DataHelpers\Filters\TrimStrings;

$data = ['name' => '  John  ', 'email' => '  john@example.com  '];
$dto = UserDto::from($data, null, null, [new TrimStrings()]);
```

### `fromArray(array $data, ?array $template = null, ?array $filters = null, ?array $pipeline = null): static`

Create from array. Alias for `from()` that only accepts arrays.

**Parameters:**
- `$data` - Array data
- `$template` - Optional template for mapping
- `$filters` - Optional property-specific filters
- `$pipeline` - Optional global filters

```php
$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = UserDto::fromArray($data);
```

### `fromJson(string $json, ?array $template = null, ?array $filters = null, ?array $pipeline = null): static`

Create from JSON.

**Parameters:**
- `$json` - JSON string
- `$template` - Optional template for mapping
- `$filters` - Optional property-specific filters
- `$pipeline` - Optional global filters

```php
$json = '{"name":"John","email":"john@example.com"}';
$dto = UserDto::fromJson($json);
```

### `fromXml(string $xml, ?array $template = null, ?array $filters = null, ?array $pipeline = null): static`

Create from XML.

**Parameters:**
- `$xml` - XML string
- `$template` - Optional template for mapping
- `$filters` - Optional property-specific filters
- `$pipeline` - Optional global filters

```php
$xml = '<user><name>John</name><email>john@example.com</email></user>';
$dto = UserDto::fromXml($xml);
```

### `fromYaml(string $yaml, ?array $template = null, ?array $filters = null, ?array $pipeline = null): static`

Create from YAML.

**Parameters:**
- `$yaml` - YAML string
- `$template` - Optional template for mapping
- `$filters` - Optional property-specific filters
- `$pipeline` - Optional global filters

```php
$yaml = "name: John\nemail: john@example.com";
$dto = UserDto::fromYaml($yaml);
```

### `fromCsv(string $csv, ?array $template = null, ?array $filters = null, ?array $pipeline = null): static`

Create from CSV.

**Parameters:**
- `$csv` - CSV string
- `$template` - Optional template for mapping
- `$filters` - Optional property-specific filters
- `$pipeline` - Optional global filters

```php
$csv = "name,email\nJohn,john@example.com";
$dto = UserDto::fromCsv($csv);
```

### `fromModel(Model $model): static`

Create from Eloquent model.

<!-- skip-test: requires Eloquent model -->
```php
use event4u\DataHelpers\UserDto;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDto($data);
$dto = UserDto::fromModel($user);
```

### `fromRequest(Request $request): static`

Create from HTTP request.

<!-- skip-test: requires HTTP request -->
```php
use event4u\DataHelpers\UserDto;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDto($data);
$dto = UserDto::fromRequest($request);
```

### `validateAndCreate(array $data): static`

Validate and create.

<!-- skip-test: requires validation rules -->
```php
use event4u\DataHelpers\UserDto;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDto($data);
$dto = UserDto::validateAndCreate($_POST);
```

## Validation Methods

### `validate(): void`

Validate Dto.

<!-- skip-test: requires validation rules -->
```php
use event4u\DataHelpers\UserDto;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDto($data);
$dto->validate();
```

### `isValid(): bool`

Check if valid.

<!-- skip-test: requires validation rules -->
```php
use event4u\DataHelpers\UserDto;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDto($data);
if ($dto->isValid()) {
    // ...
}
```

### `getErrors(): array`

Get validation errors.

<!-- skip-test: requires validation rules -->
```php
use event4u\DataHelpers\UserDto;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDto($data);
$errors = $dto->getErrors();
```

## Data Access Methods

### `get(string $path, mixed $default = null): mixed`

Get value at path using dot notation. Returns mixed type without type conversion.

```php
$dto = UserDto::fromArray([
    'name' => 'John',
    'address' => ['city' => 'New York'],
]);

$name = $dto->get('name');  // 'John'
$city = $dto->get('address.city');  // 'New York'
$missing = $dto->get('phone', 'N/A');  // 'N/A'
```

### Type-Safe Single Value Getters

These methods return a single value with strict type conversion. They return `null` if the path doesn't exist or the value is `null`. They throw `TypeMismatchException` if the value cannot be converted to the expected type.

#### `getString(string $path, ?string $default = null): ?string`

Get string value with type conversion.

```php
$dto = UserDto::fromArray(['name' => 'John', 'age' => 30]);
$name = $dto->getString('name');  // 'John'
$age = $dto->getString('age');    // '30' (int → string)
$missing = $dto->getString('phone');  // null
$withDefault = $dto->getString('phone', 'N/A');  // 'N/A'
```

#### `getInt(string $path, ?int $default = null): ?int`

Get integer value with type conversion.

```php
$dto = UserDto::fromArray(['name' => 'John', 'age' => '30']);
$age = $dto->getInt('age');  // 30 (string → int)
$missing = $dto->getInt('salary');  // null
$withDefault = $dto->getInt('salary', 0);  // 0
```

#### `getFloat(string $path, ?float $default = null): ?float`

Get float value with type conversion.

<!-- skip-test: requires UserDto -->
```php
$dto = UserDto::fromArray(['name' => 'John', 'score' => '99.99']);
$score = $dto->getFloat('score');  // 99.99 (string → float)
$missing = $dto->getFloat('discount');  // null
```

#### `getBool(string $path, ?bool $default = null): ?bool`

Get boolean value with type conversion.

<!-- skip-test: requires UserDto -->
```php
$dto = UserDto::fromArray(['name' => 'John', 'active' => 1]);
$active = $dto->getBool('active');  // true (1 → true)
$missing = $dto->getBool('verified');  // null
```

#### `getArray(string $path, ?array $default = null): ?array`

Get array value.

<!-- skip-test: requires UserDto -->
```php
$dto = UserDto::fromArray(['name' => 'John', 'tags' => ['php', 'laravel']]);
$tags = $dto->getArray('tags');  // ['php', 'laravel']
$missing = $dto->getArray('categories');  // null
```

### Type-Safe Collection Getters

These methods are designed for wildcard paths and return typed arrays. They throw `TypeMismatchException` if the path doesn't contain a wildcard.

#### `getIntCollection(string $path): array`

Get array of integers from wildcard path.

<!-- skip-test: requires DepartmentDto -->
```php
$dto = DepartmentDto::fromArray([
    'employees' => [
        ['name' => 'Alice', 'age' => 30],
        ['name' => 'Bob', 'age' => 25],
    ],
]);
$ages = $dto->getIntCollection('employees.*.age');
// ['employees.0.age' => 30, 'employees.1.age' => 25]
```

#### `getStringCollection(string $path): array`

Get array of strings from wildcard path.

<!-- skip-test: requires DepartmentDto -->
```php
$dto = DepartmentDto::fromArray([
    'employees' => [
        ['name' => 'Alice'],
        ['name' => 'Bob'],
    ],
]);
$names = $dto->getStringCollection('employees.*.name');
// ['employees.0.name' => 'Alice', 'employees.1.name' => 'Bob']
```

#### `getBoolCollection(string $path): array`

Get array of booleans from wildcard path.

```php
$dto = UserDto::fromArray([
    'emails' => [
        ['email' => 'a@x.com', 'verified' => true],
        ['email' => 'b@x.com', 'verified' => false],
    ],
]);
$verified = $dto->getBoolCollection('emails.*.verified');
// ['emails.0.verified' => true, 'emails.1.verified' => false]
```

#### `getFloatCollection(string $path): array`

Get array of floats from wildcard path.

```php
$dto = OrderDto::fromArray([
    'items' => [
        ['name' => 'Item A', 'price' => 10.50],
        ['name' => 'Item B', 'price' => 25.00],
    ],
]);
$prices = $dto->getFloatCollection('items.*.price');
// ['items.0.price' => 10.5, 'items.1.price' => 25.0]
```

#### `getArrayCollection(string $path): array`

Get array of arrays from wildcard path.

```php
$dto = OrderDto::fromArray([
    'orders' => [
        ['items' => ['A', 'B']],
        ['items' => ['C', 'D']],
    ],
]);
$items = $dto->getArrayCollection('orders.*.items');
// ['orders.0.items' => ['A', 'B'], 'orders.1.items' => ['C', 'D']]
```

### `set(string $path, mixed $value): static`

Set value at path using dot notation. Returns a new DTO instance (immutable).

```php
$dto = UserDto::fromArray(['name' => 'John', 'age' => 30]);
$updated = $dto->set('name', 'Jane');
// $dto->name is still 'John' (original unchanged)
// $updated->name is 'Jane' (new instance)
```

## Serialization Methods

### `toArray(array $context = []): array`

Convert to array.

**Parameters:**
- `$context` - Optional context for conditional properties

**Basic Usage:**

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$array = $dto->toArray();
```

**With Context:**

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\WhenContext;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[WhenContext('includeEmail')]
        public readonly ?string $email = null,
    ) {}
}

$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);

// Without context
$array = $dto->toArray();
// ['name' => 'John']

// With context
$array = $dto->toArray(['includeEmail' => true]);
// ['name' => 'John', 'email' => 'john@example.com']
```

### `toJson(int $options = 0): string`

Convert to JSON.

**Parameters:**
- `$options` - JSON encoding options (e.g., `JSON_PRETTY_PRINT`, `JSON_UNESCAPED_UNICODE`)

**Basic Usage:**

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$json = $dto->toJson();
// {"name":"John","email":"john@example.com"}
```

**With Options:**

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$json = $dto->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
// Pretty-printed JSON with unescaped Unicode characters
```

### `toXml(): string`

Convert to XML.

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$xml = $dto->toXml();
```

### `toModel(string|Model $model): Model`

Convert to Eloquent model.

<!-- skip-test: requires Eloquent model -->
```php
use event4u\DataHelpers\UserDto;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDto($data);
$user = $dto->toModel(User::class);
```

## Additional Data Methods

### `with(string|array $key, mixed $value = null): static`

Add additional data to include in serialization.

**Parameters:**
- `$key` - Property name (string) or array of properties
- `$value` - Property value (only used when $key is string)

**Single Property:**

```php
$dto = UserDto::fromArray(['name' => 'John']);
$dto = $dto->with('role', 'admin');
$array = $dto->toArray();
// ['name' => 'John', 'role' => 'admin']
```

**Multiple Properties:**

```php
$dto = UserDto::fromArray(['name' => 'John']);
$dto = $dto->with([
    'role' => 'admin',
    'status' => 'active',
    'level' => 5,
]);
$array = $dto->toArray();
// ['name' => 'John', 'role' => 'admin', 'status' => 'active', 'level' => 5]
```

**With Callbacks:**

```php
$dto = UserDto::fromArray(['name' => 'John', 'age' => 30]);
$dto = $dto->with('isAdult', fn($dto) => $dto->age >= 18);
$array = $dto->toArray();
// ['name' => 'John', 'age' => 30, 'isAdult' => true]
```

**With Nested Dtos:**

```php
$addressDto = AddressDto::fromArray(['city' => 'New York']);
$dto = UserDto::fromArray(['name' => 'John']);
$dto = $dto->with('address', $addressDto);
$array = $dto->toArray();
// ['name' => 'John', 'address' => ['city' => 'New York']]
```

### `withContext(array $context): static`

Set context for conditional properties.

**Parameters:**
- `$context` - Context data for conditional property evaluation

```php
use event4u\DataHelpers\SimpleDto\Attributes\WhenContext;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[WhenContext('includeEmail')]
        public readonly ?string $email = null,
    ) {}
}

$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$dto = $dto->withContext(['includeEmail' => true]);
$array = $dto->toArray();
// ['name' => 'John', 'email' => 'john@example.com']
```

### `include(array $properties): static`

Include lazy properties.

<!-- skip-test: requires lazy properties -->
```php
use event4u\DataHelpers\UserDto;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDto($data);
$dto = $dto->include(['posts', 'comments']);
```

## Introspection Methods

### `getKeys(bool $includeHiddenFromArray = true, bool $includeHiddenFromJson = true): array`

Get all property keys (names) of the DTO.

Returns only properties defined in the final DTO class, not from traits or parent classes.
By default, all properties are included (even hidden ones).

**Parameters:**
- `$includeHiddenFromArray` - Include properties with `#[HiddenFromArray]` attribute (default: `true`)
- `$includeHiddenFromJson` - Include properties with `#[HiddenFromJson]` attribute (default: `true`)

**Returns:** Array of property names (not mapped output names)

**Get All Properties (Default):**

```php
use event4u\DataHelpers\SimpleDto;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);
$keys = $dto->getKeys();
// ['name', 'email', 'age']
```

**With Hidden Properties:**

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromJson;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Hidden]
        public readonly string $password,

        #[HiddenFromArray]
        public readonly string $internalId,

        #[HiddenFromJson]
        public readonly string $debugInfo,

        public readonly string $email,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John',
    'password' => 'secret',
    'internalId' => '123',
    'debugInfo' => 'debug',
    'email' => 'john@example.com',
]);

// Default: All properties included
$keys = $dto->getKeys();
// ['name', 'password', 'internalId', 'debugInfo', 'email']

// Exclude properties hidden from array
$keys = $dto->getKeys(includeHiddenFromArray: false);
// ['name', 'debugInfo', 'email']

// Exclude properties hidden from JSON
$keys = $dto->getKeys(includeHiddenFromJson: false);
// ['name', 'internalId', 'email']

// Exclude all hidden properties
$keys = $dto->getKeys(includeHiddenFromArray: false, includeHiddenFromJson: false);
// ['name', 'email']
```

**Use Cases:**

```php
// Dynamic property iteration
foreach ($dto->getKeys() as $key) {
    $value = $dto->get($key);
    echo "$key: $value\n";
}

// Get only visible properties for API response
$visibleKeys = $dto->getKeys(includeHiddenFromArray: false, includeHiddenFromJson: false);

// Property validation
$requiredKeys = ['name', 'email'];
$actualKeys = $dto->getKeys();
$missingKeys = array_diff($requiredKeys, $actualKeys);
```

## Visibility Methods

### `withVisibilityContext(mixed $context): static`

Set context for visibility checks with `#[Visible]` attribute.

**Parameters:**
- `$context` - Context object (e.g., current user, request)

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Visible;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Visible(callback: 'canSeeEmail')]
        public readonly ?string $email = null,
    ) {}

    private function canSeeEmail(mixed $context): bool
    {
        return $context?->isAdmin ?? false;
    }
}

$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);

// Without context - email hidden
$array = $dto->toArray();
// ['name' => 'John']

// With admin context - email visible
$adminUser = (object)['isAdmin' => true];
$array = $dto->withVisibilityContext($adminUser)->toArray();
// ['name' => 'John', 'email' => 'john@example.com']
```

### `only(array $properties): static`

Include only specified properties.

**Parameters:**
- `$properties` - Array of property names to include

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);
$dto = $dto->only(['name', 'email']);
$array = $dto->toArray();
// ['name' => 'John', 'email' => 'john@example.com']
```

### `except(array $properties): static`

Exclude specified properties.

**Parameters:**
- `$properties` - Array of property names to exclude

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);
$dto = $dto->except(['password']);
$array = $dto->toArray();
// ['name' => 'John', 'email' => 'john@example.com']
```

## See Also

- [SimpleDto Guide](/data-helpers/simple-dto/introduction/) - Complete guide
- [Creating Dtos](/data-helpers/simple-dto/creating-dtos/) - Creation methods
- [Validation](/data-helpers/simple-dto/validation/) - Validation guide
- [Serialization](/data-helpers/simple-dto/serialization/) - Serialization guide


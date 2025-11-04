---
title: SimpleDto API
description: Complete API reference for SimpleDto
---

Complete API reference for SimpleDto.

## Creation Methods

### `from(mixed $data, ?array $template = null, ?array $filters = null, ?array $pipeline = null): static`

Create DTO from data with optional template, filters, and pipeline.

**Parameters:**
- `$data` - Array, JSON string, XML string, or object
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


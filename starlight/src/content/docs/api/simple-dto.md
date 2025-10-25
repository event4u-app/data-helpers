---
title: SimpleDTO API
description: Complete API reference for SimpleDTO
---

Complete API reference for SimpleDTO.

## Creation Methods

### `fromArray(array $data): static`

Create from array.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$dto = UserDTO::fromArray($data);
```

### `fromJson(string $json): static`

Create from JSON.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$dto = UserDTO::fromJson($json);
```

### `fromModel(Model $model): static`

Create from Eloquent model.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$dto = UserDTO::fromModel($user);
```

### `fromRequest(Request $request): static`

Create from HTTP request.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$dto = UserDTO::fromRequest($request);
```

### `validateAndCreate(array $data): static`

Validate and create.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$dto = UserDTO::validateAndCreate($_POST);
```

## Validation Methods

### `validate(): void`

Validate DTO.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$dto->validate();
```

### `isValid(): bool`

Check if valid.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
if ($dto->isValid()) {
    // ...
}
```

### `getErrors(): array`

Get validation errors.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$errors = $dto->getErrors();
```

## Serialization Methods

### `toArray(): array`

Convert to array.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$array = $dto->toArray();
```

### `toJson(): string`

Convert to JSON.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$json = $dto->toJson();
```

### `toXml(): string`

Convert to XML.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$xml = $dto->toXml();
```

### `toModel(string|Model $model): Model`

Convert to Eloquent model.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$user = $dto->toModel(User::class);
```

## Conditional Methods

### `with(string $context): static`

Set context.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$dto = $dto->with('admin');
```

### `include(array $properties): static`

Include lazy properties.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$dto = $dto->include(['posts', 'comments']);
```

### `only(array $properties): static`

Include only specified properties.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$dto = $dto->only(['name', 'email']);
```

### `except(array $properties): static`

Exclude specified properties.

```php
use event4u\DataHelpers\UserDTO;

$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = new UserDTO($data);
$dto = $dto->except(['password', 'token']);
```

## See Also

- [SimpleDTO Guide](/simple-dto/introduction/) - Complete guide
- [Creating DTOs](/simple-dto/creating-dtos/) - Creation methods
- [Validation](/simple-dto/validation/) - Validation guide
- [Serialization](/simple-dto/serialization/) - Serialization guide


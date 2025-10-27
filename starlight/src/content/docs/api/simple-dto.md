---
title: SimpleDto API
description: Complete API reference for SimpleDto
---

Complete API reference for SimpleDto.

## Creation Methods

### `fromArray(array $data): static`

Create from array.

```php
$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = UserDto::fromArray($data);
```

### `fromJson(string $json): static`

Create from JSON.

```php
$json = '{"name":"John","email":"john@example.com"}';
$dto = UserDto::fromJson($json);
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

### `toArray(): array`

Convert to array.

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$array = $dto->toArray();
```

### `toJson(): string`

Convert to JSON.

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$json = $dto->toJson();
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

## Conditional Methods

### `with(string $context): static`

Set context.

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$dto = $dto->with('admin');
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

### `only(array $properties): static`

Include only specified properties.

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$dto = $dto->only(['name', 'email']);
```

### `except(array $properties): static`

Exclude specified properties.

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$dto = $dto->except(['password', 'token']);
```

## See Also

- [SimpleDto Guide](/data-helpers/simple-dto/introduction/) - Complete guide
- [Creating Dtos](/data-helpers/simple-dto/creating-dtos/) - Creation methods
- [Validation](/data-helpers/simple-dto/validation/) - Validation guide
- [Serialization](/data-helpers/simple-dto/serialization/) - Serialization guide


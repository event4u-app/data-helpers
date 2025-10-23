---
title: SimpleDTO API
description: Complete API reference for SimpleDTO
---

Complete API reference for SimpleDTO.

## Creation Methods

### `fromArray(array $data): static`

Create from array.

```php
$dto = UserDTO::fromArray($data);
```

### `fromJson(string $json): static`

Create from JSON.

```php
$dto = UserDTO::fromJson($json);
```

### `fromModel(Model $model): static`

Create from Eloquent model.

```php
$dto = UserDTO::fromModel($user);
```

### `fromRequest(Request $request): static`

Create from HTTP request.

```php
$dto = UserDTO::fromRequest($request);
```

### `validateAndCreate(array $data): static`

Validate and create.

```php
$dto = UserDTO::validateAndCreate($_POST);
```

## Validation Methods

### `validate(): void`

Validate DTO.

```php
$dto->validate();
```

### `isValid(): bool`

Check if valid.

```php
if ($dto->isValid()) {
    // ...
}
```

### `getErrors(): array`

Get validation errors.

```php
$errors = $dto->getErrors();
```

## Serialization Methods

### `toArray(): array`

Convert to array.

```php
$array = $dto->toArray();
```

### `toJson(): string`

Convert to JSON.

```php
$json = $dto->toJson();
```

### `toXml(): string`

Convert to XML.

```php
$xml = $dto->toXml();
```

### `toModel(string|Model $model): Model`

Convert to Eloquent model.

```php
$user = $dto->toModel(User::class);
```

## Conditional Methods

### `with(string $context): static`

Set context.

```php
$dto = $dto->with('admin');
```

### `include(array $properties): static`

Include lazy properties.

```php
$dto = $dto->include(['posts', 'comments']);
```

### `only(array $properties): static`

Include only specified properties.

```php
$dto = $dto->only(['name', 'email']);
```

### `except(array $properties): static`

Exclude specified properties.

```php
$dto = $dto->except(['password', 'token']);
```

## See Also

- [SimpleDTO Guide](/simple-dto/introduction/) - Complete guide
- [Creating DTOs](/simple-dto/creating-dtos/) - Creation methods
- [Validation](/simple-dto/validation/) - Validation guide
- [Serialization](/simple-dto/serialization/) - Serialization guide


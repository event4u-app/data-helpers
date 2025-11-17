---
title: Laravel Integration
description: Complete guide for using Data Helpers with Laravel
---

Complete guide for using Data Helpers with Laravel.

## Introduction

Data Helpers provides seamless Laravel integration:

- ✅ **Automatic Service Provider** - Zero configuration
- ✅ **Controller Injection** - Automatic validation & filling
- ✅ **Eloquent Integration** - fromModel(), toModel()
- ✅ **Request Validation** - validateAndCreate()
- ✅ **Artisan Commands** - make:dto, dto:typescript
- ✅ **Laravel Attributes** - WhenAuth, WhenGuest, WhenCan, WhenRole
- ✅ **API Resources** - Replace Laravel Resources

## Installation

```bash
composer require event4u/data-helpers
```

Laravel automatically discovers the service provider. **No configuration needed!**

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=data-helpers-config
```

## Controller Injection

### Automatic Validation & Injection

Type-hint your Dto in controller methods:

```php
use App\Dtos\UserRegistrationDto;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function register(UserRegistrationDto $dto): JsonResponse
    {
        // $dto is automatically validated and filled with request data
        $user = User::create($dto->toArray());

        return response()->json($user, 201);
    }
}
```

### How It Works

1. Laravel's service container detects the Dto type hint
2. Service provider creates an instance
3. Request data is automatically passed to the Dto
4. Validation runs automatically
5. Controller receives the validated Dto

### Manual Creation

```php
public function register(Request $request): JsonResponse
{
    $dto = UserRegistrationDto::fromRequest($request);
    $dto->validate(); // Throws ValidationException on failure

    $user = User::create($dto->toArray());
    return response()->json($user, 201);
}
```

## Eloquent Integration

Laravel provides seamless integration between DTOs and Eloquent models using the `HasModel` attribute.

### From Eloquent Model

<!-- skip-test: requires Eloquent User model -->
```php
$user = User::find(1);
$dto = UserDto::fromModel($user);
```

### To Eloquent Model

<!-- skip-test: requires Eloquent User model -->
```php
$dto = UserDto::fromArray($data);
$user = new User();
$dto->toModel($user);
$user->save();
```

### Using HasModel Attribute

Link your DTO to an Eloquent model:

<!-- skip-test: requires Eloquent User model -->
```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;

#[HasModel(User::class)]
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// No need to specify model class
$dto = UserDto::fromModel($user);
$newUser = $dto->toModel();
```

:::tip[No Manual Trait Import Needed]
The `SimpleDtoEloquentTrait` is **automatically included** in `SimpleDto`. You don't need to manually import it!

**How it works:**
- The trait is always available at runtime
- Methods `fromModel()` and `toModel()` check if Laravel/Eloquent is installed when called
- If Laravel is not installed, a clear `BadMethodCallException` is thrown with installation instructions
- This allows the same DTO code to work across different environments (plain PHP, Laravel, Symfony)
:::

📖 **[HasModel Attribute Details](/data-helpers/attributes/detailed/has-object/)** - Similar pattern to HasObject for plain PHP

### Update Existing Model

<!-- skip-test: requires Eloquent User model and Request -->
```php
$user = User::find(1);
$dto = UserDto::fromRequest($request);
$dto->toModel($user);
$user->save();
```

## Request Validation

### Validate and Create

```php
try {
    $dto = UserDto::validateAndCreate($request->all());
    $user = User::create($dto->toArray());
} catch (ValidationException $e) {
    return response()->json(['errors' => $e->errors()], 422);
}
```

### Custom Validation Messages

```php
class UserDto extends SimpleDto
{
    public function __construct(
        #[Required(message: 'Email is required')]
        #[Email(message: 'Invalid email format')]
        public readonly string $email,
    ) {}
}
```

## Laravel-Specific Attributes

### WhenAuth

Show property only when user is authenticated:

```php
class UserProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth]
        public readonly ?string $email = null,
    ) {}
}
```

### WhenGuest

Show property only when user is guest:

<!-- skip-test: property declaration only -->
```php
#[WhenGuest]
public readonly ?string $registerPrompt = null;
```

### WhenCan

Show property when user has permission:

<!-- skip-test: property declaration only -->
```php
#[WhenCan('edit-posts')]
public readonly ?string $editUrl = null;
```

### WhenRole

Show property when user has role:

<!-- skip-test: property declaration only -->
```php
#[WhenRole('admin')]
public readonly ?array $adminPanel = null;

// Multiple roles (OR logic)
#[WhenRole(['admin', 'moderator'])]
public readonly ?array $moderationPanel = null;
```

## API Resources

### DtoResource

Data Helpers provides `DtoResource` and `DtoResourceCollection` classes that work like Laravel's JsonResource but with automatic DateTimeFormat support.

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DateTimeFormat;
use event4u\DataHelpers\Frameworks\Laravel\Resources\DtoResource;

class EventDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[DateTimeFormat('Y-m-d H:i:s')]
        public readonly DateTime $startDate,
    ) {}
}

// In controller
$dto = new EventDto('Conference', new DateTime('2024-01-15 10:30:00'));

return new DtoResource($dto);
// {"data":{"title":"Conference","startDate":"2024-01-15 10:30:00"}}
```

### Without Wrapping

<!-- skip-test: requires Laravel JsonResource -->
```php
return DtoResource::make($dto)->withoutWrapping();
// {"title":"Conference","startDate":"2024-01-15 10:30:00"}
```

### DtoResourceCollection

<!-- skip-test: requires Laravel JsonResource -->
```php
use event4u\DataHelpers\Frameworks\Laravel\Resources\DtoResourceCollection;

$dtos = [
    new EventDto('Conference', new DateTime('2024-01-15 10:30:00')),
    new EventDto('Workshop', new DateTime('2024-01-16 14:00:00')),
];

return new DtoResourceCollection($dtos);
// {"data":[{"title":"Conference",...},{"title":"Workshop",...}]}
```

### With Pagination

<!-- skip-test: requires Laravel Paginator -->
```php
$paginator = Event::paginate(15);
$dtos = $paginator->map(fn($event) => EventDto::fromModel($event));

return new DtoResourceCollection($dtos);
// {"data":[...],"links":{...},"meta":{...}}
```

### Features

- ✅ **Automatic DateTimeFormat Support** - DateTime objects are formatted according to `#[DateTimeFormat]` attributes
- ✅ **SimpleDto and LiteDto Support** - Works with both DTO types
- ✅ **Laravel Resource Features** - Full support for wrapping, pagination, etc.
- ✅ **Type-Safe** - Full PHPStan level 9 support

### How It Works

The `DtoResource` uses `SimpleEngine::toJsonArray()` or `LiteEngine::toJsonArray()` internally, which:

1. Converts the DTO to an array
2. Formats DateTime objects according to `#[DateTimeFormat]` attributes
3. Respects `#[Hidden]` and other serialization attributes

This ensures consistent serialization across your application, whether you use:
- `json_encode($dto)` - Uses `jsonSerialize()`
- `$dto->toJson()` - Direct JSON conversion
- `new DtoResource($dto)` - Laravel Resource

## Artisan Commands

### Generate Dto

```bash
php artisan make:dto UserDto
```

Creates `app/Dtos/UserDto.php`:

```php
<?php

namespace App\Dtos;

use event4u\DataHelpers\SimpleDto;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

### Generate TypeScript

```bash
php artisan dto:typescript
```

Generates TypeScript interfaces from your Dtos.

**See also:** [Artisan Commands](/data-helpers/framework-integration/artisan-commands/) - Complete guide to all available Artisan commands

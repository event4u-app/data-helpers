---
title: Symfony Integration
description: Complete guide for using Data Helpers with Symfony
---

Complete guide for using Data Helpers with Symfony.

## Introduction

Data Helpers provides seamless Symfony integration:

- ✅ **Automatic Bundle** - Zero configuration with Flex
- ✅ **Value Resolver** - Automatic controller injection
- ✅ **Doctrine Integration** - fromEntity(), toEntity()
- ✅ **Security Integration** - WhenGranted, WhenSymfonyRole
- ✅ **Validator Integration** - Automatic validation
- ✅ **Console Commands** - make:dto, dto:typescript
- ✅ **Serializer Integration** - Normalizers

## Installation

```bash
composer require event4u/data-helpers
```

### With Symfony Flex

Symfony Flex automatically registers the bundle. **No configuration needed!**

### Without Flex

Add to `config/bundles.php`:

```php
return [
    // ...
    event4u\DataHelpers\Frameworks\Symfony\DataHelpersBundle::class => ['all' => true],
];
```

### Configuration (Optional)

Create `config/packages/data_helpers.yaml`:

```yaml
data_helpers:
  validation:
    enabled: true
    cache: true
```

## Controller Integration

### Automatic Injection

Type-hint your Dto in controller methods:

```php
use App\Dto\UserRegistrationDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(UserRegistrationDto $dto): JsonResponse
    {
        // $dto is automatically validated and filled with request data
        $user = new User();
        $dto->toEntity($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json($user, 201);
    }
}
```

### How It Works

1. Symfony's ValueResolver detects the Dto type hint
2. Request data is extracted (JSON or form data)
3. Dto is created and validated
4. Controller receives the validated Dto

### Manual Creation

<!-- skip-test: controller method -->
```php
#[Route('/register', methods: ['POST'])]
public function register(Request $request): JsonResponse
{
    $dto = UserRegistrationDto::fromRequest($request);
    $dto->validate(); // Throws ValidationException on failure

    $user = new User();
    $dto->toEntity($user);

    return $this->json($user, 201);
}
```

## Doctrine Integration

### From Doctrine Entity

<!-- skip-test: requires Doctrine EntityManager -->
```php
$user = $this->entityManager->find(User::class, 1);
$dto = UserDto::fromEntity($user);
```

### To Doctrine Entity

<!-- skip-test: requires Doctrine EntityManager -->
```php
$dto = UserDto::fromArray($data);
$user = new User();
$dto->toEntity($user);

$this->entityManager->persist($user);
$this->entityManager->flush();
```

### Update Existing Entity

<!-- skip-test: requires Doctrine EntityManager -->
```php
$user = $this->entityManager->find(User::class, 1);
$dto = UserDto::fromRequest($request);
$dto->toEntity($user);

$this->entityManager->flush();
```

## Symfony Security Integration

### WhenGranted

Show property when user has permission:

```php
use event4u\DataHelpers\SimpleDto\Attributes\WhenGranted;

class UserProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[WhenGranted('ROLE_ADMIN')]
        public readonly ?string $email = null,
    ) {}
}
```

### WhenSymfonyRole

Show property when user has role:

<!-- skip-test: property declaration only -->
```php
use event4u\DataHelpers\SimpleDto\Attributes\WhenSymfonyRole;

#[WhenSymfonyRole('ROLE_ADMIN')]
public readonly ?array $adminPanel = null;

// Multiple roles (OR logic)
#[WhenSymfonyRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])]
public readonly ?array $moderationPanel = null;
```

## Validation Integration

### Symfony Validator Attributes

```php
use Symfony\Component\Validator\Constraints as Assert;

class UserDto extends SimpleDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public readonly string $password,
    ) {}
}
```

### SimpleDto Validation Attributes

```php
use event4u\DataHelpers\SimpleDto\Attributes\*;

class UserDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}
```

## Serializer Integration

### Automatic DTO Serialization

Data Helpers provides a `DtoNormalizer` that integrates with Symfony's Serializer component. It's **automatically registered** when you install the package.

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DateTimeFormat;
use Symfony\Component\Serializer\SerializerInterface;

class EventDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[DateTimeFormat('Y-m-d H:i:s')]
        public readonly DateTime $startDate,
    ) {}
}

// In controller
public function index(SerializerInterface $serializer): Response
{
    $dto = new EventDto('Conference', new DateTime('2024-01-15 10:30:00'));

    $json = $serializer->serialize($dto, 'json');
    // {"title":"Conference","startDate":"2024-01-15 10:30:00"}

    return new JsonResponse($json, 200, [], true);
}
```

### Normalize to Array

```php
$array = $serializer->normalize($dto);
// ['title' => 'Conference', 'startDate' => '2024-01-15 10:30:00']
```

### Serialize Collections

```php
$dtos = [
    new EventDto('Conference', new DateTime('2024-01-15 10:30:00')),
    new EventDto('Workshop', new DateTime('2024-01-16 14:00:00')),
];

$json = $serializer->serialize($dtos, 'json');
// [{"title":"Conference",...},{"title":"Workshop",...}]
```

### With Context

```php
$json = $serializer->serialize($dto, 'json', [
    'groups' => ['public'],
]);
```

### Features

- ✅ **Automatic DateTimeFormat Support** - DateTime objects are formatted according to `#[DateTimeFormat]` attributes
- ✅ **SimpleDto and LiteDto Support** - Works with both DTO types
- ✅ **Symfony Serializer Features** - Full support for context, groups, etc.
- ✅ **Type-Safe** - Full PHPStan level 9 support
- ✅ **High Priority** - Registered with priority 64 to ensure it's used before default normalizers

### How It Works

The `DtoNormalizer` uses `SimpleEngine::toJsonArray()` or `LiteEngine::toJsonArray()` internally, which:

1. Converts the DTO to an array
2. Formats DateTime objects according to `#[DateTimeFormat]` attributes
3. Respects `#[Hidden]` and other serialization attributes

This ensures consistent serialization across your application, whether you use:
- `json_encode($dto)` - Uses `jsonSerialize()`
- `$dto->toJson()` - Direct JSON conversion
- Symfony Serializer - Uses `DtoNormalizer`

### Configuration

The normalizer is automatically registered via the Symfony Flex recipe in `config/services/data_helpers.yaml`:

```yaml
services:
    event4u\DataHelpers\Frameworks\Symfony\Serializer\DtoNormalizer:
        tags:
            - { name: serializer.normalizer, priority: 64 }
```

You can adjust the priority if needed by overriding the service definition in your `services.yaml`:

```yaml
event4u\DataHelpers\Frameworks\Symfony\Serializer\DtoNormalizer:
    tags:
        - { name: serializer.normalizer, priority: 100 }
```

## Console Commands

### Generate Dto

```bash
php bin/console make:dto UserDto
```

Creates `src/Dto/UserDto.php`:

```php
<?php

namespace App\Dto;

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
php bin/console dto:typescript
```

Generates TypeScript interfaces from your Dtos.

**See also:** [Console Commands](/data-helpers/framework-integration/console-commands/) - Complete guide to all available console commands

## Real-World Example

```php
use App\Dto\CreateUserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    #[Route('/users', methods: ['POST'])]
    public function create(CreateUserDto $dto): JsonResponse
    {
        $user = new User();
        $dto->toEntity($user);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, 201);
    }

    #[Route('/users/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->em->find(User::class, $id);
        $dto = UserDto::fromEntity($user);

        return $this->json($dto);
    }

    #[Route('/users/{id}', methods: ['PUT'])]
    public function update(int $id, UpdateUserDto $dto): JsonResponse
    {
        $user = $this->em->find(User::class, $id);
        $dto->toEntity($user);

        $this->em->flush();

        return $this->json($user);
    }
}
```

## Code Examples

The following working examples demonstrate Symfony integration:

- [**Symfony Doctrine**](https://github.com/event4u-app/data-helpers/blob/main/examples/framework-integration/symfony/symfony-doctrine.php) - Symfony with Doctrine

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [SymfonyIntegrationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/Frameworks/Symfony/SymfonyIntegrationTest.php) - Symfony integration tests

Run the tests:

```bash
# Run Symfony tests
task test:unit -- --filter=Symfony

# Run E2E tests
cd tests-e2e/Symfony && composer install && vendor/bin/phpunit
```
## See Also

- [Doctrine Integration](/data-helpers/framework-integration/doctrine/) - Doctrine entity mapping
- [Validation Attributes](/data-helpers/attributes/validation/) - Validation reference
- [Conditional Attributes](/data-helpers/attributes/conditional/) - Conditional visibility

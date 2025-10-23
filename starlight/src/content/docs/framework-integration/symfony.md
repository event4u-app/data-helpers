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

Type-hint your DTO in controller methods:

```php
use App\DTO\UserRegistrationDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(UserRegistrationDTO $dto): JsonResponse
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

1. Symfony's ValueResolver detects the DTO type hint
2. Request data is extracted (JSON or form data)
3. DTO is created and validated
4. Controller receives the validated DTO

### Manual Creation

```php
#[Route('/register', methods: ['POST'])]
public function register(Request $request): JsonResponse
{
    $dto = UserRegistrationDTO::fromRequest($request);
    $dto->validate(); // Throws ValidationException on failure

    $user = new User();
    $dto->toEntity($user);

    return $this->json($user, 201);
}
```

## Doctrine Integration

### From Doctrine Entity

```php
$user = $this->entityManager->find(User::class, 1);
$dto = UserDTO::fromEntity($user);
```

### To Doctrine Entity

```php
$dto = UserDTO::fromArray($data);
$user = new User();
$dto->toEntity($user);

$this->entityManager->persist($user);
$this->entityManager->flush();
```

### Update Existing Entity

```php
$user = $this->entityManager->find(User::class, 1);
$dto = UserDTO::fromRequest($request);
$dto->toEntity($user);

$this->entityManager->flush();
```

## Symfony Security Integration

### WhenGranted

Show property when user has permission:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenGranted;

class UserProfileDTO extends SimpleDTO
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

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenSymfonyRole;

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

class UserDTO extends SimpleDTO
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

### SimpleDTO Validation Attributes

```php
use event4u\DataHelpers\SimpleDTO\Attributes\*;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}
```

## Console Commands

### Generate DTO

```bash
php bin/console make:dto UserDTO
```

Creates `src/DTO/UserDTO.php`:

```php
<?php

namespace App\DTO;

use event4u\DataHelpers\SimpleDTO\SimpleDTO;

class UserDTO extends SimpleDTO
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

Generates TypeScript interfaces from your DTOs.

**See also:** [Console Commands](/framework-integration/console-commands/) - Complete guide to all available console commands

## Real-World Example

```php
use App\DTO\CreateUserDTO;
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
    public function create(CreateUserDTO $dto): JsonResponse
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
        $dto = UserDTO::fromEntity($user);

        return $this->json($dto);
    }

    #[Route('/users/{id}', methods: ['PUT'])]
    public function update(int $id, UpdateUserDTO $dto): JsonResponse
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

- [Doctrine Integration](/framework-integration/doctrine/) - Doctrine entity mapping
- [Validation Attributes](/attributes/validation/) - Validation reference
- [Conditional Attributes](/attributes/conditional/) - Conditional visibility

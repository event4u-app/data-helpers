---
title: Doctrine Integration
description: Complete guide for using Data Helpers with Doctrine ORM
---

Complete guide for using Data Helpers with Doctrine ORM.

## Introduction

Data Helpers provides seamless Doctrine integration:

- ✅ **Entity Mapping** - fromEntity(), toEntity()
- ✅ **Collection Support** - Doctrine Collections
- ✅ **Lazy Loading** - Deferred property loading
- ✅ **Relationship Handling** - OneToMany, ManyToOne, ManyToMany
- ✅ **Type Casting** - Automatic type conversion

## Installation

```bash
composer require event4u/data-helpers
composer require doctrine/orm
```

## Entity Mapping

### From Entity

Convert Doctrine entity to DTO:

```php
use App\Entity\User;

$user = $entityManager->find(User::class, 1);
$dto = UserDTO::fromEntity($user);
```

### To Entity

Convert DTO to Doctrine entity:

```php
$dto = UserDTO::fromArray($data);
$user = new User();
$dto->toEntity($user);

$entityManager->persist($user);
$entityManager->flush();
```

### Update Existing Entity

```php
$user = $entityManager->find(User::class, 1);
$dto = UserDTO::fromArray($data);
$dto->toEntity($user);

$entityManager->flush();
```

## Collection Support

### Doctrine Collections

```php
use Doctrine\Common\Collections\Collection;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly Collection $posts,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            name: $user->getName(),
            posts: $user->getPosts(),
        );
    }
}
```

### Convert to Array

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly array $posts,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            name: $user->getName(),
            posts: array_map(
                fn($post) => PostDTO::fromEntity($post),
                $user->getPosts()->toArray()
            ),
        );
    }
}
```

## Relationships

### OneToMany

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly array $posts,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            name: $user->getName(),
            posts: PostDTO::collection($user->getPosts()),
        );
    }
}
```

### ManyToOne

```php
class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly UserDTO $author,
    ) {}

    public static function fromEntity(Post $post): self
    {
        return new self(
            title: $post->getTitle(),
            author: UserDTO::fromEntity($post->getAuthor()),
        );
    }
}
```

### ManyToMany

```php
class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly array $tags,
    ) {}

    public static function fromEntity(Post $post): self
    {
        return new self(
            title: $post->getTitle(),
            tags: TagDTO::collection($post->getTags()),
        );
    }
}
```

## Lazy Loading

### Lazy Properties

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[Lazy]
        public readonly array $posts,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            name: $user->getName(),
            posts: fn() => PostDTO::collection($user->getPosts()),
        );
    }
}

// Posts are only loaded when accessed
$dto = UserDTO::fromEntity($user);
$posts = $dto->posts; // Loads posts now
```

## Real-World Example

```php
use App\Entity\User;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function createUser(CreateUserDTO $dto): UserDTO
    {
        $user = new User();
        $dto->toEntity($user);

        $this->em->persist($user);
        $this->em->flush();

        return UserDTO::fromEntity($user);
    }

    public function updateUser(int $id, UpdateUserDTO $dto): UserDTO
    {
        $user = $this->em->find(User::class, $id);
        $dto->toEntity($user);

        $this->em->flush();

        return UserDTO::fromEntity($user);
    }

    public function getUser(int $id): UserDTO
    {
        $user = $this->em->find(User::class, $id);
        return UserDTO::fromEntity($user);
    }
}
```

## Best Practices

### Use DTOs for API Responses

```php
// ✅ Good - DTO for API response
public function show(int $id): JsonResponse
{
    $user = $this->em->find(User::class, $id);
    $dto = UserDTO::fromEntity($user);
    return $this->json($dto);
}

// ❌ Bad - Entity for API response
public function show(int $id): JsonResponse
{
    $user = $this->em->find(User::class, $id);
    return $this->json($user);
}
```

### Use Lazy Loading for Relationships

```php
// ✅ Good - lazy load relationships
#[Lazy]
public readonly array $posts;

// ❌ Bad - eager load all relationships
public readonly array $posts;
```

## Code Examples

The following working examples demonstrate Doctrine integration:

- [**Doctrine Integration**](https://github.com/event4u-app/data-helpers/blob/main/examples/framework-integration/doctrine/doctrine-integration.php) - Working with Doctrine entities

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [DataAccessorDoctrineTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataAccessor/DataAccessorDoctrineTest.php) - Doctrine accessor tests
- [DataMutatorDoctrineTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMutator/DataMutatorDoctrineTest.php) - Doctrine mutator tests
- [DoctrineIntegrationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDTO/DoctrineIntegrationTest.php) - DTO Doctrine integration tests

Run the tests:

```bash
# Run Doctrine tests
task test:unit -- --filter=Doctrine
```
## See Also

- [Symfony Integration](/framework-integration/symfony/) - Symfony guide
- [Lazy Properties](/simple-dto/lazy-properties/) - Lazy loading guide
- [Collections](/simple-dto/collections/) - Collection handling

# Symfony Integration

Learn how to use SimpleDTO with Symfony for Doctrine, security, validation, and more.

---

## 🎯 Overview

SimpleDTO provides seamless Symfony integration:

- ✅ **Doctrine Integration** - fromEntity(), toEntity()
- ✅ **Security Integration** - WhenGranted, WhenSymfonyRole
- ✅ **Validator Integration** - Automatic validation
- ✅ **Controller Integration** - Argument resolvers
- ✅ **Console Commands** - make:dto, dto:typescript
- ✅ **Serializer Integration** - Normalizers

---

## 🚀 Installation

```bash
composer require event4u/data-helpers
```

Symfony will automatically configure the bundle.

### Configuration (Optional)

Create `config/packages/simple_dto.yaml`:

```yaml
simple_dto:
  validation:
    cache_rules: true
    cache_ttl: 3600
  
  casts:
    cache_instances: true
  
  typescript:
    output_path: '%kernel.project_dir%/assets/types'
    namespace: 'App\DTO'
```

---

## 🗄️ Doctrine Integration

### From Entity

```php
use App\Entity\User;

$user = $entityManager->find(User::class, 1);
$dto = UserDTO::fromEntity($user);
```

### To Entity

```php
$dto = UserDTO::fromArray($request->request->all());
$user = $dto->toEntity(User::class);
$entityManager->persist($user);
$entityManager->flush();
```

### Create Entity from DTO

```php
$dto = UserDTO::validateAndCreate($request->request->all());
$user = new User();
$user->setName($dto->name);
$user->setEmail($dto->email);
$entityManager->persist($user);
$entityManager->flush();
```

### Update Entity from DTO

```php
$user = $entityManager->find(User::class, 1);
$dto = UserDTO::validateAndCreate($request->request->all());
$user->setName($dto->name);
$user->setEmail($dto->email);
$entityManager->flush();
```

### With Relationships

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?array $posts = null,
    ) {}
    
    public static function fromEntity(User $user): self
    {
        return new self(
            name: $user->getName(),
            email: $user->getEmail(),
            posts: array_map(
                fn($post) => PostDTO::fromEntity($post),
                $user->getPosts()->toArray()
            ),
        );
    }
}

$user = $repository->find(1);
$dto = UserDTO::fromEntity($user);
```

---

## 🔐 Security Integration

### WhenGranted

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenGranted;

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        
        #[WhenGranted('EDIT')]
        public readonly ?string $editUrl = null,
        
        #[WhenGranted('DELETE', 'subject')]
        public readonly ?string $deleteUrl = null,
    ) {}
}

// In controller
$dto = PostDTO::fromEntity($post);
$array = $dto->withContext([
    'security' => $this->security,
    'subject' => $post,
])->toArray();
```

### WhenSymfonyRole

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenSymfonyRole;

class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[WhenSymfonyRole('ROLE_ADMIN')]
        public readonly ?array $adminPanel = null,
        
        #[WhenSymfonyRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])]
        public readonly ?array $moderationTools = null,
    ) {}
}
```

### With Security Component

```php
use Symfony\Bundle\SecurityBundle\Security;

class UserController extends AbstractController
{
    public function __construct(
        private Security $security
    ) {}
    
    #[Route('/api/users/{id}', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        $dto = UserDTO::fromEntity($user);
        
        return $this->json(
            $dto->withContext(['security' => $this->security])->toArray()
        );
    }
}
```

---

## ✅ Validation Integration

### Basic Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;

class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        
        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}

// In controller
#[Route('/api/users', methods: ['POST'])]
public function create(Request $request): JsonResponse
{
    $dto = CreateUserDTO::validateAndCreate($request->request->all());
    
    $user = new User();
    $user->setEmail($dto->email);
    $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
    
    $this->entityManager->persist($user);
    $this->entityManager->flush();
    
    return $this->json($user, 201);
}
```

### With Symfony Validator

```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}
    
    #[Route('/api/users', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = CreateUserDTO::fromArray($request->request->all());
        
        // Validate using Symfony Validator
        $errors = $this->validator->validate($dto);
        
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 422);
        }
        
        // Process DTO
        $user = new User();
        $user->setEmail($dto->email);
        
        return $this->json($user, 201);
    }
}
```

---

## 🎯 Controller Integration

### Argument Resolver

```php
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;

class DTOValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();
        
        if (!$type || !is_subclass_of($type, SimpleDTO::class)) {
            return [];
        }
        
        $dto = $type::validateAndCreate($request->request->all());
        
        yield $dto;
    }
}

// Register in services.yaml
services:
    App\Resolver\DTOValueResolver:
        tags:
            - { name: controller.argument_value_resolver, priority: 150 }
```

### Using in Controller

```php
#[Route('/api/users', methods: ['POST'])]
public function create(CreateUserDTO $dto): JsonResponse
{
    // DTO is automatically validated and injected!
    $user = new User();
    $user->setEmail($dto->email);
    
    $this->entityManager->persist($user);
    $this->entityManager->flush();
    
    return $this->json($user, 201);
}
```

---

## 🎨 API Platform Integration

### As API Resource

```php
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new Get(),
        new Post(input: CreateUserDTO::class),
    ]
)]
class User
{
    // Entity properties
}
```

### Custom Data Provider

```php
use ApiPlatform\State\ProviderInterface;

class UserDTOProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->repository->find($uriVariables['id']);
        
        return UserDTO::fromEntity($user);
    }
}
```

---

## 🎯 Real-World Examples

### Example 1: REST API

```php
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->repository->findAll();
        $dtos = array_map(
            fn($user) => UserDTO::fromEntity($user),
            $users
        );
        
        return $this->json($dtos);
    }
    
    #[Route('', methods: ['POST'])]
    public function create(CreateUserDTO $dto): JsonResponse
    {
        $user = new User();
        $user->setName($dto->name);
        $user->setEmail($dto->email);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $this->json(
            UserDTO::fromEntity($user),
            201
        );
    }
    
    #[Route('/{id}', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json(UserDTO::fromEntity($user));
    }
    
    #[Route('/{id}', methods: ['PUT'])]
    public function update(User $user, UpdateUserDTO $dto): JsonResponse
    {
        $user->setName($dto->name);
        $user->setEmail($dto->email);
        
        $this->entityManager->flush();
        
        return $this->json(UserDTO::fromEntity($user));
    }
    
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        
        return $this->json(null, 204);
    }
}
```

### Example 2: Form Handling

```php
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('email')
            ->add('password');
    }
}

// In controller
#[Route('/users/create', methods: ['POST'])]
public function create(Request $request): Response
{
    $form = $this->createForm(UserType::class);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        $dto = CreateUserDTO::fromArray($form->getData());
        
        $user = new User();
        $user->setName($dto->name);
        $user->setEmail($dto->email);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
    }
    
    return $this->render('user/create.html.twig', [
        'form' => $form,
    ]);
}
```

### Example 3: Event Subscribers

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserCreatedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::class => 'onUserCreated',
        ];
    }
    
    public function onUserCreated(UserCreatedEvent $event): void
    {
        $dto = UserDTO::fromEntity($event->getUser());
        
        // Send welcome email, etc.
    }
}
```

---

## 🛠️ Console Commands

### Create DTO

```bash
bin/console make:dto UserDTO
bin/console make:dto User/ProfileDTO
```

### Generate TypeScript

```bash
bin/console dto:typescript
bin/console dto:typescript --output=assets/types
```

### List DTOs

```bash
bin/console dto:list
```

### Validate DTO

```bash
bin/console dto:validate UserDTO
```

---

## 🔄 Messenger Integration

### Message DTOs

```php
use Symfony\Component\Messenger\MessageBusInterface;

class UserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus
    ) {}
    
    #[Route('/api/users', methods: ['POST'])]
    public function create(CreateUserDTO $dto): JsonResponse
    {
        $this->bus->dispatch(new CreateUserMessage($dto));
        
        return $this->json(['status' => 'queued'], 202);
    }
}

// Message
class CreateUserMessage
{
    public function __construct(
        public readonly CreateUserDTO $dto
    ) {}
}

// Handler
class CreateUserMessageHandler implements MessageHandlerInterface
{
    public function __invoke(CreateUserMessage $message): void
    {
        $dto = $message->dto;
        
        $user = new User();
        $user->setName($dto->name);
        $user->setEmail($dto->email);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
```

---

## 💡 Best Practices

### 1. Use DTOs for API Responses

```php
// ✅ Good - consistent API responses
return $this->json(UserDTO::fromEntity($user));

// ❌ Bad - inconsistent responses
return $this->json($user);
```

### 2. Validate at Controller Entry

```php
// ✅ Good - validate early
public function create(CreateUserDTO $dto): JsonResponse

// ❌ Bad - validate late
public function create(Request $request): JsonResponse
```

### 3. Use Type Hints

```php
// ✅ Good - type hinted
public function create(CreateUserDTO $dto): JsonResponse

// ❌ Bad - no type hints
public function create($dto)
```

### 4. Separate Request and Response DTOs

```php
// ✅ Good - separate DTOs
class CreateUserDTO extends SimpleDTO { /* ... */ }
class UserResponseDTO extends SimpleDTO { /* ... */ }

// ❌ Bad - same DTO for both
class UserDTO extends SimpleDTO { /* ... */ }
```

---

## 📚 Next Steps

1. [Laravel Integration](17-laravel-integration.md) - Laravel features
2. [Validation](07-validation.md) - Advanced validation
3. [Security & Visibility](22-security-visibility.md) - Security features
4. [Console Commands](26-console-commands.md) - All commands

---

**Previous:** [Laravel Integration](17-laravel-integration.md)  
**Next:** [Plain PHP Usage](19-plain-php.md)


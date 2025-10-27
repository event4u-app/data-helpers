<?php

declare(strict_types=1);

/**
 * Phase 15.3: Symfony Request Validation Integration
 *
 * This example demonstrates Symfony-specific features:
 * - DtoValueResolver for automatic controller injection
 * - Integration with Symfony Validator
 * - Attribute-based routing with Dtos
 *
 * Note: This example shows the API, but requires a Symfony application to run.
 */

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Between;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\ValidateRequest;

echo "=== Phase 15.3: Symfony Request Validation Integration ===\n\n";

// Example 1: Dto with ValidateRequest Attribute
echo "1. Dto with ValidateRequest Attribute\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(throw: true)]
class CreateUserDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,

        #[Between(18, 120)]
        public readonly int $age,
    ) {}
}

echo "✅  CreateUserDto defined with ValidateRequest attribute\n";
echo "    - Automatic validation in controllers\n";
echo "    - Throws ValidationException on failure\n";
echo "\n";

// Example 2: Controller Method with Dto Injection
echo "2. Controller Method with Dto Injection\n";
echo str_repeat('-', 60) . "\n";

echo "```php\n";
echo "use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;\n";
echo "use Symfony\Component\HttpFoundation\Response;\n";
echo "use Symfony\Component\Routing\Annotation\Route;\n";
echo "\n";
echo "class UserController extends AbstractController\n";
echo "{\n";
echo "    #[Route('/users', methods: ['POST'])]\n";
echo "    public function store(CreateUserDto \$dto): Response\n";
echo "    {\n";
echo "        // \$dto is automatically validated!\n";
echo "        \$user = new User();\n";
echo "        \$user->setEmail(\$dto->email);\n";
echo "        \$user->setName(\$dto->name);\n";
echo "        \$user->setAge(\$dto->age);\n";
echo "\n";
echo "        \$this->entityManager->persist(\$user);\n";
echo "        \$this->entityManager->flush();\n";
echo "\n";
echo "        return \$this->json(\$user);\n";
echo "    }\n";
echo "}\n";
echo "```\n";
echo "\n";
echo "✅  Dto is automatically:\n";
echo "    - Created from request data (JSON or form)\n";
echo "    - Validated using defined rules\n";
echo "    - Injected into controller method\n";
echo "\n";

// Example 3: Update Dto with Partial Validation
echo "3. Update Dto with Partial Validation\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(throw: true, except: ['email'])]
class UpdateUserDto extends SimpleDto
{
    public function __construct(
        #[Email]
        public readonly ?string $email = null,

        #[Min(3)]
        public readonly ?string $name = null,

        #[Between(18, 120)]
        public readonly ?int $age = null,
    ) {}
}

echo "✅  UpdateUserDto defined for PATCH requests\n";
echo "    - All fields are optional\n";
echo "    - Email validation is excluded\n";
echo "    - Only provided fields are validated\n";
echo "\n";

echo "```php\n";
echo "#[Route('/users/{id}', methods: ['PATCH'])]\n";
echo "public function update(int \$id, UpdateUserDto \$dto): Response\n";
echo "{\n";
echo "    \$user = \$this->userRepository->find(\$id);\n";
echo "    if (!\$user) {\n";
echo "        throw \$this->createNotFoundException();\n";
echo "    }\n";
echo "\n";
echo "    // Update only provided fields\n";
echo "    foreach (\$dto->partial() as \$key => \$value) {\n";
echo "        \$setter = 'set' . ucfirst(\$key);\n";
echo "        if (method_exists(\$user, \$setter)) {\n";
echo "            \$user->\$setter(\$value);\n";
echo "        }\n";
echo "    }\n";
echo "\n";
echo "    \$this->entityManager->flush();\n";
echo "    return \$this->json(\$user);\n";
echo "}\n";
echo "```\n";
echo "\n";

// Example 4: API Resource Controller
echo "4. API Resource Controller\n";
echo str_repeat('-', 60) . "\n";

echo "```php\n";
echo "class UserController extends AbstractController\n";
echo "{\n";
echo "    #[Route('/users', methods: ['GET'])]\n";
echo "    public function index(): Response\n";
echo "    {\n";
echo "        \$users = \$this->userRepository->findAll();\n";
echo "        \$dtos = array_map(\n";
echo "            fn(\$user) => UserDto::from(\$user),\n";
echo "            \$users\n";
echo "        );\n";
echo "        return \$this->json(\$dtos);\n";
echo "    }\n";
echo "\n";
echo "    #[Route('/users', methods: ['POST'])]\n";
echo "    public function store(CreateUserDto \$dto): Response\n";
echo "    {\n";
echo "        \$user = new User();\n";
echo "        \$user->setEmail(\$dto->email);\n";
echo "        \$user->setName(\$dto->name);\n";
echo "        \$user->setAge(\$dto->age);\n";
echo "\n";
echo "        \$this->entityManager->persist(\$user);\n";
echo "        \$this->entityManager->flush();\n";
echo "\n";
echo "        return \$this->json(UserDto::from(\$user), 201);\n";
echo "    }\n";
echo "\n";
echo "    #[Route('/users/{id}', methods: ['GET'])]\n";
echo "    public function show(int \$id): Response\n";
echo "    {\n";
echo "        \$user = \$this->userRepository->find(\$id);\n";
echo "        if (!\$user) {\n";
echo "            throw \$this->createNotFoundException();\n";
echo "        }\n";
echo "        return \$this->json(UserDto::from(\$user));\n";
echo "    }\n";
echo "\n";
echo "    #[Route('/users/{id}', methods: ['PATCH'])]\n";
echo "    public function update(int \$id, UpdateUserDto \$dto): Response\n";
echo "    {\n";
echo "        \$user = \$this->userRepository->find(\$id);\n";
echo "        if (!\$user) {\n";
echo "            throw \$this->createNotFoundException();\n";
echo "        }\n";
echo "\n";
echo "        foreach (\$dto->partial() as \$key => \$value) {\n";
echo "            \$setter = 'set' . ucfirst(\$key);\n";
echo "            if (method_exists(\$user, \$setter)) {\n";
echo "                \$user->\$setter(\$value);\n";
echo "            }\n";
echo "        }\n";
echo "\n";
echo "        \$this->entityManager->flush();\n";
echo "        return \$this->json(UserDto::from(\$user));\n";
echo "    }\n";
echo "\n";
echo "    #[Route('/users/{id}', methods: ['DELETE'])]\n";
echo "    public function destroy(int \$id): Response\n";
echo "    {\n";
echo "        \$user = \$this->userRepository->find(\$id);\n";
echo "        if (!\$user) {\n";
echo "            throw \$this->createNotFoundException();\n";
echo "        }\n";
echo "\n";
echo "        \$this->entityManager->remove(\$user);\n";
echo "        \$this->entityManager->flush();\n";
echo "\n";
echo "        return new Response(null, 204);\n";
echo "    }\n";
echo "}\n";
echo "```\n";
echo "\n";
echo "✅  Complete CRUD API with automatic validation\n";
echo "\n";

// Example 5: Bundle Registration
echo "5. Bundle Registration\n";
echo str_repeat('-', 60) . "\n";

echo "Add to config/bundles.php:\n";
echo "```php\n";
echo "return [\n";
echo "    // ...\n";
echo "    event4u\\DataHelpers\\Symfony\\DtoBundle::class => ['all' => true],\n";
echo "];\n";
echo "```\n";
echo "\n";
echo "✅  Enables automatic Dto injection in controllers\n";
echo "\n";

// Example 6: Error Handling
echo "6. Error Handling\n";
echo str_repeat('-', 60) . "\n";

echo "```php\n";
echo "// In src/EventListener/ValidationExceptionListener.php\n";
echo "use event4u\\DataHelpers\\Validation\\ValidationException;\n";
echo "use Symfony\\Component\\HttpFoundation\\JsonResponse;\n";
echo "use Symfony\\Component\\HttpKernel\\Event\\ExceptionEvent;\n";
echo "\n";
echo "class ValidationExceptionListener\n";
echo "{\n";
echo "    public function onKernelException(ExceptionEvent \$event): void\n";
echo "    {\n";
echo "        \$exception = \$event->getThrowable();\n";
echo "\n";
echo "        if (!\$exception instanceof ValidationException) {\n";
echo "            return;\n";
echo "        }\n";
echo "\n";
echo "        \$response = new JsonResponse([\n";
echo "            'message' => \$exception->getMessage(),\n";
echo "            'errors' => \$exception->errors(),\n";
echo "        ], 422);\n";
echo "\n";
echo "        \$event->setResponse(\$response);\n";
echo "    }\n";
echo "}\n";
echo "```\n";
echo "\n";
echo "Register in services.yaml:\n";
echo "```yaml\n";
echo "services:\n";
echo "    App\\EventListener\\ValidationExceptionListener:\n";
echo "        tags:\n";
echo "            - { name: kernel.event_listener, event: kernel.exception }\n";
echo "```\n";
echo "\n";
echo "✅  Automatic error handling for JSON API responses\n";
echo "\n";

// Example 7: Console Command
echo "7. Console Command\n";
echo str_repeat('-', 60) . "\n";

echo "Generate Dtos with:\n";
echo "```bash\n";
echo "php bin/console make:dto UserDto\n";
echo "php bin/console make:dto UserDto --validate\n";
echo "```\n";
echo "\n";
echo "✅  Quick Dto generation with validation support\n";
echo "\n";

echo "=== Symfony Integration Complete! ===\n";
echo "\n";
echo "Key Features:\n";
echo "  ✅  Automatic controller injection (DtoValueResolver)\n";
echo "  ✅  Attribute-based routing support\n";
echo "  ✅  Symfony Validator integration\n";
echo "  ✅  Custom validation messages\n";
echo "  ✅  Partial updates (PATCH)\n";
echo "  ✅  JSON API support\n";
echo "  ✅  Error handling (EventListener)\n";
echo "  ✅  Console command (make:dto)\n";

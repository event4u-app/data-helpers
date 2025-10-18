<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Max;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;
use event4u\DataHelpers\SimpleDTO\Attributes\In;
use event4u\DataHelpers\SimpleDTO\Attributes\Uuid;
use event4u\DataHelpers\SimpleDTO\Attributes\Size;
use event4u\DataHelpers\SimpleDTO\Attributes\Ip;
use event4u\DataHelpers\SimpleDTO\Attributes\Json;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;

echo str_repeat('=', 80) . "\n";
echo "SYMFONY VALIDATION INTEGRATION\n";
echo str_repeat('=', 80) . "\n\n";

// Example 1: Validation Attributes with Symfony Constraint Support
echo "1. VALIDATION ATTRIBUTES WITH SYMFONY CONSTRAINT SUPPORT:\n";
echo str_repeat('-', 80) . "\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        #[Max(50)]
        public readonly string $name,

        #[Between(18, 120)]
        public readonly int $age,

        #[In(['admin', 'user', 'guest'])]
        public readonly string $role,
    ) {}
}

echo "UserDTO defined with validation attributes.\n";
echo "Each attribute implements both ValidationRule and SymfonyConstraint interfaces.\n\n";

// Show that attributes implement SymfonyConstraint
$emailAttr = new Email();
$requiredAttr = new Required();
$minAttr = new Min(3);

echo "Attribute Interfaces:\n";
echo "  - Email implements SymfonyConstraint: " . ($emailAttr instanceof SymfonyConstraint ? '✅ Yes' : '❌ No') . "\n";
echo "  - Required implements SymfonyConstraint: " . ($requiredAttr instanceof SymfonyConstraint ? '✅ Yes' : '❌ No') . "\n";
echo "  - Min implements SymfonyConstraint: " . ($minAttr instanceof SymfonyConstraint ? '✅ Yes' : '❌ No') . "\n";

// Example 2: Laravel vs Symfony Rule Generation
echo "\n\n2. LARAVEL VS SYMFONY RULE GENERATION:\n";
echo str_repeat('-', 80) . "\n";

echo "Laravel Rules (string format):\n";
echo "  - Required: " . $requiredAttr->rule() . "\n";
echo "  - Email: " . $emailAttr->rule() . "\n";
echo "  - Min(3): " . $minAttr->rule() . "\n";

echo "\nSymfony Constraints (object format):\n";
if (class_exists('Symfony\Component\Validator\Constraints\NotBlank')) {
    echo "  - Required: " . get_class($requiredAttr->constraint()) . "\n";
    echo "  - Email: " . get_class($emailAttr->constraint()) . "\n";
    echo "  - Min(3): " . get_class($minAttr->constraint()) . "\n";
} else {
    echo "  ⚠️  Symfony Validator not installed\n";
    echo "  Install with: composer require symfony/validator\n";
}

// Example 3: Advanced Validation Attributes
echo "\n\n3. ADVANCED VALIDATION ATTRIBUTES:\n";
echo str_repeat('-', 80) . "\n";

class AdvancedDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Uuid]
        public readonly string $id,

        #[Required]
        #[Ip]
        public readonly string $ipAddress,

        #[Required]
        #[Json]
        public readonly string $settings,

        #[Required]
        #[Size(10)]
        public readonly string $phoneNumber,
    ) {}
}

$uuidAttr = new Uuid();
$ipAttr = new Ip();
$jsonAttr = new Json();
$sizeAttr = new Size(10);

echo "Laravel Rules:\n";
echo "  - Uuid: " . $uuidAttr->rule() . "\n";
echo "  - Ip: " . $ipAttr->rule() . "\n";
echo "  - Json: " . $jsonAttr->rule() . "\n";
echo "  - Size(10): " . $sizeAttr->rule() . "\n";

echo "\nSymfony Constraints:\n";
if (class_exists('Symfony\Component\Validator\Constraints\Uuid')) {
    echo "  - Uuid: " . get_class($uuidAttr->constraint()) . "\n";
    echo "  - Ip: " . get_class($ipAttr->constraint()) . "\n";
    echo "  - Json: " . get_class($jsonAttr->constraint()) . "\n";
    echo "  - Size(10): " . get_class($sizeAttr->constraint()) . "\n";
} else {
    echo "  ⚠️  Symfony Validator not installed\n";
}

// Example 4: Mapping Table
echo "\n\n4. VALIDATION ATTRIBUTE TO SYMFONY CONSTRAINT MAPPING:\n";
echo str_repeat('-', 80) . "\n";

$mappings = [
    'Required' => 'NotBlank',
    'Email' => 'Email',
    'Min' => 'GreaterThanOrEqual',
    'Max' => 'LessThanOrEqual',
    'Between' => 'Range',
    'In' => 'Choice',
    'NotIn' => 'Choice (match: false)',
    'Regex' => 'Regex',
    'Url' => 'Url',
    'Uuid' => 'Uuid',
    'Size' => 'Length (exactly)',
    'Ip' => 'Ip',
    'Json' => 'Json',
];

foreach ($mappings as $attribute => $constraint) {
    echo sprintf("  %-15s → Symfony\\Component\\Validator\\Constraints\\%s\n", $attribute, $constraint);
}

echo "\n\n" . str_repeat('=', 80) . "\n";
echo "✅  All Symfony validation integration examples completed!\n";
echo str_repeat('=', 80) . "\n\n";

echo "USAGE IN SYMFONY:\n";
echo str_repeat('-', 80) . "\n";
echo <<<'USAGE'
// In a Symfony Controller:
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;

#[ValidateRequest]
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        
        #[Required, Min(3)]
        public readonly string $name,
        
        #[Between(18, 120)]
        public readonly int $age,
    ) {}
}

// Controller - automatic validation!
class UserController extends AbstractController
{
    #[Route('/users', methods: ['POST'])]
    public function store(UserDTO $dto): Response
    {
        // $dto is already validated using framework-independent validator
        // When Symfony Validator is available, it uses Symfony constraints
        
        $user = new User();
        $user->setEmail($dto->email);
        $user->setName($dto->name);
        $user->setAge($dto->age);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $this->json($user, 201);
    }
}

// The DTOs automatically generate Symfony constraints when Symfony Validator is available.
// All validation attributes implement both ValidationRule and SymfonyConstraint interfaces.
// This provides seamless integration with both Laravel and Symfony frameworks.
USAGE;
echo "\n" . str_repeat('=', 80) . "\n";

echo "\nNOTE:\n";
echo "  - All validation attributes implement SymfonyConstraint interface\n";
echo "  - Symfony constraints are generated automatically when Symfony Validator is available\n";
echo "  - Falls back to framework-independent validator when Symfony is not available\n";
echo "  - Same attributes work for both Laravel and Symfony!\n";


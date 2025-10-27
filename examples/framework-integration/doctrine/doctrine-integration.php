<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoDoctrineTrait;

echo "=== Doctrine Integration Example ===\n\n";

// Mock Doctrine Entity (for demonstration purposes)
class User
{
    private ?int $id = null;
    private string $name = '';
    private string $email = '';
    private ?int $age = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): void
    {
        $this->age = $age;
    }
}

// Dto with Doctrine integration
class UserDto extends SimpleDto
{
    use SimpleDtoDoctrineTrait;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?int $age = null,
        public readonly ?int $id = null,
    ) {}
}

// Example 1: Create Dto from Entity
echo "1. Create Dto from Entity (fromEntity)\n";
echo "----------------------------------------\n";

$user = new User();
$user->setId(1);
$user->setName('John Doe');
$user->setEmail('john@example.com');
$user->setAge(30);

/** @phpstan-ignore-next-line unknown */
$dto = UserDto::fromEntity($user);

echo "Entity data:\n";
echo sprintf('  ID: %s%s', $user->getId(), PHP_EOL);
echo sprintf('  Name: %s%s', $user->getName(), PHP_EOL);
echo sprintf('  Email: %s%s', $user->getEmail(), PHP_EOL);
echo sprintf('  Age: %s%s', $user->getAge(), PHP_EOL);
echo "\n";

echo "Dto data:\n";
echo sprintf('  ID: %s%s', $dto->id, PHP_EOL);
echo sprintf('  Name: %s%s', $dto->name, PHP_EOL);
echo sprintf('  Email: %s%s', $dto->email, PHP_EOL);
echo sprintf('  Age: %s%s', $dto->age, PHP_EOL);
echo "\n\n";

// Example 2: Create Entity from Dto
echo "2. Create Entity from Dto (toEntity)\n";
echo "----------------------------------------\n";

$dto2 = UserDto::fromArray([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'age' => 25,
]);

/** @phpstan-ignore-next-line unknown */
/** @var User $entity */
$entity = $dto2->toEntity(User::class);

echo "Dto data:\n";
echo sprintf('  Name: %s%s', $dto2->name, PHP_EOL);
echo sprintf('  Email: %s%s', $dto2->email, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('  Age: %s%s', $dto2->age, PHP_EOL);
echo "\n";

echo "Entity data:\n";
echo sprintf('  Name: %s%s', $entity->getName(), PHP_EOL);
echo sprintf('  Email: %s%s', $entity->getEmail(), PHP_EOL);
echo sprintf('  Age: %s%s', $entity->getAge(), PHP_EOL);
echo "\n\n";

// Example 3: Update Entity from Dto
echo "3. Update Entity from Dto\n";
echo "----------------------------------------\n";

$existingEntity = new User();
$existingEntity->setId(42);
$existingEntity->setName('Old Name');
$existingEntity->setEmail('old@example.com');
$existingEntity->setAge(40);

echo "Before update:\n";
echo sprintf('  ID: %s%s', $existingEntity->getId(), PHP_EOL);
echo sprintf('  Name: %s%s', $existingEntity->getName(), PHP_EOL);
echo sprintf('  Email: %s%s', $existingEntity->getEmail(), PHP_EOL);
echo sprintf('  Age: %s%s', $existingEntity->getAge(), PHP_EOL);
echo "\n";

$updateDto = UserDto::fromArray([
    'name' => 'Updated Name',
    'email' => 'updated@example.com',
    'age' => 45,
]);

// Update entity properties
$existingEntity->setName($updateDto->name);
$existingEntity->setEmail($updateDto->email);
/** @phpstan-ignore-next-line unknown */
$existingEntity->setAge($updateDto->age);

echo "After update:\n";
echo sprintf('  ID: %s%s', $existingEntity->getId(), PHP_EOL);
echo sprintf('  Name: %s%s', $existingEntity->getName(), PHP_EOL);
echo sprintf('  Email: %s%s', $existingEntity->getEmail(), PHP_EOL);
echo sprintf('  Age: %s%s', $existingEntity->getAge(), PHP_EOL);
echo "\n\n";

// Example 4: Round-trip (Entity → Dto → Entity)
echo "4. Round-trip (Entity → Dto → Entity)\n";
echo "----------------------------------------\n";

$originalEntity = new User();
$originalEntity->setId(99);
$originalEntity->setName('Round Trip');
$originalEntity->setEmail('roundtrip@example.com');
$originalEntity->setAge(35);

echo "Original entity:\n";
echo sprintf('  ID: %s%s', $originalEntity->getId(), PHP_EOL);
echo sprintf('  Name: %s%s', $originalEntity->getName(), PHP_EOL);
echo sprintf('  Email: %s%s', $originalEntity->getEmail(), PHP_EOL);
echo sprintf('  Age: %s%s', $originalEntity->getAge(), PHP_EOL);
echo "\n";

/** @phpstan-ignore-next-line unknown */
$roundTripDto = UserDto::fromEntity($originalEntity);
/** @var User $roundTripEntity */
$roundTripEntity = $roundTripDto->toEntity(User::class);

echo "After round-trip:\n";
echo sprintf('  Name: %s%s', $roundTripEntity->getName(), PHP_EOL);
echo sprintf('  Email: %s%s', $roundTripEntity->getEmail(), PHP_EOL);
echo sprintf('  Age: %s%s', $roundTripEntity->getAge(), PHP_EOL);
echo "\n";

echo "Data preserved: " . (
    $roundTripEntity->getName() === $originalEntity->getName() &&
    $roundTripEntity->getEmail() === $originalEntity->getEmail() &&
    $roundTripEntity->getAge() === $originalEntity->getAge()
        ? '✅ Yes'
        : '❌ No'
) . "\n\n";

// Example 5: Doctrine Type Usage (Conceptual)
echo "5. Doctrine Type Usage (Conceptual)\n";
echo "----------------------------------------\n";
echo "In a real Doctrine entity, you would use:\n\n";
echo "```php\n";
echo "use Doctrine\\ORM\\Mapping as ORM;\n";
echo "use event4u\\DataHelpers\\SimpleDto\\SimpleDtoDoctrineType;\n\n";
echo "#[ORM\\Entity]\n";
echo "class Product\n";
echo "{\n";
echo "    #[ORM\\Column(type: 'json')]\n";
echo "    private ?AddressDto \$address = null;\n\n";
echo "    // Or register custom type:\n";
echo "    // Type::addType('address_dto', SimpleDtoDoctrineType::class);\n";
echo "    // #[ORM\\Column(type: 'address_dto')]\n";
echo "    // private ?AddressDto \$address = null;\n";
echo "}\n";
echo "```\n\n";

echo "The Dto will be automatically serialized to JSON when saving\n";
echo "and deserialized back to Dto when loading from database.\n\n";

echo "=== Example Complete ===\n";

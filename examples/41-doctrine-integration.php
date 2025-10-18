<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\SimpleDTODoctrineTrait;

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

// DTO with Doctrine integration
class UserDTO extends SimpleDTO
{
    use SimpleDTODoctrineTrait;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?int $age = null,
        public readonly ?int $id = null,
    ) {}
}

// Example 1: Create DTO from Entity
echo "1. Create DTO from Entity (fromEntity)\n";
echo "----------------------------------------\n";

$user = new User();
$user->setId(1);
$user->setName('John Doe');
$user->setEmail('john@example.com');
$user->setAge(30);

$dto = UserDTO::fromEntity($user);

echo "Entity data:\n";
echo "  ID: {$user->getId()}\n";
echo "  Name: {$user->getName()}\n";
echo "  Email: {$user->getEmail()}\n";
echo "  Age: {$user->getAge()}\n";
echo "\n";

echo "DTO data:\n";
echo "  ID: {$dto->id}\n";
echo "  Name: {$dto->name}\n";
echo "  Email: {$dto->email}\n";
echo "  Age: {$dto->age}\n";
echo "\n\n";

// Example 2: Create Entity from DTO
echo "2. Create Entity from DTO (toEntity)\n";
echo "----------------------------------------\n";

$dto2 = UserDTO::fromArray([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'age' => 25,
]);

$entity = $dto2->toEntity(User::class);

echo "DTO data:\n";
echo "  Name: {$dto2->name}\n";
echo "  Email: {$dto2->email}\n";
echo "  Age: {$dto2->age}\n";
echo "\n";

echo "Entity data:\n";
echo "  Name: {$entity->getName()}\n";
echo "  Email: {$entity->getEmail()}\n";
echo "  Age: {$entity->getAge()}\n";
echo "\n\n";

// Example 3: Update Entity from DTO
echo "3. Update Entity from DTO\n";
echo "----------------------------------------\n";

$existingEntity = new User();
$existingEntity->setId(42);
$existingEntity->setName('Old Name');
$existingEntity->setEmail('old@example.com');
$existingEntity->setAge(40);

echo "Before update:\n";
echo "  ID: {$existingEntity->getId()}\n";
echo "  Name: {$existingEntity->getName()}\n";
echo "  Email: {$existingEntity->getEmail()}\n";
echo "  Age: {$existingEntity->getAge()}\n";
echo "\n";

$updateDto = UserDTO::fromArray([
    'name' => 'Updated Name',
    'email' => 'updated@example.com',
    'age' => 45,
]);

// Update entity properties
$existingEntity->setName($updateDto->name);
$existingEntity->setEmail($updateDto->email);
$existingEntity->setAge($updateDto->age);

echo "After update:\n";
echo "  ID: {$existingEntity->getId()}\n";
echo "  Name: {$existingEntity->getName()}\n";
echo "  Email: {$existingEntity->getEmail()}\n";
echo "  Age: {$existingEntity->getAge()}\n";
echo "\n\n";

// Example 4: Round-trip (Entity → DTO → Entity)
echo "4. Round-trip (Entity → DTO → Entity)\n";
echo "----------------------------------------\n";

$originalEntity = new User();
$originalEntity->setId(99);
$originalEntity->setName('Round Trip');
$originalEntity->setEmail('roundtrip@example.com');
$originalEntity->setAge(35);

echo "Original entity:\n";
echo "  ID: {$originalEntity->getId()}\n";
echo "  Name: {$originalEntity->getName()}\n";
echo "  Email: {$originalEntity->getEmail()}\n";
echo "  Age: {$originalEntity->getAge()}\n";
echo "\n";

$roundTripDto = UserDTO::fromEntity($originalEntity);
$roundTripEntity = $roundTripDto->toEntity(User::class);

echo "After round-trip:\n";
echo "  Name: {$roundTripEntity->getName()}\n";
echo "  Email: {$roundTripEntity->getEmail()}\n";
echo "  Age: {$roundTripEntity->getAge()}\n";
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
echo "use event4u\\DataHelpers\\SimpleDTO\\SimpleDTODoctrineType;\n\n";
echo "#[ORM\\Entity]\n";
echo "class Product\n";
echo "{\n";
echo "    #[ORM\\Column(type: 'json')]\n";
echo "    private ?AddressDTO \$address = null;\n\n";
echo "    // Or register custom type:\n";
echo "    // Type::addType('address_dto', SimpleDTODoctrineType::class);\n";
echo "    // #[ORM\\Column(type: 'address_dto')]\n";
echo "    // private ?AddressDTO \$address = null;\n";
echo "}\n";
echo "```\n\n";

echo "The DTO will be automatically serialized to JSON when saving\n";
echo "and deserialized back to DTO when loading from database.\n\n";

echo "=== Example Complete ===\n";


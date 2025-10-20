<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\SimpleDTODoctrineTrait;

/**
 * Mock Doctrine Entity for testing.
 */
class TestUserEntity
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

describe('Doctrine Integration', function(): void {
    describe('fromEntity()', function(): void {
        it('creates DTO from entity', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $entity = new TestUserEntity();
            $entity->setName('John Doe');
            $entity->setEmail('john@example.com');

            $instance = $dto::fromEntity($entity);

            expect($instance->name)->toBe('John Doe');
            expect($instance->email)->toBe('john@example.com');
        });

        it('handles entity with extra attributes', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $entity = new TestUserEntity();
            $entity->setName('John Doe');
            $entity->setEmail('john@example.com');
            $entity->setAge(30);

            $instance = $dto::fromEntity($entity);

            expect($instance->name)->toBe('John Doe');
        });

        it('handles entity with missing optional attributes', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                    public readonly ?int $age = null,
                ) {}
            };

            $entity = new TestUserEntity();
            $entity->setName('John Doe');
            $entity->setEmail('john@example.com');

            $instance = $dto::fromEntity($entity);

            expect($instance->name)->toBe('John Doe');
            expect($instance->email)->toBe('john@example.com');
            expect($instance->age)->toBeNull();
        });
    });

    describe('toEntity()', function(): void {
        it('creates entity from DTO', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
            ]);

            $entity = $instance->toEntity(TestUserEntity::class);

            expect($entity)->toBeInstanceOf(TestUserEntity::class);
            expect($entity->getName())->toBe('Jane Smith');
            expect($entity->getEmail())->toBe('jane@example.com');
        });

        it('throws exception if entity class does not exist', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'Test']);

            expect(fn(): object => $instance->toEntity('NonExistentClass'))
                ->toThrow(InvalidArgumentException::class, 'Entity class NonExistentClass does not exist');
        });
    });

    describe('Round-trip', function(): void {
        it('preserves data in round-trip', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                    public readonly ?int $age = null,
                ) {}
            };

            $originalEntity = new TestUserEntity();
            $originalEntity->setName('Round Trip');
            $originalEntity->setEmail('roundtrip@example.com');
            $originalEntity->setAge(35);

            $dtoInstance = $dto::fromEntity($originalEntity);
            $newEntity = $dtoInstance->toEntity(TestUserEntity::class);

            expect($newEntity->getName())->toBe($originalEntity->getName());
            expect($newEntity->getEmail())->toBe($originalEntity->getEmail());
            expect($newEntity->getAge())->toBe($originalEntity->getAge());
        });

        it('handles multiple round-trips', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $entity1 = new TestUserEntity();
            $entity1->setName('Test User');
            $entity1->setEmail('test@example.com');

            $dto1 = $dto::fromEntity($entity1);
            $entity2 = $dto1->toEntity(TestUserEntity::class);
            $dto2 = $dto::fromEntity($entity2);
            $entity3 = $dto2->toEntity(TestUserEntity::class);

            expect($entity3->getName())->toBe('Test User');
            expect($entity3->getEmail())->toBe('test@example.com');
        });
    });

    describe('Update Entity from DTO', function(): void {
        it('updates existing entity with DTO data', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $entity = new TestUserEntity();
            $entity->setId(42);
            $entity->setName('Old Name');
            $entity->setEmail('old@example.com');

            $updateDto = $dto::fromArray([
                'name' => 'New Name',
                'email' => 'new@example.com',
            ]);

            $entity->setName($updateDto->name);
            $entity->setEmail($updateDto->email);

            expect($entity->getId())->toBe(42);
            expect($entity->getName())->toBe('New Name');
            expect($entity->getEmail())->toBe('new@example.com');
        });
    });

    describe('Doctrine Entity with Getters/Setters', function(): void {
        it('uses EntityHelper to read entity properties via reflection', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                    public readonly ?int $age = null,
                ) {}
            };

            // Create entity with data
            $entity = new TestUserEntity();
            $entity->setName('Test User');
            $entity->setEmail('test@example.com');
            $entity->setAge(25);

            // fromEntity should use EntityHelper::toArray() which uses reflection
            $instance = $dto::fromEntity($entity);

            // Verify data was extracted correctly
            expect($instance->name)->toBe('Test User');
            expect($instance->email)->toBe('test@example.com');
            expect($instance->age)->toBe(25);
        });

        it('uses EntityHelper to set entity properties via setters', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                    public readonly ?int $age = null,
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'New User',
                'email' => 'new@example.com',
                'age' => 30,
            ]);

            // toEntity should use EntityHelper::setAttribute() which calls setters
            $entity = $instance->toEntity(TestUserEntity::class);

            // Verify setters were called correctly
            expect($entity->getName())->toBe('New User');
            expect($entity->getEmail())->toBe('new@example.com');
            expect($entity->getAge())->toBe(30);
        });

        it('handles snake_case to camelCase conversion for setters', function(): void {
            // Create a mock entity with camelCase setters
            $mockEntity = new class {
                private string $firstName = '';
                private string $lastName = '';

                public function getFirstName(): string
                {
                    return $this->firstName;
                }

                public function setFirstName(string $firstName): void
                {
                    $this->firstName = $firstName;
                }

                public function getLastName(): string
                {
                    return $this->lastName;
                }

                public function setLastName(string $lastName): void
                {
                    $this->lastName = $lastName;
                }
            };

            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $firstName = '',
                    public readonly string $lastName = '',
                ) {}
            };

            $instance = $dto::fromArray([
                'firstName' => 'John',
                'lastName' => 'Doe',
            ]);

            // toEntity should convert snake_case to camelCase for setters
            $entity = $instance->toEntity($mockEntity::class);

            expect($entity->getFirstName())->toBe('John');
            expect($entity->getLastName())->toBe('Doe');
        });
    });
})->group('doctrine');


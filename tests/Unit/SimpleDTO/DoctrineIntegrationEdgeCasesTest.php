<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\SimpleDTODoctrineTrait;

/**
 * Mock Doctrine Entity for edge case testing.
 */
class EdgeCaseTestEntity
{
    private ?int $id = null;
    private string $name = '';
    private ?string $email = null;
    private ?int $age = null;
    private bool $isActive = false;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
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

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
}

describe('Doctrine Integration Edge Cases', function(): void {
    describe('fromEntity() Edge Cases', function(): void {
        it('handles entity with null values', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                    public readonly ?int $age = null,
                ) {}
            };

            $entity = new EdgeCaseTestEntity();
            $entity->setName('John');
            $entity->setEmail(null);
            $entity->setAge(null);

            $instance = $dto::fromEntity($entity);

            expect($instance->name)->toBe('John');
            expect($instance->email)->toBeNull();
            expect($instance->age)->toBeNull();
        });

        it('handles entity with empty strings', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                ) {}
            };

            $entity = new EdgeCaseTestEntity();
            $entity->setName('');
            $entity->setEmail('');

            $instance = $dto::fromEntity($entity);

            expect($instance->name)->toBe('');
            expect($instance->email)->toBe('');
        });

        it('handles entity with boolean values', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly bool $isActive = false,
                ) {}
            };

            $entity = new EdgeCaseTestEntity();
            $entity->setIsActive(true);

            $instance = $dto::fromEntity($entity);

            expect($instance->isActive)->toBeTrue();
        });

        it('handles entity with missing optional properties', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $nonExistent = null,
                ) {}
            };

            $entity = new EdgeCaseTestEntity();
            $entity->setName('John');

            $instance = $dto::fromEntity($entity);

            expect($instance->name)->toBe('John');
            expect($instance->nonExistent)->toBeNull();
        });
    });

    describe('toEntity() Edge Cases', function(): void {
        it('handles DTO with null values', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                    public readonly ?int $age = null,
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'John',
                'email' => null,
                'age' => null,
            ]);

            $entity = $instance->toEntity(EdgeCaseTestEntity::class);

            expect($entity->getName())->toBe('John');
            expect($entity->getEmail())->toBeNull();
            expect($entity->getAge())->toBeNull();
        });

        it('handles DTO with empty strings', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => '',
                'email' => '',
            ]);

            $entity = $instance->toEntity(EdgeCaseTestEntity::class);

            expect($entity->getName())->toBe('');
            expect($entity->getEmail())->toBe('');
        });

        it('handles DTO with boolean values', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly bool $isActive = false,
                ) {}
            };

            $instance = $dto::fromArray(['isActive' => true]);

            $entity = $instance->toEntity(EdgeCaseTestEntity::class);

            expect($entity->getIsActive())->toBeTrue();
        });

        it('handles DTO with extra properties not in entity', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $extraField = '',
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'John',
                'extraField' => 'Extra',
            ]);

            // Should not throw error, just ignore extra field
            $entity = $instance->toEntity(EdgeCaseTestEntity::class);

            expect($entity->getName())->toBe('John');
        });

        it('throws exception for invalid entity class', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John']);

            expect(fn(): object => $instance->toEntity('NonExistentClass'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('Round-trip Edge Cases', function(): void {
        it('preserves null values in round-trip', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                ) {}
            };

            $entity = new EdgeCaseTestEntity();
            $entity->setName('John');
            $entity->setEmail(null);

            $dtoInstance = $dto::fromEntity($entity);
            $newEntity = $dtoInstance->toEntity(EdgeCaseTestEntity::class);

            expect($newEntity->getName())->toBe('John');
            expect($newEntity->getEmail())->toBeNull();
        });

        it('preserves empty strings in round-trip', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                ) {}
            };

            $entity = new EdgeCaseTestEntity();
            $entity->setName('');
            $entity->setEmail('');

            $dtoInstance = $dto::fromEntity($entity);
            $newEntity = $dtoInstance->toEntity(EdgeCaseTestEntity::class);

            expect($newEntity->getName())->toBe('');
            expect($newEntity->getEmail())->toBe('');
        });

        it('preserves boolean values in round-trip', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTODoctrineTrait;

                public function __construct(
                    public readonly bool $isActive = false,
                ) {}
            };

            $entity = new EdgeCaseTestEntity();
            $entity->setIsActive(true);

            $dtoInstance = $dto::fromEntity($entity);
            $newEntity = $dtoInstance->toEntity(EdgeCaseTestEntity::class);

            expect($newEntity->getIsActive())->toBeTrue();
        });
    });
})->group('doctrine');


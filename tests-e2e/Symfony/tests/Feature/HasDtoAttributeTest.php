<?php

declare(strict_types=1);

use App\Dto\UserDto;
use App\Entity\User;

describe('Symfony HasDto Attribute E2E', function(): void {
    it('creates DTO from entity using HasDto attribute', function(): void {
        $entity = new User();
        $entity->setName('John Doe'); // @phpstan-ignore method.resultUnused
        $entity->setEmail('john@example.com'); // @phpstan-ignore method.resultUnused

        // toDto() without parameter should use HasDto attribute
        /** @phpstan-ignore method.notFound */
        $dto = $entity->toDto();

        expect($dto)->toBeInstanceOf(UserDto::class);
        expect($dto->name)->toBe('John Doe');
        expect($dto->email)->toBe('john@example.com');
    });

    it('allows overriding DTO class in toDto()', function(): void {
        $entity = new User();
        $entity->setName('Override Test'); // @phpstan-ignore method.resultUnused
        $entity->setEmail('override@example.com'); // @phpstan-ignore method.resultUnused

        // Create anonymous DTO class for testing override
        $customDto = new class('', '') extends \event4u\DataHelpers\SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {
            }
        };

        /** @phpstan-ignore method.notFound */
        $dto = $entity->toDto($customDto::class);

        expect($dto)->toBeInstanceOf($customDto::class);
        expect($dto->name)->toBe('Override Test');
        expect($dto->email)->toBe('override@example.com');
    });

    it('handles entity with relationships', function(): void {
        $entity = new User();
        $entity->setName('Relationship Test'); // @phpstan-ignore method.resultUnused
        $entity->setEmail('relationship@test.com'); // @phpstan-ignore method.resultUnused

        /** @phpstan-ignore method.notFound */
        $dto = $entity->toDto();

        expect($dto)->toBeInstanceOf(UserDto::class);
        expect($dto->name)->toBe('Relationship Test');
        expect($dto->email)->toBe('relationship@test.com');
    });

    it('throws exception when no DTO class provided and no attribute', function(): void {
        // Create entity without HasDto attribute
        $entityWithoutAttribute = new class {
            use \event4u\DataHelpers\Traits\DtoMappingTrait;

            private string $name = 'Test';
            private string $email = 'test@example.com';

            public function getName(): string
            {
                return $this->name;
            }

            public function getEmail(): string
            {
                return $this->email;
            }
        };

        expect(fn() => $entityWithoutAttribute->toDto())
            ->toThrow(InvalidArgumentException::class);
    });

    it('round-trip conversion Entity -> DTO -> Entity', function(): void {
        $originalEntity = new User();
        $originalEntity->setName('Round Trip'); // @phpstan-ignore method.resultUnused
        $originalEntity->setEmail('roundtrip@test.com'); // @phpstan-ignore method.resultUnused

        // Entity -> DTO
        /** @phpstan-ignore method.notFound */
        $dto = $originalEntity->toDto();

        expect($dto)->toBeInstanceOf(UserDto::class);
        expect($dto->name)->toBe('Round Trip');
        expect($dto->email)->toBe('roundtrip@test.com');

        // DTO -> Entity
        /** @phpstan-ignore method.notFound */
        $newEntity = $dto->toEntity();

        expect($newEntity)->toBeInstanceOf(User::class);
        expect($newEntity->getName())->toBe('Round Trip');
        expect($newEntity->getEmail())->toBe('roundtrip@test.com');
    });
});


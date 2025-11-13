<?php

declare(strict_types=1);

use App\Dto\UserDto;
use App\Entity\User;

describe('Symfony HasEntity Attribute E2E', function(): void {
    it('creates entity from DTO using HasEntity attribute', function(): void {
        $dto = new UserDto(
            name: 'John Doe',
            email: 'john@example.com'
        );

        // toEntity() without parameter should use HasEntity attribute
        /** @phpstan-ignore method.notFound, class.notFound */
        $entity = $dto->toEntity();

        expect($entity)->toBeInstanceOf(User::class);
        expect($entity->getName())->toBe('John Doe');
        expect($entity->getEmail())->toBe('john@example.com');
    });

    it('allows overriding entity class in toEntity()', function(): void {
        $dto = new UserDto(
            name: 'Override Test',
            email: 'override@example.com'
        );

        // Create anonymous entity class for testing override
        $customEntity = new class {
            private string $name;
            private string $email;

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
        };

        /** @phpstan-ignore method.notFound, class.notFound */
        $entity = $dto->toEntity($customEntity::class);

        expect($entity)->toBeInstanceOf($customEntity::class);
        expect($entity->getName())->toBe('Override Test');
        expect($entity->getEmail())->toBe('override@example.com');
    });

    it('creates DTO from entity using fromEntity()', function(): void {
        $entity = new User();
        $entity->setName('Entity Test'); // @phpstan-ignore method.resultUnused
        $entity->setEmail('entity@test.com'); // @phpstan-ignore method.resultUnused

        /** @phpstan-ignore class.notFound */
        $dto = UserDto::fromEntity($entity);

        expect($dto)->toBeInstanceOf(UserDto::class);
        expect($dto->name)->toBe('Entity Test');
        expect($dto->email)->toBe('entity@test.com');
    });

    it('throws exception when no entity class provided and no attribute', function(): void {
        // Create DTO without HasEntity attribute
        $dtoWithoutAttribute = new class('Test', 'test@example.com') extends \event4u\DataHelpers\SimpleDto {
            use \event4u\DataHelpers\SimpleDto\SimpleDtoDoctrineTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {
            }
        };

        expect(fn() => $dtoWithoutAttribute->toEntity())
            ->toThrow(InvalidArgumentException::class);
    });
});


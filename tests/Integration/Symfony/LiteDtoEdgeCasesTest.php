<?php

declare(strict_types=1);

namespace Tests\Integration\Symfony;

use Tests\Utils\Entities\Employee;
use Tests\Utils\LiteDtos\Symfony\EmployeeLiteDto;

describe('Symfony LiteDto Edge Cases - toEntity()', function(): void {
    test('Edge Case B: Nullable properties - explicit null is included', function(): void {
        // DTO with email
        $dto = EmployeeLiteDto::from([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        /** @var Employee $entity */
        $entity = $dto->toEntity(Employee::class);

        expect($entity->getName())->toBe('Alice')
            ->and($entity->getEmail())->toBe('alice@example.com');
    });

    test('Edge Case C: Mapped properties work with toEntity()', function(): void {
        // Test basic DTO to Entity conversion
        $dto = EmployeeLiteDto::from([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'position' => 'Manager',
        ]);

        /** @var Employee $entity */
        $entity = $dto->toEntity(Employee::class);

        expect($entity->getName())->toBe('Bob')
            ->and($entity->getPosition())->toBe('Manager');
    });

    test('Edge Case F: Inheritance works with toEntity()', function(): void {
        // Simple test: DTO to Entity conversion works
        $dto = EmployeeLiteDto::from([
            'name' => 'John',
            'email' => 'john@example.com',
            'position' => 'Engineer',
        ]);

        /** @var Employee $entity */
        $entity = $dto->toEntity(Employee::class);

        expect($entity->getName())->toBe('John')
            ->and($entity->getEmail())->toBe('john@example.com')
            ->and($entity->getPosition())->toBe('Engineer');
    });
})->group('doctrine');

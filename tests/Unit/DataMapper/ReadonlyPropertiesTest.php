<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('DataMapper with readonly properties', function(): void {
    test('can map to class with readonly properties using class name and modifyReadOnly', function(): void {
        // Define a DTO class with readonly properties
        $dtoClass = new class(0, '') {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $source = ['id' => 123, 'name' => 'John'];

        // Pass class name (string) as target with modifyReadOnly enabled
        $result = DataMapper::source($source)
            ->target($dtoClass::class)
            ->modifyReadOnly(true)
            ->template([
                'id' => '{{ id }}',
                'name' => '{{ name }}',
            ])
            ->map()
            ->getTarget();

        /** @var object{id: int, name: string} $result */
        // Readonly properties should be set
        expect($result->id)->toBe(123);
        expect($result->name)->toBe('John');
    });

    test('skips already initialized readonly properties when modifyReadOnly is disabled', function(): void {
        // Create an object instance with readonly properties already initialized
        $dto = new class(999, 'Original') {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $source = ['id' => 123, 'name' => 'John'];

        // Pass object instance as target WITHOUT modifyReadOnly - readonly properties should be skipped
        $result = DataMapper::source($source)
            ->target($dto)
            ->modifyReadOnly(false)  // Explicitly disable (default)
            ->template([
                'id' => '{{ id }}',
                'name' => '{{ name }}',
            ])
            ->map()
            ->getTarget();

        /** @var object{id: int, name: string} $result */
        // Readonly properties that are already initialized should keep original values
        expect($result->id)->toBe(999);  // Original value
        expect($result->name)->toBe('Original');  // Original value
    });

    test('can modify already initialized readonly properties when modifyReadOnly is enabled', function(): void {
        // Create an object instance with readonly properties already initialized
        $dto = new class(999, 'Original') {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $source = ['id' => 123, 'name' => 'John'];

        // Pass object instance as target WITH modifyReadOnly - should create new instance
        $result = DataMapper::source($source)
            ->target($dto)
            ->modifyReadOnly(true)
            ->template([
                'id' => '{{ id }}',
                'name' => '{{ name }}',
            ])
            ->map()
            ->getTarget();

        /** @var object{id: int, name: string} $result */
        // Readonly properties should be modified (new instance created)
        expect($result->id)->toBe(123);
        expect($result->name)->toBe('John');

        // Original object should remain unchanged
        expect($dto->id)->toBe(999);
        expect($dto->name)->toBe('Original');
    });

    test('can map to class with mixed readonly and mutable properties', function(): void {
        // Define a DTO class with mixed properties
        $dtoClass = new class(0) {
            public function __construct(
                public readonly int $id,
            ) {}

            public string $name = '';  // Mutable property
        };

        $source = ['id' => 123, 'name' => 'John'];

        // Pass class name as target with modifyReadOnly
        $result = DataMapper::source($source)
            ->target($dtoClass::class)
            ->modifyReadOnly(true)
            ->template([
                'id' => '{{ id }}',
                'name' => '{{ name }}',
            ])
            ->map()
            ->getTarget();

        /** @var object{id: int, name: string} $result */
        // Both readonly and mutable properties should be set
        expect($result->id)->toBe(123);
        expect($result->name)->toBe('John');
    });

    test('can map to class with all readonly properties', function(): void {
        // Define a DTO class with only readonly properties
        $dtoClass = new class(0, '', false) {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
                public readonly bool $active,
            ) {}
        };

        $source = [
            'id' => 456,
            'name' => 'Jane',
            'active' => true,
        ];

        // Pass class name as target with modifyReadOnly
        $result = DataMapper::source($source)
            ->target($dtoClass::class)
            ->modifyReadOnly(true)
            ->template([
                'id' => '{{ id }}',
                'name' => '{{ name }}',
                'active' => '{{ active }}',
            ])
            ->map()
            ->getTarget();

        /** @var object{id: int, name: string, active: bool} $result */
        // All readonly properties should be set
        expect($result->id)->toBe(456);
        expect($result->name)->toBe('Jane');
        expect($result->active)->toBe(true);
    });

    test('preserves object reference when modifyReadOnly is disabled and no readonly properties would be modified', function(): void {
        // Create an object with mutable properties only
        $dto = new class() {
            public int $id = 0;
            public string $name = '';
        };

        $source = ['id' => 123, 'name' => 'John'];

        // Pass object instance as target
        $result = DataMapper::source($source)
            ->target($dto)
            ->modifyReadOnly(false)
            ->template([
                'id' => '{{ id }}',
                'name' => '{{ name }}',
            ])
            ->map()
            ->getTarget();

        /** @var object{id: int, name: string} $result */
        // Properties should be set
        expect($result->id)->toBe(123);
        expect($result->name)->toBe('John');

        // Should be the same object reference
        expect($result)->toBe($dto);
    });
});

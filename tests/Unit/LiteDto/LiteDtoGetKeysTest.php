<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Hidden;

describe('LiteDto getKeys()', function(): void {
    it('returns all property keys by default', function(): void {
        $dto = new class extends LiteDto {
            public function __construct(
                public readonly string $name = 'John',
                public readonly string $email = 'john@example.com',
                public readonly int $age = 30,
            ) {}
        };

        $keys = $dto->getKeys();

        expect($keys)->toBe(['name', 'email', 'age']);
    });

    it('includes hidden properties by default', function(): void {
        $dto = new class extends LiteDto {
            public function __construct(
                public readonly string $name = 'John',
                #[Hidden]
                public readonly string $password = 'secret',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        $keys = $dto->getKeys();

        expect($keys)->toBe(['name', 'password', 'email']);
    });

    it('excludes hidden properties when includeHiddenFromArray is false', function(): void {
        $dto = new class extends LiteDto {
            public function __construct(
                public readonly string $name = 'John',
                #[Hidden]
                public readonly string $password = 'secret',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        $keys = $dto->getKeys(includeHiddenFromArray: false);

        expect($keys)->toBe(['name', 'email']);
    });

    it('excludes hidden properties when includeHiddenFromJson is false', function(): void {
        $dto = new class extends LiteDto {
            public function __construct(
                public readonly string $name = 'John',
                #[Hidden]
                public readonly string $password = 'secret',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        $keys = $dto->getKeys(includeHiddenFromJson: false);

        expect($keys)->toBe(['name', 'email']);
    });

    it('excludes all hidden properties when both flags are false', function(): void {
        $dto = new class extends LiteDto {
            public function __construct(
                public readonly string $name = 'John',
                #[Hidden]
                public readonly string $password = 'secret',
                #[Hidden]
                public readonly string $apiKey = 'key123',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        $keys = $dto->getKeys(includeHiddenFromArray: false, includeHiddenFromJson: false);

        expect($keys)->toBe(['name', 'email']);
    });

    it('does not include internal properties from LiteDto', function(): void {
        $dto = new class extends LiteDto {
            public function __construct(
                public readonly string $name = 'John',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        // Access internal properties to ensure they exist
        $dto->toArray();

        $keys = $dto->getKeys();

        // Should not include internal properties like toArrayCache, toJsonCache
        expect($keys)->toBe(['name', 'email']);
    });

    it('works with empty dto', function(): void {
        $dto = new class extends LiteDto
        {
        };

        $keys = $dto->getKeys();

        expect($keys)->toBe([]);
    });

    it('maintains property order', function(): void {
        $dto = new class extends LiteDto {
            public function __construct(
                public readonly string $zebra = 'z',
                public readonly string $alpha = 'a',
                public readonly string $beta = 'b',
            ) {}
        };

        $keys = $dto->getKeys();

        expect($keys)->toBe(['zebra', 'alpha', 'beta']);
    });

    it('works with multiple hidden properties', function(): void {
        $dto = new class extends LiteDto {
            public function __construct(
                public readonly string $name = 'John',
                #[Hidden]
                public readonly string $password = 'secret',
                public readonly string $email = 'john@example.com',
                #[Hidden]
                public readonly string $apiKey = 'key123',
                public readonly int $age = 30,
            ) {}
        };

        // Default: includes all properties (even hidden)
        $keys = $dto->getKeys();
        expect($keys)->toBe(['name', 'password', 'email', 'apiKey', 'age']);

        // Exclude hidden properties
        $keys = $dto->getKeys(includeHiddenFromArray: false);
        expect($keys)->toBe(['name', 'email', 'age']);
    });
});

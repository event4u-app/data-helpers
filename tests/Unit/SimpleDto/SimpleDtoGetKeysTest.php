<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromJson;

describe('SimpleDto getKeys()', function(): void {
    it('returns all property keys by default', function(): void {
        $dto = new class extends SimpleDto {
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
        $dto = new class extends SimpleDto {
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

    it('includes properties hidden from array by default', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly string $name = 'John',
                #[HiddenFromArray]
                public readonly string $internalId = '123',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        $keys = $dto->getKeys();

        expect($keys)->toBe(['name', 'internalId', 'email']);
    });

    it('includes properties hidden from json by default', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly string $name = 'John',
                #[HiddenFromJson]
                public readonly string $debugInfo = 'debug',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        $keys = $dto->getKeys();

        expect($keys)->toBe(['name', 'debugInfo', 'email']);
    });

    it('excludes hidden from array when includeHiddenFromArray is false', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly string $name = 'John',
                #[HiddenFromArray]
                public readonly string $internalId = '123',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        $keys = $dto->getKeys(includeHiddenFromArray: false);

        expect($keys)->toBe(['name', 'email']);
    });

    it('excludes hidden from json when includeHiddenFromJson is false', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly string $name = 'John',
                #[HiddenFromJson]
                public readonly string $debugInfo = 'debug',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        $keys = $dto->getKeys(includeHiddenFromJson: false);

        expect($keys)->toBe(['name', 'email']);
    });

    it('excludes all hidden properties when both flags are false', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly string $name = 'John',
                #[Hidden]
                public readonly string $password = 'secret',
                #[HiddenFromArray]
                public readonly string $internalId = '123',
                #[HiddenFromJson]
                public readonly string $debugInfo = 'debug',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        $keys = $dto->getKeys(includeHiddenFromArray: false, includeHiddenFromJson: false);

        expect($keys)->toBe(['name', 'email']);
    });

    it('does not include properties from traits', function(): void {
        $dto = new class extends SimpleDto {
            protected ?array $mapperTemplate = [
                'name' => '{{ user.name }}',
            ];

            public function __construct(
                public readonly string $name = 'John',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        $keys = $dto->getKeys();

        // Should not include $mapperTemplate from trait
        expect($keys)->toBe(['name', 'email']);
    });

    it('does not include internal properties from SimpleDtoTrait', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly string $name = 'John',
                public readonly string $email = 'john@example.com',
            ) {}
        };

        // Access internal properties to ensure they exist
        $dto->toArray();

        $keys = $dto->getKeys();

        // Should not include internal properties like toArrayCache, toJsonCache, etc.
        expect($keys)->toBe(['name', 'email']);
    });

    it('works with empty dto', function(): void {
        $dto = new class extends SimpleDto
        {
        };

        $keys = $dto->getKeys();

        expect($keys)->toBe([]);
    });

    it('maintains property order', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly string $zebra = 'z',
                public readonly string $alpha = 'a',
                public readonly string $beta = 'b',
            ) {}
        };

        $keys = $dto->getKeys();

        expect($keys)->toBe(['zebra', 'alpha', 'beta']);
    });
});

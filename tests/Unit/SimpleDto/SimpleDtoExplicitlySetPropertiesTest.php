<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Map;

describe('SimpleDto Explicitly Set Properties Tests', function(): void {
    it('tracks explicitly set properties', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly ?int $id = null,
                public readonly string $name = '',
                public readonly int $count = 0,
                public readonly bool $active = false,
            ) {}
        };

        $result = $dto::from([
            'id' => 123,
            'name' => 'Test',
        ]);

        expect($result->wasPropertyExplicitlySet('id'))->toBeTrue()
            ->and($result->wasPropertyExplicitlySet('name'))->toBeTrue()
            ->and($result->wasPropertyExplicitlySet('count'))->toBeFalse()
            ->and($result->wasPropertyExplicitlySet('active'))->toBeFalse();
    });

    it('tracks explicitly set properties with null values', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly ?int $id = null,
                public readonly ?string $name = null,
            ) {}
        };

        $result = $dto::from([
            'id' => null,
            'name' => 'Test',
        ]);

        expect($result->wasPropertyExplicitlySet('id'))->toBeTrue()
            ->and($result->wasPropertyExplicitlySet('name'))->toBeTrue()
            ->and($result->id)->toBeNull()
            ->and($result->name)->toBe('Test');
    });

    it('returns only explicitly set properties in toArrayOnlyExplicitlySet #version1', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly ?int $id = null,
                public readonly string $name = '',
                public readonly int $count = 0,
                public readonly bool $active = false,
            ) {}
        };

        $result = $dto::from([
            'id' => 123,
            'name' => 'Test',
        ]);

        $explicitArray = $result->toArrayOnlyExplicitlySet();

        expect($explicitArray)->toBe([
            'id' => 123,
            'name' => 'Test',
        ]);
    });

    it('returns only explicitly set properties in toArrayOnlyExplicitlySet #version2', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly ?int $id = null,
                public string $name = '',
                public int $count = 0,
                public bool $active = false,
            ) {}
        };

        $result = $dto::from([
            'id' => 123,
        ]);
        $result->name = 'Test';

        $explicitArray = $result->toArrayOnlyExplicitlySet();

        expect($explicitArray)->toBe([
            'id' => 123,
            'name' => 'Test',
        ]);
    });

    it('returns only explicitly set properties in toArrayOnlyExplicitlySet #version3', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly ?int $id = null,
                public string $name = 'Test',
                public int $count = 0,
                public bool $active = false,
            ) {}
        };

        $result = $dto::from([
            'id' => 123,
        ]);
        $result->name = 'Tester';

        $explicitArray = $result->toArrayOnlyExplicitlySet();

        expect($explicitArray)->toBe([
            'id' => 123,
            'name' => 'Tester',
        ]);
    });

    it('returns only explicitly set properties in toArrayOnlyExplicitlySet #version4', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly ?int $id = null,
                public string $name = 'Test',
                public int $count = 0,
                public bool $active = false,
            ) {}
        };

        $result = $dto::from([
            'id' => 123,
            'name' => 'Tester',
        ]);

        $explicitArray = $result->toArrayOnlyExplicitlySet();

        expect($explicitArray)->toBe([
            'id' => 123,
            'name' => 'Tester',
        ]);
    });

    it('works with mapped properties', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                #[Map('id_position')]
                public readonly ?int $id = null,
                #[Map('pos_care')]
                public readonly int $care = 0,
                public readonly string $name = '',
            ) {}
        };

        $result = $dto::from([
            'id_position' => 456,
        ]);

        // Check that the property 'id' was explicitly set (not the mapped name)
        expect($result->wasPropertyExplicitlySet('id'))->toBeTrue()
            ->and($result->wasPropertyExplicitlySet('care'))->toBeFalse()
            ->and($result->wasPropertyExplicitlySet('name'))->toBeFalse();
    });

    it('handles all properties explicitly set', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly int $a = 0,
                public readonly int $b = 0,
                public readonly int $c = 0,
            ) {}
        };

        $result = $dto::from([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);

        expect($result->wasPropertyExplicitlySet('a'))->toBeTrue()
            ->and($result->wasPropertyExplicitlySet('b'))->toBeTrue()
            ->and($result->wasPropertyExplicitlySet('c'))->toBeTrue();

        $explicitArray = $result->toArrayOnlyExplicitlySet();
        expect($explicitArray)->toBe([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);
    });

    it('handles no properties explicitly set', function(): void {
        $dto = new class extends SimpleDto {
            public function __construct(
                public readonly int $a = 0,
                public readonly int $b = 0,
            ) {}
        };

        $result = $dto::from([]);

        expect($result->wasPropertyExplicitlySet('a'))->toBeFalse()
            ->and($result->wasPropertyExplicitlySet('b'))->toBeFalse();

        $explicitArray = $result->toArrayOnlyExplicitlySet();
        expect($explicitArray)->toBe([]);
    });
});

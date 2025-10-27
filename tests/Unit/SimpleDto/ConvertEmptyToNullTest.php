<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

describe('ConvertEmptyToNull Attribute', function () {
    it('converts empty string to null on property level', function () {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?string $bio = null,
                public readonly ?string $name = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'bio' => '',
            'name' => 'test',
        ]);

        expect($dto->bio)->toBeNull();
        expect($dto->name)->toBe('test');
    });

    it('converts empty array to null on property level', function () {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?array $tags = null,
                public readonly ?array $categories = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'tags' => [],
            'categories' => ['tag1'],
        ]);

        expect($dto->tags)->toBeNull();
        expect($dto->categories)->toBe(['tag1']);
    });

    it('does not convert boolean false to null', function () {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?bool $active = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'active' => false,
        ]);

        expect($dto->active)->toBe(false);
    });

    it('converts empty string to null when using fromArray', function () {
        $dto = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?string $bio = null,
                #[ConvertEmptyToNull]
                public readonly ?array $tags = null,
            ) {}
        };

        $instance = $dto::fromArray([
            'bio' => '',
            'tags' => [],
        ]);

        expect($instance->bio)->toBeNull();
        expect($instance->tags)->toBeNull();
    });

    it('keeps non-empty values unchanged', function () {
        $dto = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?string $bio = null,
                #[ConvertEmptyToNull]
                public readonly ?array $tags = null,
            ) {}
        };

        $instance = $dto::fromArray([
            'bio' => 'Hello World',
            'tags' => ['php', 'laravel'],
        ]);

        expect($instance->bio)->toBe('Hello World');
        expect($instance->tags)->toBe(['php', 'laravel']);
    });

    it('works on class level', function () {
        $dtoClass = new #[ConvertEmptyToNull] class () extends SimpleDto {
            public function __construct(
                public readonly ?string $bio = null,
                public readonly ?array $tags = null,
                public readonly ?string $name = null,
            ) {}
        };

        $instance = $dtoClass::fromArray([
            'bio' => '',
            'tags' => [],
            'name' => 'John',
        ]);

        expect($instance->bio)->toBeNull();
        expect($instance->tags)->toBeNull();
        expect($instance->name)->toBe('John');
    });

    it('does not convert zero string to null by default', function () {
        $dto = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?string $value = null,
            ) {}
        };

        $instance = $dto::fromArray([
            'value' => '0',
        ]);

        expect($instance->value)->toBe('0');
    });

    it('does not convert integer zero to null by default', function () {
        $dto = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?int $count = null,
            ) {}
        };

        $instance = $dto::fromArray([
            'count' => 0,
        ]);

        expect($instance->count)->toBe(0);
    });

    it('converts zero string to null when enabled', function () {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull(convertStringZero: true)]
                public readonly ?string $value = null,
            ) {}
        };

        $instance = $dtoClass::fromArray([
            'value' => '0',
        ]);

        expect($instance->value)->toBeNull();
    });

    it('converts integer zero to null when enabled', function () {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull(convertZero: true)]
                public readonly ?int $count = null,
            ) {}
        };

        $instance = $dtoClass::fromArray([
            'count' => 0,
        ]);

        expect($instance->count)->toBeNull();
    });

    it('does not convert non-zero and non-empty values', function () {
        $dto = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?int $count = null,
                #[ConvertEmptyToNull]
                public readonly ?string $value = null,
            ) {}
        };

        $instance = $dto::fromArray([
            'count' => 1,
            'value' => '1',
        ]);

        expect($instance->count)->toBe(1);
        expect($instance->value)->toBe('1');
    });

    it('converts both zero types when both options enabled', function () {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull(convertZero: true, convertStringZero: true)]
                public readonly ?int $count = null,
                #[ConvertEmptyToNull(convertZero: true, convertStringZero: true)]
                public readonly ?string $value = null,
            ) {}
        };

        $instance = $dtoClass::fromArray([
            'count' => 0,
            'value' => '0',
        ]);

        expect($instance->count)->toBeNull();
        expect($instance->value)->toBeNull();
    });

    it('handles null values correctly', function () {
        $dto = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?string $bio = null,
            ) {}
        };

        $instance = $dto::fromArray([
            'bio' => null,
        ]);

        expect($instance->bio)->toBeNull();
    });

    it('works with toArray output', function () {
        $dto = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?string $bio = null,
                #[ConvertEmptyToNull]
                public readonly ?array $tags = null,
            ) {}
        };

        $instance = $dto::fromArray([
            'bio' => '',
            'tags' => [],
        ]);

        $array = $instance->toArray();

        expect($array['bio'])->toBeNull();
        expect($array['tags'])->toBeNull();
    });
});


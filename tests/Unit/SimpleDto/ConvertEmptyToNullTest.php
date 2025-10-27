<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\SimpleDto\Casts\ConvertEmptyToNullCast;

describe('ConvertEmptyToNull Attribute', function(): void {
    it('converts empty string to null on property level', function(): void {
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

    it('converts empty array to null on property level', function(): void {
        $dtoClass = new class () extends SimpleDto {
            /**
             * @param array<string>|null $tags
             * @param array<string>|null $categories
             */
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

    it('does not convert boolean false to null', function(): void {
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

    it('converts empty string to null when using fromArray', function(): void {
        $dto = new class () extends SimpleDto {
            /** @param array<string>|null $tags */
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

    it('keeps non-empty values unchanged', function(): void {
        $dto = new class () extends SimpleDto {
            /** @param array<string>|null $tags */
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

    it('works on class level', function(): void {
        $dtoClass = new #[ConvertEmptyToNull] class () extends SimpleDto {
            /** @param array<string>|null $tags */
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

    it('does not convert zero string to null by default', function(): void {
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

    it('does not convert integer zero to null by default', function(): void {
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

    it('converts zero string to null when enabled', function(): void {
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

    it('converts integer zero to null when enabled', function(): void {
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

    it('does not convert non-zero and non-empty values', function(): void {
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

    it('converts both zero types when both options enabled', function(): void {
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

    it('handles null values correctly', function(): void {
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

    it('works with toArray output', function(): void {
        $dto = new class () extends SimpleDto {
            /** @param array<string>|null $tags */
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

    it('converts false to null when convertFalse is enabled', function(): void {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull(convertFalse: true)]
                public readonly ?bool $active = null,
                #[ConvertEmptyToNull]
                public readonly ?bool $enabled = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'active' => false,
            'enabled' => false,
        ]);

        expect($dto->active)->toBeNull();
        expect($dto->enabled)->toBe(false);
    });

    it('converts false to null on class level when convertFalse is enabled', function(): void {
        $dtoClass = new #[ConvertEmptyToNull(convertFalse: true)] class () extends SimpleDto {
            public function __construct(
                public readonly ?bool $active = null,
                public readonly ?bool $enabled = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'active' => false,
            'enabled' => false,
        ]);

        expect($dto->active)->toBeNull();
        expect($dto->enabled)->toBeNull();
    });

    it('converts false to null with cast when convertFalse is enabled', function(): void {
        $dtoClass = new class () extends SimpleDto {
            protected function casts(): array
            {
                return [
                    'active' => ConvertEmptyToNullCast::class . ':convertFalse=1',
                ];
            }

            public function __construct(
                public readonly ?bool $active = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'active' => false,
        ]);

        expect($dto->active)->toBeNull();
    });

    it('keeps true unchanged when convertFalse is enabled', function(): void {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull(convertFalse: true)]
                public readonly ?bool $active = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'active' => true,
        ]);

        expect($dto->active)->toBe(true);
    });

    it('combines convertZero, convertStringZero and convertFalse', function(): void {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull(convertZero: true, convertStringZero: true, convertFalse: true)]
                public readonly mixed $value = null,
            ) {}
        };

        $dto1 = $dtoClass::fromArray(['value' => 0]);
        $dto2 = $dtoClass::fromArray(['value' => '0']);
        $dto3 = $dtoClass::fromArray(['value' => false]);
        $dto4 = $dtoClass::fromArray(['value' => '']);

        expect($dto1->value)->toBeNull();
        expect($dto2->value)->toBeNull();
        expect($dto3->value)->toBeNull();
        expect($dto4->value)->toBeNull();
    });

    it('converts float zero to null when convertZero is enabled', function(): void {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull(convertZero: true)]
                public readonly ?float $amount = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'amount' => 0.0,
        ]);

        expect($dto->amount)->toBeNull();
    });

    it('does not convert float zero by default', function(): void {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?float $amount = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'amount' => 0.0,
        ]);

        expect($dto->amount)->toBe(0.0);
    });

    it('does not convert string "false" or "true"', function(): void {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull(convertFalse: true)]
                public readonly ?string $falseString = null,
                #[ConvertEmptyToNull(convertFalse: true)]
                public readonly ?string $trueString = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'falseString' => 'false',
            'trueString' => 'true',
        ]);

        expect($dto->falseString)->toBe('false');
        expect($dto->trueString)->toBe('true');
    });

    it('does not convert array with null values', function(): void {
        $dtoClass = new class () extends SimpleDto {
            /** @param array<mixed>|null $items */
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?array $items = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'items' => [null],
        ]);

        expect($dto->items)->toBe([null]);
    });

    it('does not convert whitespace-only strings', function(): void {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull]
                public readonly ?string $value = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'value' => '   ',
        ]);

        expect($dto->value)->toBe('   ');
    });

    it('does not convert numeric strings other than "0"', function(): void {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull(convertStringZero: true)]
                public readonly ?string $value1 = null,
                #[ConvertEmptyToNull(convertStringZero: true)]
                public readonly ?string $value2 = null,
                #[ConvertEmptyToNull(convertStringZero: true)]
                public readonly ?string $value3 = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'value1' => '1',
            'value2' => '42',
            'value3' => '-1',
        ]);

        expect($dto->value1)->toBe('1');
        expect($dto->value2)->toBe('42');
        expect($dto->value3)->toBe('-1');
    });

    it('does not convert negative numbers', function(): void {
        $dtoClass = new class () extends SimpleDto {
            public function __construct(
                #[ConvertEmptyToNull(convertZero: true)]
                public readonly ?int $value = null,
            ) {}
        };

        $dto = $dtoClass::fromArray([
            'value' => -1,
        ]);

        expect($dto->value)->toBe(-1);
    });
});

<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCallback;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenEquals;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenFalse;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenIn;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenNotNull;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenNull;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenTrue;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenValue;

// Test DTOs
class ConditionalPropsTestDTO1 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

class ConditionalPropsTestDTO2 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        #[WhenValue('price', '>', 100)]
        public readonly ?string $badge = null,
    ) {}
}

class ConditionalPropsTestDTO3 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        #[WhenValue('price', '>=', 100)]
        public readonly ?string $badge = null,
    ) {}
}

class ConditionalPropsTestDTO4 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenNull]
        public readonly ?string $deletedAt = null,
    ) {}
}

class ConditionalPropsTestDTO5 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenNotNull]
        public readonly ?string $phone = null,
    ) {}
}

class ConditionalPropsTestDTO6 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenTrue]
        public readonly bool $isPremium = false,
    ) {}
}

class ConditionalPropsTestDTO7 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[WhenFalse]
        public readonly bool $isDisabled = false,
    ) {}
}

class ConditionalPropsTestDTO8 extends SimpleDTO
{
    public function __construct(
        #[WhenEquals('completed')]
        public readonly string $status = 'pending',
    ) {}
}

class ConditionalPropsTestDTO9 extends SimpleDTO
{
    public function __construct(
        #[WhenIn(['completed', 'shipped'])]
        public readonly string $status = 'pending',
    ) {}
}

// Test callback function for WhenCallback tests
function isAdult(mixed $dto): bool
{
    // @phpstan-ignore property.nonObject
    return 18 <= $dto->age;
}

describe('Conditional Properties', function(): void {
    describe('WhenCallback Attribute', function(): void {
        it('includes property when callback returns true', function(): void {
            $attr = new WhenCallback('Tests\Unit\isAdult');
            $dto = new ConditionalPropsTestDTO1('John', 25);

            $shouldInclude = $attr->shouldInclude('Adult content', $dto);
            expect($shouldInclude)->toBeTrue();
        });

        it('excludes property when callback returns false', function(): void {
            $attr = new WhenCallback('Tests\Unit\isAdult');
            $dto = new ConditionalPropsTestDTO1('Jane', 16);

            $shouldInclude = $attr->shouldInclude('Adult content', $dto);
            expect($shouldInclude)->toBeFalse();
        });
    });

    describe('WhenValue Attribute', function(): void {
        it('includes property when value comparison is true', function(): void {
            $dto = new ConditionalPropsTestDTO2('Product', 150.0, 'Premium');

            $array = $dto->toArray();
            expect($array)->toHaveKey('badge')
                ->and($array['badge'])->toBe('Premium');
        });

        it('excludes property when value comparison is false', function(): void {
            $dto = new ConditionalPropsTestDTO2('Product', 50.0, 'Premium');

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('badge');
        });

        it('supports different comparison operators', function(): void {
            $dto = new ConditionalPropsTestDTO3('Product', 100.0, 'Exact');

            $array = $dto->toArray();
            expect($array)->toHaveKey('badge');
        });
    });

    describe('WhenNull Attribute', function(): void {
        it('includes property when value is null', function(): void {
            $dto = new ConditionalPropsTestDTO4('User', null);

            $array = $dto->toArray();
            expect($array)->toHaveKey('deletedAt')
                ->and($array['deletedAt'])->toBeNull();
        });

        it('excludes property when value is not null', function(): void {
            $dto = new ConditionalPropsTestDTO4('User', '2024-01-01');

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('deletedAt');
        });
    });

    describe('WhenNotNull Attribute', function(): void {
        it('includes property when value is not null', function(): void {
            $dto = new ConditionalPropsTestDTO5('User', '555-1234');

            $array = $dto->toArray();
            expect($array)->toHaveKey('phone')
                ->and($array['phone'])->toBe('555-1234');
        });

        it('excludes property when value is null', function(): void {
            $dto = new ConditionalPropsTestDTO5('User', null);

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('phone');
        });
    });

    describe('WhenTrue Attribute', function(): void {
        it('includes property when value is true', function(): void {
            $dto = new ConditionalPropsTestDTO6('Feature', true);

            $array = $dto->toArray();
            expect($array)->toHaveKey('isPremium')
                ->and($array['isPremium'])->toBeTrue();
        });

        it('excludes property when value is false', function(): void {
            $dto = new ConditionalPropsTestDTO6('Feature', false);

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('isPremium');
        });
    });

    describe('WhenFalse Attribute', function(): void {
        it('includes property when value is false', function(): void {
            $dto = new ConditionalPropsTestDTO7('Feature', false);

            $array = $dto->toArray();
            expect($array)->toHaveKey('isDisabled')
                ->and($array['isDisabled'])->toBeFalse();
        });

        it('excludes property when value is true', function(): void {
            $dto = new ConditionalPropsTestDTO7('Feature', true);

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('isDisabled');
        });
    });

    describe('WhenEquals Attribute', function(): void {
        it('includes property when value equals target (strict)', function(): void {
            $dto = new ConditionalPropsTestDTO8('completed');

            $array = $dto->toArray();
            expect($array)->toHaveKey('status')
                ->and($array['status'])->toBe('completed');
        });

        it('excludes property when value does not equal target', function(): void {
            $dto = new ConditionalPropsTestDTO8('pending');

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('status');
        });
    });

    describe('WhenIn Attribute', function(): void {
        it('includes property when value is in list', function(): void {
            $dto = new ConditionalPropsTestDTO9('completed');

            $array = $dto->toArray();
            expect($array)->toHaveKey('status')
                ->and($array['status'])->toBe('completed');
        });

        it('excludes property when value is not in list', function(): void {
            $dto = new ConditionalPropsTestDTO9('pending');

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('status');
        });
    });
});


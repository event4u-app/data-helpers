<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCallback;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenValue;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenNull;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenNotNull;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenTrue;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenFalse;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenEquals;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenIn;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenInstanceOf;

// Test callback function for WhenCallback tests
function isAdult($dto): bool
{
    return $dto->age >= 18;
}

describe('Conditional Properties', function () {
    describe('WhenCallback Attribute', function () {
        it('includes property when callback returns true', function () {
            $attr = new WhenCallback('Tests\Unit\isAdult');

            $dto = new class('John', 25) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                    public readonly int $age,
                ) {}
            };

            $shouldInclude = $attr->shouldInclude('Adult content', $dto);
            expect($shouldInclude)->toBeTrue();
        });

        it('excludes property when callback returns false', function () {
            $attr = new WhenCallback('Tests\Unit\isAdult');

            $dto = new class('Jane', 16) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                    public readonly int $age,
                ) {}
            };

            $shouldInclude = $attr->shouldInclude('Adult content', $dto);
            expect($shouldInclude)->toBeFalse();
        });
    });

    describe('WhenValue Attribute', function () {
        it('includes property when value comparison is true', function () {
            $dto = new class('Product', 150.0, 'Premium') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                    public readonly float $price,

                    #[WhenValue('price', '>', 100)]
                    public readonly ?string $badge = null,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('badge')
                ->and($array['badge'])->toBe('Premium');
        });

        it('excludes property when value comparison is false', function () {
            $dto = new class('Product', 50.0, 'Premium') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                    public readonly float $price,

                    #[WhenValue('price', '>', 100)]
                    public readonly ?string $badge = null,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('badge');
        });

        it('supports different comparison operators', function () {
            $dto = new class('Product', 100.0, 'Exact') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                    public readonly float $price,

                    #[WhenValue('price', '>=', 100)]
                    public readonly ?string $badge = null,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('badge');
        });
    });

    describe('WhenNull Attribute', function () {
        it('includes property when value is null', function () {
            $dto = new class('User', null) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenNull]
                    public readonly ?string $deletedAt = null,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('deletedAt')
                ->and($array['deletedAt'])->toBeNull();
        });

        it('excludes property when value is not null', function () {
            $dto = new class('User', '2024-01-01') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenNull]
                    public readonly ?string $deletedAt = null,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('deletedAt');
        });
    });

    describe('WhenNotNull Attribute', function () {
        it('includes property when value is not null', function () {
            $dto = new class('User', '555-1234') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenNotNull]
                    public readonly ?string $phone = null,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('phone')
                ->and($array['phone'])->toBe('555-1234');
        });

        it('excludes property when value is null', function () {
            $dto = new class('User', null) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenNotNull]
                    public readonly ?string $phone = null,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('phone');
        });
    });

    describe('WhenTrue Attribute', function () {
        it('includes property when value is true', function () {
            $dto = new class('Feature', true) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenTrue]
                    public readonly bool $isPremium = false,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('isPremium')
                ->and($array['isPremium'])->toBeTrue();
        });

        it('excludes property when value is false', function () {
            $dto = new class('Feature', false) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenTrue]
                    public readonly bool $isPremium = false,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('isPremium');
        });
    });

    describe('WhenFalse Attribute', function () {
        it('includes property when value is false', function () {
            $dto = new class('Feature', false) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenFalse]
                    public readonly bool $isDisabled = false,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('isDisabled')
                ->and($array['isDisabled'])->toBeFalse();
        });

        it('excludes property when value is true', function () {
            $dto = new class('Feature', true) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenFalse]
                    public readonly bool $isDisabled = false,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('isDisabled');
        });
    });

    describe('WhenEquals Attribute', function () {
        it('includes property when value equals target (strict)', function () {
            $dto = new class('completed') {
                use SimpleDTOTrait;

                public function __construct(
                    #[WhenEquals('completed')]
                    public readonly string $status = 'pending',
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('status')
                ->and($array['status'])->toBe('completed');
        });

        it('excludes property when value does not equal target', function () {
            $dto = new class('pending') {
                use SimpleDTOTrait;

                public function __construct(
                    #[WhenEquals('completed')]
                    public readonly string $status = 'pending',
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('status');
        });
    });

    describe('WhenIn Attribute', function () {
        it('includes property when value is in list', function () {
            $dto = new class('completed') {
                use SimpleDTOTrait;

                public function __construct(
                    #[WhenIn(['completed', 'shipped'])]
                    public readonly string $status = 'pending',
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('status')
                ->and($array['status'])->toBe('completed');
        });

        it('excludes property when value is not in list', function () {
            $dto = new class('pending') {
                use SimpleDTOTrait;

                public function __construct(
                    #[WhenIn(['completed', 'shipped'])]
                    public readonly string $status = 'pending',
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('status');
        });
    });
});


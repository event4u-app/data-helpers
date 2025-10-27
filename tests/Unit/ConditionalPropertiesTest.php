<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\WhenCallback;
use event4u\DataHelpers\SimpleDto\Attributes\WhenEquals;
use event4u\DataHelpers\SimpleDto\Attributes\WhenFalse;
use event4u\DataHelpers\SimpleDto\Attributes\WhenIn;
use event4u\DataHelpers\SimpleDto\Attributes\WhenNotNull;
use event4u\DataHelpers\SimpleDto\Attributes\WhenNull;
use event4u\DataHelpers\SimpleDto\Attributes\WhenTrue;
use event4u\DataHelpers\SimpleDto\Attributes\WhenValue;
use event4u\DataHelpers\SimpleDto\Enums\ComparisonOperator;

// Test Dtos
class ConditionalPropsTestDto1 extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}

    /** @param array<string, mixed> $context */
    public static function checkAge(object $dto, mixed $value, array $context, int $minAge): bool
    {
        /** @phpstan-ignore-next-line unknown */
        return $dto->age >= $minAge;
    }

    /** @param array<string, mixed> $context */
    public static function hasPermission(object $dto, mixed $value, array $context, string $permission): bool
    {
        return in_array($permission, $context['permissions'] ?? []);
    }

    /** @param array<string, mixed> $context */
    public static function checkRole(
        object $dto,
        mixed $value,
        array $context,
        string $role,
        bool $strict = false
    ): bool
    {
        if ($strict) {
            return ($context['role'] ?? null) === $role;
        }

        return in_array($role, $context['roles'] ?? []);
    }
}

class ConditionalPropsTestDto2 extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        #[WhenValue('price', '>', 100)]
        public readonly ?string $badge = null,
    ) {}
}

class ConditionalPropsTestDto3 extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        #[WhenValue('price', '>=', 100)]
        public readonly ?string $badge = null,
    ) {}
}

class ConditionalPropsTestDto4 extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[WhenNull]
        public readonly ?string $deletedAt = null,
    ) {}
}

class ConditionalPropsTestDto5 extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[WhenNotNull]
        public readonly ?string $phone = null,
    ) {}
}

class ConditionalPropsTestDto6 extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[WhenTrue]
        public readonly bool $isPremium = false,
    ) {}
}

class ConditionalPropsTestDto7 extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[WhenFalse]
        public readonly bool $isDisabled = false,
    ) {}
}

class ConditionalPropsTestDto8 extends SimpleDto
{
    public function __construct(
        #[WhenEquals('completed')]
        public readonly string $status = 'pending',
    ) {}
}

class ConditionalPropsTestDto9 extends SimpleDto
{
    public function __construct(
        #[WhenIn(['completed', 'shipped'])]
        public readonly string $status = 'pending',
    ) {}
}

// Test callback functions for WhenCallback tests
function isAdult(object $dto): bool
{
    /** @phpstan-ignore-next-line unknown */
    return 18 <= $dto->age;
}

/**
 * @param array<string, mixed> $context
 */
function isAdultWithParams(object $dto, mixed $value, array $context, int $minAge = 18): bool
{
    /** @phpstan-ignore-next-line unknown */
    return $dto->age >= $minAge;
}

/**
 * @param array<string, mixed> $context
 */
function checkPermission(object $dto, mixed $value, array $context, string $permission): bool
{
    return in_array($permission, $context['permissions'] ?? []);
}

/**
 * @param array<string, mixed> $context
 */
function checkRole(object $dto, mixed $value, array $context, string $role, bool $strict = false): bool
{
    if ($strict) {
        return ($context['role'] ?? null) === $role;
    }

    return in_array($role, $context['roles'] ?? []);
}

describe('Conditional Properties', function(): void {
    describe('WhenCallback Attribute', function(): void {
        it('works with global function reference', function(): void {
            $attr = new WhenCallback('Tests\Unit\isAdult');
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('Adult content', $dto))->toBeTrue();

            $dto2 = new ConditionalPropsTestDto1('Jane', 16);
            expect($attr->shouldInclude('Adult content', $dto2))->toBeFalse();
        });

        it('works with static method reference using static::', function(): void {
            $attr = new WhenCallback('static::checkAge', [21]);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto))->toBeTrue();

            $dto2 = new ConditionalPropsTestDto1('Jane', 18);
            expect($attr->shouldInclude('data', $dto2))->toBeFalse();
        });

        it('works with positional parameters', function(): void {
            $attr = new WhenCallback('Tests\Unit\isAdultWithParams', [21]);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto))->toBeTrue();

            $dto2 = new ConditionalPropsTestDto1('Jane', 18);
            expect($attr->shouldInclude('data', $dto2))->toBeFalse();
        });

        it('works with named parameters', function(): void {
            $attr = new WhenCallback('Tests\Unit\isAdultWithParams', ['minAge' => 21]);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto))->toBeTrue();

            $dto2 = new ConditionalPropsTestDto1('Jane', 18);
            expect($attr->shouldInclude('data', $dto2))->toBeFalse();
        });

        it('works with context-based callback and positional parameters', function(): void {
            $attr = new WhenCallback('Tests\Unit\checkPermission', ['admin']);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto, ['permissions' => ['admin', 'editor']]))->toBeTrue();
            expect($attr->shouldInclude('data', $dto, ['permissions' => ['editor']]))->toBeFalse();
        });

        it('works with context-based callback and named parameters', function(): void {
            $attr = new WhenCallback('Tests\Unit\checkRole', ['role' => 'admin', 'strict' => true]);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto, ['role' => 'admin']))->toBeTrue();
            expect($attr->shouldInclude('data', $dto, ['role' => 'editor']))->toBeFalse();
        });

        it('works with static method and positional parameters', function(): void {
            $attr = new WhenCallback('static::hasPermission', ['admin']);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto, ['permissions' => ['admin']]))->toBeTrue();
            expect($attr->shouldInclude('data', $dto, ['permissions' => ['editor']]))->toBeFalse();
        });

        it('works with static method and named parameters', function(): void {
            $attr = new WhenCallback('static::checkRole', ['role' => 'admin', 'strict' => true]);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto, ['role' => 'admin']))->toBeTrue();
            expect($attr->shouldInclude('data', $dto, ['role' => 'editor']))->toBeFalse();
        });

        it('works with fully qualified class name', function(): void {
            $attr = new WhenCallback('Tests\Unit\ConditionalPropsTestDto1::checkAge', [21]);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto))->toBeTrue();

            $dto2 = new ConditionalPropsTestDto1('Jane', 18);
            expect($attr->shouldInclude('data', $dto2))->toBeFalse();
        });

        it('returns false when callback function does not exist', function(): void {
            $attr = new WhenCallback('not_a_function');
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto))->toBeFalse();
        });

        it('returns false when static method does not exist', function(): void {
            $attr = new WhenCallback('static::nonExistentMethod');
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto))->toBeFalse();
        });

        it('supports legacy closure callbacks', function(): void {
            $attr = new WhenCallback(fn($dto): bool => 18 <= $dto->age);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto))->toBeTrue();

            $dto2 = new ConditionalPropsTestDto1('Jane', 16);
            expect($attr->shouldInclude('data', $dto2))->toBeFalse();
        });

        it('supports legacy invokable class callbacks', function(): void {
            $invokable = new class {
                public function __invoke(object $dto): bool
                {
                    /** @phpstan-ignore-next-line unknown */
                    return 18 <= $dto->age;
                }
            };

            $attr = new WhenCallback($invokable);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto))->toBeTrue();

            $dto2 = new ConditionalPropsTestDto1('Jane', 16);
            expect($attr->shouldInclude('data', $dto2))->toBeFalse();
        });

        it('supports legacy array callable callbacks', function(): void {
            $callable = [new class {
                public function check(object $dto): bool
                {
                    /** @phpstan-ignore-next-line unknown */
                    return 18 <= $dto->age;
                }
            }, 'check'];

            $attr = new WhenCallback($callable);
            $dto = new ConditionalPropsTestDto1('John', 25);

            expect($attr->shouldInclude('data', $dto))->toBeTrue();

            $dto2 = new ConditionalPropsTestDto1('Jane', 16);
            expect($attr->shouldInclude('data', $dto2))->toBeFalse();
        });

        it('casts callback result to boolean', function(): void {
            // Test that non-boolean return values are cast to bool
            $attr = new WhenCallback(fn($dto) => $dto->age); // Returns int
            $dto1 = new ConditionalPropsTestDto1('John', 25);
            $dto2 = new ConditionalPropsTestDto1('Baby', 0);

            expect($attr->shouldInclude('data', $dto1))->toBeTrue(); // 25 -> true
            expect($attr->shouldInclude('data', $dto2))->toBeFalse(); // 0 -> false
        });

        it('documents recommended usage in attributes', function(): void {
            // PHP does not allow closures as attribute arguments at parse time
            // This is a PHP language limitation, not a library limitation
            //
            // ❌ This will NOT work:
            // #[WhenCallback(fn($dto) => $dto->age >= 18)]
            // public readonly ?string $adultContent = null;
            //
            // Error: "Constant expression contains invalid operations"
            //
            // ✅ Recommended approach - Use string reference with parameters:
            // 1. Global function:
            //    #[WhenCallback('isAdult')]
            //    public readonly ?string $adultContent = null;
            //
            // 2. Global function with parameters:
            //    #[WhenCallback('isAdultWithParams', [21])]
            //    public readonly ?string $adultContent = null;
            //
            // 3. Static method with 'static::':
            //    #[WhenCallback('static::checkAge', [21])]
            //    public readonly ?string $adultContent = null;
            //
            // 4. Static method with named parameters:
            //    #[WhenCallback('static::checkRole', ['role' => 'admin', 'strict' => true])]
            //    public readonly ?string $adminContent = null;
            //
            // 5. Fully qualified class name:
            //    #[WhenCallback('App\Services\UserService::canAccess', ['resource' => 'admin'])]
            //    public readonly ?string $adminContent = null;

            // This test documents the recommended usage patterns
            expect(true)->toBeTrue();
        });
    });

    describe('WhenValue Attribute', function(): void {
        it('includes property when value comparison is true', function(): void {
            $dto = new ConditionalPropsTestDto2('Product', 150.0, 'Premium');

            $array = $dto->toArray();
            expect($array)->toHaveKey('badge')
                ->and($array['badge'])->toBe('Premium');
        });

        it('excludes property when value comparison is false', function(): void {
            $dto = new ConditionalPropsTestDto2('Product', 50.0, 'Premium');

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('badge');
        });

        it('supports different comparison operators', function(): void {
            $dto = new ConditionalPropsTestDto3('Product', 100.0, 'Exact');

            $array = $dto->toArray();
            expect($array)->toHaveKey('badge');
        });

        it('supports ComparisonOperator enum', function(): void {
            $dto = new class('Product', 150.0, 'Premium') extends SimpleDto {
                public function __construct(
                    public readonly string $name,
                    public readonly float $price,
                    #[WhenValue('price', ComparisonOperator::GreaterThan, 100)]
                    public readonly ?string $badge = null,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('badge')
                ->and($array['badge'])->toBe('Premium');
        });

        it('supports string operator for backward compatibility', function(): void {
            $dto = new class('Product', 150.0, 'Premium') extends SimpleDto {
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

        it('supports strict equality with enum', function(): void {
            $dto = new class('Product', '100', 'Match') extends SimpleDto {
                public function __construct(
                    public readonly string $name,
                    public readonly string $price,
                    #[WhenValue('price', ComparisonOperator::StrictEqual, '100')]
                    public readonly ?string $badge = null,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('badge');
        });

        it('supports loose equality with enum', function(): void {
            $dto = new class('Product', 100, 'Match') extends SimpleDto {
                public function __construct(
                    public readonly string $name,
                    public readonly int $price,
                    #[WhenValue('price', ComparisonOperator::LooseEqual, '100')]
                    public readonly ?string $badge = null,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKey('badge');
        });
    });

    describe('WhenNull Attribute', function(): void {
        it('includes property when value is null', function(): void {
            $dto = new ConditionalPropsTestDto4('User', null);

            $array = $dto->toArray();
            expect($array)->toHaveKey('deletedAt')
                ->and($array['deletedAt'])->toBeNull();
        });

        it('excludes property when value is not null', function(): void {
            $dto = new ConditionalPropsTestDto4('User', '2024-01-01');

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('deletedAt');
        });
    });

    describe('WhenNotNull Attribute', function(): void {
        it('includes property when value is not null', function(): void {
            $dto = new ConditionalPropsTestDto5('User', '555-1234');

            $array = $dto->toArray();
            expect($array)->toHaveKey('phone')
                ->and($array['phone'])->toBe('555-1234');
        });

        it('excludes property when value is null', function(): void {
            $dto = new ConditionalPropsTestDto5('User', null);

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('phone');
        });
    });

    describe('WhenTrue Attribute', function(): void {
        it('includes property when value is true', function(): void {
            $dto = new ConditionalPropsTestDto6('Feature', true);

            $array = $dto->toArray();
            expect($array)->toHaveKey('isPremium')
                ->and($array['isPremium'])->toBeTrue();
        });

        it('excludes property when value is false', function(): void {
            $dto = new ConditionalPropsTestDto6('Feature', false);

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('isPremium');
        });
    });

    describe('WhenFalse Attribute', function(): void {
        it('includes property when value is false', function(): void {
            $dto = new ConditionalPropsTestDto7('Feature', false);

            $array = $dto->toArray();
            expect($array)->toHaveKey('isDisabled')
                ->and($array['isDisabled'])->toBeFalse();
        });

        it('excludes property when value is true', function(): void {
            $dto = new ConditionalPropsTestDto7('Feature', true);

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('isDisabled');
        });
    });

    describe('WhenEquals Attribute', function(): void {
        it('includes property when value equals target (strict)', function(): void {
            $dto = new ConditionalPropsTestDto8('completed');

            $array = $dto->toArray();
            expect($array)->toHaveKey('status')
                ->and($array['status'])->toBe('completed');
        });

        it('excludes property when value does not equal target', function(): void {
            $dto = new ConditionalPropsTestDto8('pending');

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('status');
        });
    });

    describe('WhenIn Attribute', function(): void {
        it('includes property when value is in list', function(): void {
            $dto = new ConditionalPropsTestDto9('completed');

            $array = $dto->toArray();
            expect($array)->toHaveKey('status')
                ->and($array['status'])->toBe('completed');
        });

        it('excludes property when value is not in list', function(): void {
            $dto = new ConditionalPropsTestDto9('pending');

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('status');
        });
    });
});

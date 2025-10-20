<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Optional as OptionalAttribute;
use event4u\DataHelpers\Support\Optional;

// Test DTOs defined outside to avoid anonymous class issues
class TestUserDTO1 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,
    ) {}
}

class TestUserDTO2 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly Optional|string $email,
    ) {}
}

class TestUserDTO3 extends SimpleDTO
{
    public function __construct(
        public readonly Optional|string $name,
        public readonly Optional|string|null $email,
    ) {}
}

class TestUserDTO4 extends SimpleDTO
{
    public function __construct(
        public readonly Optional|string $name,
        public readonly Optional|string $email,
        public readonly Optional|int $age,
    ) {}
}

class TestUserDTO5 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute(default: 'default@example.com')]
        public readonly Optional|string $email,
    ) {}
}

describe('Optional Properties', function(): void {
    it('wraps optional properties with attribute syntax', function(): void {
        $dto = TestUserDTO1::fromArray(['name' => 'John']);

        expect($dto->name)->toBe('John')
            ->and($dto->email)->toBeInstanceOf(Optional::class)
            ->and($dto->email->isEmpty())->toBeTrue();
    });

    it('wraps optional properties with union type syntax', function(): void {
        $dto = TestUserDTO2::fromArray(['name' => 'John']);

        expect($dto->name)->toBe('John')
            ->and($dto->email)->toBeInstanceOf(Optional::class)
            ->and($dto->email->isEmpty())->toBeTrue();
    });

    it('distinguishes between null and missing values', function(): void {
        // Missing values
        $dto1 = TestUserDTO3::fromArray([]);
        expect($dto1->name->isEmpty())->toBeTrue()
            ->and($dto1->email->isEmpty())->toBeTrue();

        // Null value (explicitly set)
        $dto2 = TestUserDTO3::fromArray(['email' => null]);
        expect($dto2->name->isEmpty())->toBeTrue()
            ->and($dto2->email->isPresent())->toBeTrue()
            ->and($dto2->email->get())->toBeNull();

        // Present value
        $dto3 = TestUserDTO3::fromArray(['name' => 'John', 'email' => 'john@example.com']);
        expect($dto3->name->isPresent())->toBeTrue()
            ->and($dto3->name->get())->toBe('John')
            ->and($dto3->email->isPresent())->toBeTrue()
            ->and($dto3->email->get())->toBe('john@example.com');
    });

    it('supports partial updates', function(): void {
        // Only update email
        $dto = TestUserDTO4::fromArray(['email' => 'new@example.com']);
        $partial = $dto->partial();

        expect($partial)->toBe(['email' => 'new@example.com'])
            ->and(array_key_exists('name', $partial))->toBeFalse()
            ->and(array_key_exists('age', $partial))->toBeFalse();
    });

    it('includes all values in toArray by default', function(): void {
        $dto = TestUserDTO2::fromArray(['name' => 'John', 'email' => 'john@example.com']);
        $array = $dto->toArray();

        expect($array)->toBe([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);
    });

    it('includes present null values in toArray', function(): void {
        $dto = TestUserDTO3::fromArray(['name' => 'John', 'email' => null]);
        $array = $dto->toArray();

        expect($array)->toBe([
            'name' => 'John',
            'email' => null,
        ]);
    });

    it('includes empty optional values in toArray', function(): void {
        $dto = TestUserDTO2::fromArray(['name' => 'John']);
        $array = $dto->toArray();

        expect($array)->toBe([
            'name' => 'John',
            'email' => null,
        ]);
    });

    it('supports default values', function(): void {
        $dto = TestUserDTO5::fromArray(['name' => 'John']);

        expect($dto->email->isPresent())->toBeTrue()
            ->and($dto->email->get())->toBe('default@example.com');
    });

    it('works with JSON serialization', function(): void {
        $dto = TestUserDTO2::fromArray(['name' => 'John', 'email' => 'john@example.com']);
        $json = json_encode($dto);

        expect($json)->toBe('{"name":"John","email":"john@example.com"}');
    });

    it('handles missing values in JSON serialization', function(): void {
        $dto = TestUserDTO2::fromArray(['name' => 'John']);
        $json = json_encode($dto);

        expect($json)->toBe('{"name":"John","email":null}');
    });
});

describe('Optional Wrapper', function(): void {
    it('creates present optional', function(): void {
        $optional = Optional::of('value');

        expect($optional->isPresent())->toBeTrue()
            ->and($optional->isEmpty())->toBeFalse()
            ->and($optional->get())->toBe('value');
    });

    it('creates empty optional', function(): void {
        $optional = Optional::empty();

        expect($optional->isEmpty())->toBeTrue()
            ->and($optional->isPresent())->toBeFalse()
            ->and($optional->get())->toBeNull();
    });

    it('wraps null value', function(): void {
        $optional = Optional::of(null);

        expect($optional->isPresent())->toBeTrue()
            ->and($optional->get())->toBeNull();
    });

    it('returns default value when empty', function(): void {
        $optional = Optional::empty();

        expect($optional->get('default'))->toBe('default')
            ->and($optional->orElse('default'))->toBe('default');
    });

    it('maps present value', function(): void {
        $optional = Optional::of(5);
        $mapped = $optional->map(fn($x): int => $x * 2);

        expect($mapped->isPresent())->toBeTrue()
            ->and($mapped->get())->toBe(10);
    });

    it('does not map empty value', function(): void {
        $optional = Optional::empty();
        $mapped = $optional->map(fn($x): int => $x * 2);

        expect($mapped->isEmpty())->toBeTrue();
    });

    it('executes callback if present', function(): void {
        $called = false;
        $optional = Optional::of('value');

        $optional->ifPresent(function($value) use (&$called): void {
            $called = true;
            expect($value)->toBe('value');
        });

        expect($called)->toBeTrue();
    });

    it('does not execute callback if empty', function(): void {
        $called = false;
        $optional = Optional::empty();

        $optional->ifPresent(function() use (&$called): void {
            $called = true;
        });

        expect($called)->toBeFalse();
    });

    it('executes callback if empty', function(): void {
        $called = false;
        $optional = Optional::empty();

        $optional->ifEmpty(function() use (&$called): void {
            $called = true;
        });

        expect($called)->toBeTrue();
    });

    it('filters present value', function(): void {
        $optional = Optional::of(5);

        $filtered1 = $optional->filter(fn($x): bool => 3 < $x);
        expect($filtered1->isPresent())->toBeTrue();

        $filtered2 = $optional->filter(fn($x): bool => 10 < $x);
        expect($filtered2->isEmpty())->toBeTrue();
    });

    it('throws exception when empty', function(): void {
        $optional = Optional::empty();

        expect(fn(): mixed => $optional->orThrow('Value not present'))
            ->toThrow(RuntimeException::class, 'Value not present');
    });

    it('converts to array', function(): void {
        $optional = Optional::of('value');

        expect($optional->toArray())->toBe([
            'present' => true,
            'value' => 'value',
        ]);
    });

    it('converts to string', function(): void {
        expect((string)Optional::empty())->toBe('Optional.empty')
            ->and((string)Optional::of(null))->toBe('Optional[null]')
            ->and((string)Optional::of('value'))->toBe('Optional[value]')
            ->and((string)Optional::of(123))->toBe('Optional[123]');
    });
});


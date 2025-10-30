<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Optional as OptionalAttribute;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\Support\Optional;

// Test DTOs (Standard LiteDto - no UltraFast)
class TestUserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,
    ) {}
}

class TestUpdateUserDto extends LiteDto
{
    public function __construct(
        #[OptionalAttribute]
        public readonly Optional|string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,
        #[OptionalAttribute]
        public readonly Optional|string $phone,
    ) {}
}

class TestMixedDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,      // Can be missing
        public readonly ?string $phone,              // Can be null
        #[OptionalAttribute]
        public readonly Optional|string|null $bio,   // Can be missing OR null
    ) {}
}

// Test DTOs with UltraFast
#[UltraFast]
class TestUserUltraFastDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,
    ) {}
}

#[UltraFast]
class TestUpdateUltraFastDto extends LiteDto
{
    public function __construct(
        #[OptionalAttribute]
        public readonly Optional|string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,
    ) {}
}

describe('LiteDto Optional Properties', function(): void {
    it('wraps optional properties when missing', function(): void {
        $dto = TestUserDto::from(['name' => 'John']);

        expect($dto->name)->toBe('John');
        expect($dto->email)->toBeInstanceOf(Optional::class);
        expect($dto->email->isEmpty())->toBeTrue();
        expect($dto->email->isPresent())->toBeFalse();
    });

    it('wraps optional properties when present', function(): void {
        $dto = TestUserDto::from(['name' => 'John', 'email' => 'john@example.com']);

        expect($dto->name)->toBe('John');
        expect($dto->email)->toBeInstanceOf(Optional::class);
        expect($dto->email->isPresent())->toBeTrue();
        expect($dto->email->isEmpty())->toBeFalse();
        expect($dto->email->get())->toBe('john@example.com');
    });

    it('wraps optional properties with null value', function(): void {
        $dto = TestUserDto::from(['name' => 'John', 'email' => null]);

        expect($dto->email)->toBeInstanceOf(Optional::class);
        expect($dto->email->isPresent())->toBeTrue();
        expect($dto->email->get())->toBeNull();
    });

    it('supports partial updates', function(): void {
        $updates = TestUpdateUserDto::from(['email' => 'new@example.com']);

        expect($updates->name->isEmpty())->toBeTrue();
        expect($updates->email->isPresent())->toBeTrue();
        expect($updates->email->get())->toBe('new@example.com');
        expect($updates->phone->isEmpty())->toBeTrue();
    });

    it('distinguishes between null and missing', function(): void {
        // Missing email, explicit null phone
        $dto = TestMixedDto::from(['name' => 'John', 'phone' => null]);

        expect($dto->email->isEmpty())->toBeTrue();      // missing
        expect($dto->phone)->toBeNull();                 // explicitly set to null

        // Explicit null bio
        $dto2 = TestMixedDto::from(['name' => 'John', 'phone' => '123', 'bio' => null]);
        expect($dto2->bio->isPresent())->toBeTrue();
        expect($dto2->bio->get())->toBeNull();
    });

    it('excludes empty optional from toArray', function(): void {
        $dto = TestUserDto::from(['name' => 'John']);

        $array = $dto->toArray();

        expect($array)->toBe(['name' => 'John']);
        expect($array)->not->toHaveKey('email');
    });

    it('includes present optional in toArray', function(): void {
        $dto = TestUserDto::from(['name' => 'John', 'email' => 'john@example.com']);

        $array = $dto->toArray();

        expect($array)->toBe([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);
    });

    it('includes null optional in toArray', function(): void {
        $dto = TestUserDto::from(['name' => 'John', 'email' => null]);

        $array = $dto->toArray();

        expect($array)->toBe([
            'name' => 'John',
            'email' => null,
        ]);
    });

    it('excludes empty optional from toJson', function(): void {
        $dto = TestUserDto::from(['name' => 'John']);

        $json = $dto->toJson();

        expect($json)->toBe('{"name":"John"}');
    });

    it('includes present optional in toJson', function(): void {
        $dto = TestUserDto::from(['name' => 'John', 'email' => 'john@example.com']);

        $json = $dto->toJson();

        expect($json)->toBe('{"name":"John","email":"john@example.com"}');
    });

    it('works with multiple optional properties', function(): void {
        $dto = TestUpdateUserDto::from([
            'name' => 'John',
            'phone' => '123-456-7890',
        ]);

        expect($dto->name->isPresent())->toBeTrue();
        expect($dto->name->get())->toBe('John');
        expect($dto->email->isEmpty())->toBeTrue();
        expect($dto->phone->isPresent())->toBeTrue();
        expect($dto->phone->get())->toBe('123-456-7890');

        $array = $dto->toArray();
        expect($array)->toBe([
            'name' => 'John',
            'phone' => '123-456-7890',
        ]);
    });

    it('uses orElse for default values', function(): void {
        $dto = TestUserDto::from(['name' => 'John']);

        $email = $dto->email->orElse('default@example.com');

        expect($email)->toBe('default@example.com');
    });

    it('maps optional values', function(): void {
        $dto = TestUserDto::from(['name' => 'John', 'email' => 'john@example.com']);

        $uppercase = $dto->email->map(fn($email) => strtoupper($email));

        expect($uppercase->isPresent())->toBeTrue();
        expect($uppercase->get())->toBe('JOHN@EXAMPLE.COM');
    });

    it('does not map empty optional', function(): void {
        $dto = TestUserDto::from(['name' => 'John']);

        $mapped = $dto->email->map(fn($email) => strtoupper($email));

        expect($mapped->isEmpty())->toBeTrue();
    });
});

describe('LiteDto Optional Properties with UltraFast', function(): void {
    it('wraps optional properties when missing (UltraFast)', function(): void {
        $dto = TestUserUltraFastDto::from(['name' => 'John']);

        expect($dto->name)->toBe('John');
        expect($dto->email)->toBeInstanceOf(Optional::class);
        expect($dto->email->isEmpty())->toBeTrue();
    });

    it('wraps optional properties when present (UltraFast)', function(): void {
        $dto = TestUserUltraFastDto::from(['name' => 'John', 'email' => 'john@example.com']);

        expect($dto->email)->toBeInstanceOf(Optional::class);
        expect($dto->email->isPresent())->toBeTrue();
        expect($dto->email->get())->toBe('john@example.com');
    });

    it('supports partial updates (UltraFast)', function(): void {
        $updates = TestUpdateUltraFastDto::from(['email' => 'new@example.com']);

        expect($updates->name->isEmpty())->toBeTrue();
        expect($updates->email->isPresent())->toBeTrue();
        expect($updates->email->get())->toBe('new@example.com');
    });

    it('excludes empty optional from toArray (UltraFast)', function(): void {
        $dto = TestUserUltraFastDto::from(['name' => 'John']);

        $array = $dto->toArray();

        expect($array)->toBe(['name' => 'John']);
        expect($array)->not->toHaveKey('email');
    });

    it('includes present optional in toArray (UltraFast)', function(): void {
        $dto = TestUserUltraFastDto::from(['name' => 'John', 'email' => 'john@example.com']);

        $array = $dto->toArray();

        expect($array)->toBe([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);
    });
});

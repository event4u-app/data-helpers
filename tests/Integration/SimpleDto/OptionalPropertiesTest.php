<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto\Attributes\Optional as OptionalAttribute;
use event4u\DataHelpers\SimpleDto\SimpleDto;
use event4u\DataHelpers\Support\Optional;

// Test DTOs (Standard SimpleDto - no UltraFast)
class SimpleTestUserDto extends SimpleDto
{
    /** @phpstan-ignore-next-line */
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute]
        /** @phpstan-var Optional<string>|string */
        public readonly Optional|string $email,
    ) {}
}

class SimpleTestUpdateUserDto extends SimpleDto
{
    /** @phpstan-ignore-next-line */
    public function __construct(
        #[OptionalAttribute]
        /** @phpstan-var Optional<string>|string */
        public readonly Optional|string $name,
        #[OptionalAttribute]
        /** @phpstan-var Optional<string>|string */
        public readonly Optional|string $email,
        #[OptionalAttribute]
        /** @phpstan-var Optional<string>|string */
        public readonly Optional|string $phone,
    ) {}
}

class SimpleTestMixedDto extends SimpleDto
{
    /** @phpstan-ignore-next-line */
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute]
        /** @phpstan-var Optional<string>|string */
        public readonly Optional|string $email,      // Can be missing
        public readonly ?string $phone,              // Can be null
        #[OptionalAttribute]
        /** @phpstan-var Optional<string|null>|string|null */
        public readonly Optional|string|null $bio,   // Can be missing OR null
    ) {}
}

describe('SimpleDto Optional Properties', function(): void {
    it('wraps optional properties when missing', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John']);

        expect($dto->name)->toBe('John');
        expect($dto->email)->toBeInstanceOf(Optional::class);
        expect($dto->email->isEmpty())->toBeTrue(); // @phpstan-ignore-line
        expect($dto->email->isPresent())->toBeFalse(); // @phpstan-ignore-line
    });

    it('wraps optional properties when present', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John', 'email' => 'john@example.com']);

        expect($dto->name)->toBe('John');
        expect($dto->email)->toBeInstanceOf(Optional::class);
        expect($dto->email->isPresent())->toBeTrue(); // @phpstan-ignore-line
        expect($dto->email->isEmpty())->toBeFalse(); // @phpstan-ignore-line
        expect($dto->email->get())->toBe('john@example.com'); // @phpstan-ignore-line
    });

    it('wraps optional properties with null value', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John', 'email' => null]);

        expect($dto->email)->toBeInstanceOf(Optional::class);
        expect($dto->email->isPresent())->toBeTrue(); // @phpstan-ignore-line
        expect($dto->email->get())->toBeNull(); // @phpstan-ignore-line
    });

    it('supports partial updates', function(): void {
        $updates = SimpleTestUpdateUserDto::from(['email' => 'new@example.com']);

        expect($updates->name->isEmpty())->toBeTrue(); // @phpstan-ignore-line
        expect($updates->email->isPresent())->toBeTrue(); // @phpstan-ignore-line
        expect($updates->email->get())->toBe('new@example.com'); // @phpstan-ignore-line
        expect($updates->phone->isEmpty())->toBeTrue(); // @phpstan-ignore-line
    });

    it('distinguishes between null and missing', function(): void {
        // Missing email, explicit null phone
        $dto = SimpleTestMixedDto::from(['name' => 'John', 'phone' => null]);

        expect($dto->email->isEmpty())->toBeTrue(); // @phpstan-ignore-line
        expect($dto->phone)->toBeNull();                 // explicitly set to null

        // Explicit null bio
        $dto2 = SimpleTestMixedDto::from(['name' => 'John', 'phone' => '123', 'bio' => null]);
        expect($dto2->bio->isPresent())->toBeTrue(); // @phpstan-ignore-line
        expect($dto2->bio->get())->toBeNull(); // @phpstan-ignore-line
    });

    it('excludes empty optional from toArray', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John']);

        $array = $dto->toArray();

        expect($array)->toBe(['name' => 'John']);
        expect($array)->not->toHaveKey('email');
    });

    it('includes present optional in toArray', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John', 'email' => 'john@example.com']);

        $array = $dto->toArray();

        expect($array)->toBe([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);
    });

    it('includes null optional in toArray', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John', 'email' => null]);

        $array = $dto->toArray();

        expect($array)->toBe([
            'name' => 'John',
            'email' => null,
        ]);
    });

    it('excludes empty optional from toJson', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John']);

        $json = $dto->toJson();

        expect($json)->toBe('{"name":"John"}');
    });

    it('includes present optional in toJson', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John', 'email' => 'john@example.com']);

        $json = $dto->toJson();

        expect($json)->toBe('{"name":"John","email":"john@example.com"}');
    });

    it('works with multiple optional properties', function(): void {
        $dto = SimpleTestUpdateUserDto::from([
            'name' => 'John',
            'phone' => '123-456-7890',
        ]);

        expect($dto->name->isPresent())->toBeTrue(); // @phpstan-ignore-line
        expect($dto->name->get())->toBe('John'); // @phpstan-ignore-line
        expect($dto->email->isEmpty())->toBeTrue(); // @phpstan-ignore-line
        expect($dto->phone->isPresent())->toBeTrue(); // @phpstan-ignore-line
        expect($dto->phone->get())->toBe('123-456-7890'); // @phpstan-ignore-line

        $array = $dto->toArray();
        expect($array)->toBe([
            'name' => 'John',
            'phone' => '123-456-7890',
        ]);
    });

    it('uses orElse for default values', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John']);

        $email = $dto->email->orElse('default@example.com'); // @phpstan-ignore-line

        expect($email)->toBe('default@example.com');
    });

    it('maps optional values', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John', 'email' => 'john@example.com']);

        $uppercase = $dto->email->map(fn($email) => strtoupper($email)); // @phpstan-ignore-line

        expect($uppercase->isPresent())->toBeTrue();
        expect($uppercase->get())->toBe('JOHN@EXAMPLE.COM');
    });

    it('does not map empty optional', function(): void {
        $dto = SimpleTestUserDto::from(['name' => 'John']);

        $mapped = $dto->email->map(fn($email) => strtoupper($email)); // @phpstan-ignore-line

        expect($mapped->isEmpty())->toBeTrue();
    });
});

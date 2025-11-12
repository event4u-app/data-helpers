<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto\SimpleDto;

describe('SimpleDto ArrayAccess', function(): void {
    it('implements ArrayAccess interface', function(): void {
        $dto = new class('John Doe', 'john@example.com') extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {}
        };

        expect($dto)->toBeInstanceOf(ArrayAccess::class);
    });

    it('can check if property exists with offsetExists', function(): void {
        $dto = new class('John Doe', 'john@example.com') extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {}
        };

        expect(isset($dto['name']))->toBeTrue()
            ->and(isset($dto['email']))->toBeTrue()
            ->and(isset($dto['nonexistent']))->toBeFalse();
    });

    it('can get property value with offsetGet', function(): void {
        $dto = new class('John Doe', 'john@example.com') extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {}
        };

        expect($dto['name'])->toBe('John Doe')
            ->and($dto['email'])->toBe('john@example.com');
    });

    it('returns null for nonexistent property with offsetGet', function(): void {
        $dto = new class('John Doe') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        expect($dto['nonexistent'])->toBeNull();
    });

    it('throws exception when trying to set readonly property with offsetSet', function(): void {
        $dto = new class('John Doe') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        $dto['name'] = 'Jane Doe';
    })->throws(BadMethodCallException::class, 'Cannot modify readonly property');

    it('throws exception when trying to unset readonly property with offsetUnset', function(): void {
        $dto = new class('John Doe') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        unset($dto['name']);
    })->throws(BadMethodCallException::class, 'Cannot modify readonly property');

    it('works with nested DTOs', function(): void {
        $addressDto = new class('Berlin', '10115') extends SimpleDto {
            public function __construct(
                public readonly string $city,
                public readonly string $zip,
            ) {}
        };

        $userDto = new class('John Doe', $addressDto) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly SimpleDto $address,
            ) {}
        };

        // Direct property access returns the DTO object
        expect($userDto['name'])->toBe('John Doe')
            ->and($userDto['address'])->toBeArray()  // get() converts nested DTOs to arrays
            ->and($userDto['address']['city'])->toBe('Berlin')
            ->and($userDto['address']['zip'])->toBe('10115');
    });

    it('works with nullable properties', function(): void {
        $dto = new class('John Doe', null) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly ?string $email,
            ) {}
        };

        expect($dto['name'])->toBe('John Doe')
            ->and($dto['email'])->toBeNull();
    });

    it('supports dot notation for nested properties', function(): void {
        $addressDto = new class('Berlin', '10115') extends SimpleDto {
            public function __construct(
                public readonly string $city,
                public readonly string $zip,
            ) {}
        };

        $userDto = new class('John Doe', $addressDto) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly SimpleDto $address,
            ) {}
        };

        expect($userDto['address.city'])->toBe('Berlin')
            ->and($userDto['address.zip'])->toBe('10115')
            ->and(isset($userDto['address.city']))->toBeTrue()
            ->and(isset($userDto['address.nonexistent']))->toBeFalse();
    });

    it('supports dot notation with wildcards', function(): void {
        $emailDto1 = new class('john@work.com', 'work') extends SimpleDto {
            public function __construct(
                public readonly string $email,
                public readonly string $type,
            ) {}
        };

        $emailDto2 = new class('john@home.com', 'home') extends SimpleDto {
            public function __construct(
                public readonly string $email,
                public readonly string $type,
            ) {}
        };

        $userDto = new class('John Doe', [$emailDto1, $emailDto2]) extends SimpleDto {
            /** @param array<int, SimpleDto> $emails */
            public function __construct(
                public readonly string $name,
                public readonly array $emails,
            ) {}
        };

        $emails = $userDto['emails.*.email'];
        expect($emails)->toBe([
            'emails.0.email' => 'john@work.com',
            'emails.1.email' => 'john@home.com',
        ]);
    });

    it('throws exception when trying to set readonly nested property with dot notation', function(): void {
        $addressDto = new class('Berlin', '10115') extends SimpleDto {
            public function __construct(
                public readonly string $city,
                public readonly string $zip,
            ) {}
        };

        $userDto = new class('John Doe', $addressDto) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly SimpleDto $address,
            ) {}
        };

        $userDto['address.city'] = 'Munich';
    })->throws(BadMethodCallException::class, 'Cannot modify nested path');

    it('allows setting mutable properties with offsetSet', function(): void {
        $dto = new class('John Doe', 0) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public int $loginCount,
            ) {}
        };

        expect($dto['loginCount'])->toBe(0);

        $dto['loginCount'] = 5;

        expect($dto['loginCount'])->toBe(5);
    });

    it('allows unsetting mutable properties with offsetUnset (sets to null)', function(): void {
        $dto = new class('John Doe', 'john@example.com') extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public ?string $email,
            ) {}
        };

        expect($dto->email)->toBe('john@example.com');
        expect($dto['email'])->toBe('john@example.com');

        // Test offsetUnset
        unset($dto['email']);

        // Check both direct property access and array access
        expect($dto->email)->toBeNull();
        expect($dto['email'])->toBeNull();
    });

    it('has() method checks if property exists', function(): void {
        $dto = new class('John Doe', 'john@example.com') extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly ?string $email,
            ) {}
        };

        expect($dto->has('name'))->toBeTrue();
        expect($dto->has('email'))->toBeTrue();
        expect($dto->has('nonexistent'))->toBeFalse();
    });

    it('has() method supports dot notation', function(): void {
        $addressDto = new class('Berlin', '10115') extends SimpleDto {
            public function __construct(
                public readonly string $city,
                public readonly string $zip,
            ) {}
        };

        $userDto = new class('John Doe', $addressDto) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly SimpleDto $address,
            ) {}
        };

        expect($userDto->has('name'))->toBeTrue();
        expect($userDto->has('address'))->toBeTrue();
        expect($userDto->has('address.city'))->toBeTrue();
        expect($userDto->has('address.zip'))->toBeTrue();
        expect($userDto->has('address.nonexistent'))->toBeFalse();
        expect($userDto->has('nonexistent.city'))->toBeFalse();
    });
});

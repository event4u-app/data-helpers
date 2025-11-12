<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\LiteDto;

describe('LiteDto ArrayAccess', function(): void {
    it('implements ArrayAccess interface', function(): void {
        $dto = new class('John Doe', 'john@example.com') extends LiteDto {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {}
        };

        expect($dto)->toBeInstanceOf(ArrayAccess::class);
    });

    it('can check if property exists with offsetExists', function(): void {
        $dto = new class('John Doe', 'john@example.com') extends LiteDto {
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
        $dto = new class('John Doe', 'john@example.com') extends LiteDto {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {}
        };

        expect($dto['name'])->toBe('John Doe')
            ->and($dto['email'])->toBe('john@example.com');
    });

    it('returns null for nonexistent property with offsetGet', function(): void {
        $dto = new class('John Doe') extends LiteDto {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        expect($dto['nonexistent'])->toBeNull();
    });

    it('throws exception when trying to set property with offsetSet', function(): void {
        $dto = new class('John Doe') extends LiteDto {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        $dto['name'] = 'Jane Doe';
    })->throws(BadMethodCallException::class, 'LiteDto is immutable');

    it('throws exception when trying to unset property with offsetUnset', function(): void {
        $dto = new class('John Doe') extends LiteDto {
            public function __construct(
                public readonly string $name,
            ) {}
        };

        unset($dto['name']);
    })->throws(BadMethodCallException::class, 'LiteDto is immutable');

    it('works with nested DTOs', function(): void {
        $addressDto = new class('Berlin', '10115') extends LiteDto {
            public function __construct(
                public readonly string $city,
                public readonly string $zip,
            ) {}
        };

        $userDto = new class('John Doe', $addressDto) extends LiteDto {
            public function __construct(
                public readonly string $name,
                public readonly LiteDto $address,
            ) {}
        };

        // Direct property access returns the DTO object
        expect($userDto['name'])->toBe('John Doe')
            ->and($userDto['address'])->toBeArray()  // get() converts nested DTOs to arrays
            ->and($userDto['address']['city'])->toBe('Berlin')
            ->and($userDto['address']['zip'])->toBe('10115');
    });

    it('works with nullable properties', function(): void {
        $dto = new class('John Doe', null) extends LiteDto {
            public function __construct(
                public readonly string $name,
                public readonly ?string $email,
            ) {}
        };

        expect($dto['name'])->toBe('John Doe')
            ->and($dto['email'])->toBeNull();
    });

    it('supports dot notation for nested properties', function(): void {
        $addressDto = new class('Berlin', '10115') extends LiteDto {
            public function __construct(
                public readonly string $city,
                public readonly string $zip,
            ) {}
        };

        $userDto = new class('John Doe', $addressDto) extends LiteDto {
            public function __construct(
                public readonly string $name,
                public readonly LiteDto $address,
            ) {}
        };

        expect($userDto['address.city'])->toBe('Berlin')
            ->and($userDto['address.zip'])->toBe('10115')
            ->and(isset($userDto['address.city']))->toBeTrue()
            ->and(isset($userDto['address.nonexistent']))->toBeFalse();
    });

    it('supports dot notation with wildcards', function(): void {
        $emailDto1 = new class('john@work.com', 'work') extends LiteDto {
            public function __construct(
                public readonly string $email,
                public readonly string $type,
            ) {}
        };

        $emailDto2 = new class('john@home.com', 'home') extends LiteDto {
            public function __construct(
                public readonly string $email,
                public readonly string $type,
            ) {}
        };

        $userDto = new class('John Doe', [$emailDto1, $emailDto2]) extends LiteDto {
            /** @param array<int, LiteDto> $emails */
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

    it('throws exception when trying to set with dot notation', function(): void {
        $addressDto = new class('Berlin', '10115') extends LiteDto {
            public function __construct(
                public readonly string $city,
                public readonly string $zip,
            ) {}
        };

        $userDto = new class('John Doe', $addressDto) extends LiteDto {
            public function __construct(
                public readonly string $name,
                public readonly LiteDto $address,
            ) {}
        };

        $userDto['address.city'] = 'Munich';
    })->throws(BadMethodCallException::class, 'LiteDto is immutable');

    it('has() method checks if property exists', function(): void {
        $dto = new class('John Doe', 'john@example.com') extends LiteDto {
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
        $addressDto = new class('Berlin', '10115') extends LiteDto {
            public function __construct(
                public readonly string $city,
                public readonly string $zip,
            ) {}
        };

        $userDto = new class('John Doe', $addressDto) extends LiteDto {
            public function __construct(
                public readonly string $name,
                public readonly LiteDto $address,
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

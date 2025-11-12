<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto;

use event4u\DataHelpers\SimpleDto;

class AddressDtoForDotNotation extends SimpleDto
{
    public function __construct(
        public string $street,
        public string $city,
        public string $country,
    ) {}
}

class EmailDtoForDotNotation extends SimpleDto
{
    public function __construct(
        public string $email,
        public string $type,
        public bool $verified = false,
    ) {}
}

class UserDtoForDotNotation extends SimpleDto
{
    /** @param array<int, EmailDtoForDotNotation> $emails */
    public function __construct(
        public string $name,
        public int $age,
        public AddressDtoForDotNotation $address,
        public array $emails = [],
    ) {}
}

test('get() retrieves simple properties', function(): void {
    $user = UserDtoForDotNotation::from([
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'street' => 'Main St',
            'city' => 'New York',
            'country' => 'USA',
        ],
    ]);

    expect($user->get('name'))->toBe('John Doe');
    expect($user->get('age'))->toBe(30);
});

test('get() retrieves nested properties using dot notation', function(): void {
    $user = UserDtoForDotNotation::from([
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'street' => 'Main St',
            'city' => 'New York',
            'country' => 'USA',
        ],
    ]);

    expect($user->get('address.city'))->toBe('New York');
    expect($user->get('address.country'))->toBe('USA');
    expect($user->get('address.street'))->toBe('Main St');
});

test('get() returns default value for non-existent paths', function(): void {
    $user = UserDtoForDotNotation::from([
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'street' => 'Main St',
            'city' => 'New York',
            'country' => 'USA',
        ],
    ]);

    expect($user->get('nonexistent'))->toBeNull();
    expect($user->get('nonexistent', 'default'))->toBe('default');
    expect($user->get('address.nonexistent', 'N/A'))->toBe('N/A');
});

test('get() works with wildcards on arrays', function(): void {
    $user = UserDtoForDotNotation::from([
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'street' => 'Main St',
            'city' => 'New York',
            'country' => 'USA',
        ],
        'emails' => [
            ['email' => 'john@work.com', 'type' => 'work', 'verified' => true],
            ['email' => 'john@home.com', 'type' => 'home', 'verified' => false],
        ],
    ]);

    $addresses = $user->get('emails.*.email');
    expect($addresses)->toBe([
        'emails.0.email' => 'john@work.com',
        'emails.1.email' => 'john@home.com',
    ]);

    $verified = $user->get('emails.*.verified');
    expect($verified)->toBe([
        'emails.0.verified' => true,
        'emails.1.verified' => false,
    ]);
});

test('get() works with array indices', function(): void {
    $user = UserDtoForDotNotation::from([
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'street' => 'Main St',
            'city' => 'New York',
            'country' => 'USA',
        ],
        'emails' => [
            ['email' => 'first@example.com', 'type' => 'work', 'verified' => true],
            ['email' => 'second@example.com', 'type' => 'home', 'verified' => false],
        ],
    ]);

    expect($user->get('emails.0.email'))->toBe('first@example.com');
    expect($user->get('emails.1.email'))->toBe('second@example.com');
    expect($user->get('emails.0.verified'))->toBeTrue();
    expect($user->get('emails.1.verified'))->toBeFalse();
});

test('set() modifies mutable property directly', function(): void {
    $user = UserDtoForDotNotation::from([
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'street' => 'Main St',
            'city' => 'New York',
            'country' => 'USA',
        ],
    ]);

    $user->set('name', 'Jane Doe');

    // Property modified directly
    expect($user->name)->toBe('Jane Doe');
    expect($user->age)->toBe(30);
});

test('set() modifies nested mutable property directly', function(): void {
    $user = UserDtoForDotNotation::from([
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'street' => 'Main St',
            'city' => 'New York',
            'country' => 'USA',
        ],
    ]);

    $user->set('address.city', 'Los Angeles');

    // Property modified directly
    expect($user->get('address.city'))->toBe('Los Angeles');
    expect($user->get('address.country'))->toBe('USA');
});

test('set() works with array indices', function(): void {
    $user = UserDtoForDotNotation::from([
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'street' => 'Main St',
            'city' => 'New York',
            'country' => 'USA',
        ],
        'emails' => [
            ['email' => 'first@example.com', 'type' => 'work', 'verified' => false],
            ['email' => 'second@example.com', 'type' => 'home', 'verified' => false],
        ],
    ]);

    $user->set('emails.0.verified', true);

    // Property modified directly
    expect($user->get('emails.0.verified'))->toBeTrue();
    expect($user->get('emails.1.verified'))->toBeFalse();
});

test('set() modifies mutable DTO directly', function(): void {
    $user = UserDtoForDotNotation::from([
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'street' => 'Main St',
            'city' => 'New York',
            'country' => 'USA',
        ],
    ]);

    $user->set('name', 'Jane Doe');
    $user->set('age', 25);
    $user->set('address.city', 'Los Angeles');

    // All properties modified on same instance
    expect($user->name)->toBe('Jane Doe');
    expect($user->age)->toBe(25);
    expect($user->get('address.city'))->toBe('Los Angeles');
});

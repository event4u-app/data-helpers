<?php

declare(strict_types=1);

namespace Tests\Unit\LiteDto;

use event4u\DataHelpers\LiteDto\LiteDto;

class AddressDto extends LiteDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class EmailDto extends LiteDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $type,
        public readonly bool $verified = false,
    ) {}
}

class UserDto extends LiteDto
{
    /** @param array<int, EmailDto> $emails */
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly AddressDto $address,
        public readonly array $emails = [],
    ) {}
}

test('get() retrieves simple properties', function(): void {
    $user = new UserDto(
        name: 'John Doe',
        age: 30,
        address: new AddressDto(
            street: 'Main St',
            city: 'New York',
            country: 'USA'
        )
    );

    expect($user->get('name'))->toBe('John Doe');
    expect($user->get('age'))->toBe(30);
});

test('get() retrieves nested properties using dot notation', function(): void {
    $user = new UserDto(
        name: 'John Doe',
        age: 30,
        address: new AddressDto(
            street: 'Main St',
            city: 'New York',
            country: 'USA'
        )
    );

    expect($user->get('address.city'))->toBe('New York');
    expect($user->get('address.country'))->toBe('USA');
    expect($user->get('address.street'))->toBe('Main St');
});

test('get() returns default value for non-existent paths', function(): void {
    $user = new UserDto(
        name: 'John Doe',
        age: 30,
        address: new AddressDto(
            street: 'Main St',
            city: 'New York',
            country: 'USA'
        )
    );

    expect($user->get('nonexistent'))->toBeNull();
    expect($user->get('nonexistent', 'default'))->toBe('default');
    expect($user->get('address.nonexistent', 'N/A'))->toBe('N/A');
});

test('get() works with wildcards on arrays', function(): void {
    $user = new UserDto(
        name: 'John Doe',
        age: 30,
        address: new AddressDto(
            street: 'Main St',
            city: 'New York',
            country: 'USA'
        ),
        emails: [
            new EmailDto(email: 'john@work.com', type: 'work', verified: true),
            new EmailDto(email: 'john@home.com', type: 'home', verified: false),
        ]
    );

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
    $user = new UserDto(
        name: 'John Doe',
        age: 30,
        address: new AddressDto(
            street: 'Main St',
            city: 'New York',
            country: 'USA'
        ),
        emails: [
            new EmailDto(email: 'first@example.com', type: 'work', verified: true),
            new EmailDto(email: 'second@example.com', type: 'home', verified: false),
        ]
    );

    expect($user->get('emails.0.email'))->toBe('first@example.com');
    expect($user->get('emails.1.email'))->toBe('second@example.com');
    expect($user->get('emails.0.verified'))->toBeTrue();
    expect($user->get('emails.1.verified'))->toBeFalse();
});

test('set() creates new instance with updated simple property', function(): void {
    $user = new UserDto(
        name: 'John Doe',
        age: 30,
        address: new AddressDto(
            street: 'Main St',
            city: 'New York',
            country: 'USA'
        )
    );

    $updated = $user->set('name', 'Jane Doe');

    // Original unchanged
    expect($user->name)->toBe('John Doe');

    // New instance updated
    expect($updated->name)->toBe('Jane Doe');
    expect($updated->age)->toBe(30);
});

test('set() creates new instance with updated nested property', function(): void {
    $user = new UserDto(
        name: 'John Doe',
        age: 30,
        address: new AddressDto(
            street: 'Main St',
            city: 'New York',
            country: 'USA'
        )
    );

    $updated = $user->set('address.city', 'Los Angeles');

    // Original unchanged
    expect($user->get('address.city'))->toBe('New York');

    // New instance updated
    expect($updated->get('address.city'))->toBe('Los Angeles');
    expect($updated->get('address.country'))->toBe('USA');
});

test('set() works with array indices', function(): void {
    $user = new UserDto(
        name: 'John Doe',
        age: 30,
        address: new AddressDto(
            street: 'Main St',
            city: 'New York',
            country: 'USA'
        ),
        emails: [
            new EmailDto(email: 'first@example.com', type: 'work', verified: false),
            new EmailDto(email: 'second@example.com', type: 'home', verified: false),
        ]
    );

    $updated = $user->set('emails.0.verified', true);

    // Original unchanged
    expect($user->get('emails.0.verified'))->toBeFalse();

    // New instance updated
    expect($updated->get('emails.0.verified'))->toBeTrue();
    expect($updated->get('emails.1.verified'))->toBeFalse();
});

test('set() maintains immutability', function(): void {
    $user = new UserDto(
        name: 'John Doe',
        age: 30,
        address: new AddressDto(
            street: 'Main St',
            city: 'New York',
            country: 'USA'
        )
    );

    $updated1 = $user->set('name', 'Jane Doe');
    $updated2 = $updated1->set('age', 25);
    $updated3 = $updated2->set('address.city', 'Los Angeles');

    // All instances are different
    expect($user->name)->toBe('John Doe');
    expect($user->age)->toBe(30);
    expect($user->get('address.city'))->toBe('New York');

    expect($updated1->name)->toBe('Jane Doe');
    expect($updated1->age)->toBe(30);
    expect($updated1->get('address.city'))->toBe('New York');

    expect($updated2->name)->toBe('Jane Doe');
    expect($updated2->age)->toBe(25);
    expect($updated2->get('address.city'))->toBe('New York');

    expect($updated3->name)->toBe('Jane Doe');
    expect($updated3->age)->toBe(25);
    expect($updated3->get('address.city'))->toBe('Los Angeles');
});

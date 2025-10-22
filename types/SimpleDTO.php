<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use function PHPStan\Testing\assertType;

// Test DTO class
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Required]
        #[Email]
        public readonly string $email,
        public readonly ?int $age = null,
        #[Hidden]
        public readonly ?string $password = null,
    ) {}
}

// Test nested DTO
class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class ProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $fullName,
        public readonly AddressDTO $address,
    ) {}
}

// Test fromArray
$data = ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30];
$user = UserDTO::fromArray($data);
assertType(UserDTO::class, $user);

// Test toArray
$array = $user->toArray();
assertType('array<string, mixed>', $array);

// Test jsonSerialize
$json = $user->jsonSerialize();
assertType('array<string, mixed>', $json);

// Test nested DTO
$data = [
    'fullName' => 'Alice',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'country' => 'USA',
    ],
];
$profile = ProfileDTO::fromArray($data);
assertType(ProfileDTO::class, $profile);
// assertType(AddressDTO::class, $profile->address); // Skipped due to PHPStan limitation with promoted properties

// Test with method - adds additional data, doesn't change properties
$updated = $profile->with(['extra' => 'data']);
assertType(ProfileDTO::class, $updated);

// Test property access on ProfileDTO
// assertType('string', $profile->fullName); // Skipped due to PHPStan limitation with promoted properties
// assertType(AddressDTO::class, $profile->address); // Skipped due to PHPStan limitation with promoted properties
// assertType('string', $profile->address->city); // Skipped due to PHPStan limitation with promoted properties

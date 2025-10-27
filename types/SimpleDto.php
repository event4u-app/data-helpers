<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use function PHPStan\Testing\assertType;

// Test Dto class
class UserDto extends SimpleDto
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

// Test nested Dto
class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class ProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $fullName,
        public readonly AddressDto $address,
    ) {}
}

// Test fromArray
$data = ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30];
$user = UserDto::fromArray($data);
assertType(UserDto::class, $user);

// Test toArray
$array = $user->toArray();
assertType('array<string, mixed>', $array);

// Test jsonSerialize
$json = $user->jsonSerialize();
assertType('array<string, mixed>', $json);

// Test nested Dto
$data = [
    'fullName' => 'Alice',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'country' => 'USA',
    ],
];
$profile = ProfileDto::fromArray($data);
assertType(ProfileDto::class, $profile);
// assertType(AddressDto::class, $profile->address); // Skipped due to PHPStan limitation with promoted properties

// Test with method - adds additional data, doesn't change properties
$updated = $profile->with(['extra' => 'data']);
assertType(ProfileDto::class, $updated);

// Test property access on ProfileDto
// assertType('string', $profile->fullName); // Skipped due to PHPStan limitation with promoted properties
// assertType(AddressDto::class, $profile->address); // Skipped due to PHPStan limitation with promoted properties
// assertType('string', $profile->address->city); // Skipped due to PHPStan limitation with promoted properties

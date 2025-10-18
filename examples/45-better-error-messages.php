<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Exceptions\DTOException;

// Example 1: Type Mismatch Error
echo "Example 1: Type Mismatch Error\n";
echo str_repeat('=', 80) . "\n\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

try {
    // Wrong type for age (string instead of int)
    UserDTO::fromArray([
        'name' => 'John Doe',
        'age' => 'thirty', // Should be int
        'email' => 'john@example.com',
    ]);
} catch (TypeError $e) {
    echo "❌  Standard PHP TypeError:\n";
    echo $e->getMessage() . "\n\n";
}

// With DTOException (enhanced error message)
try {
    throw DTOException::typeMismatch(
        dtoClass: UserDTO::class,
        property: 'age',
        expectedType: 'int',
        actualValue: 'thirty',
        propertyPath: 'user.age'
    );
} catch (DTOException $e) {
    echo "✅  Enhanced DTOException:\n";
    echo $e->getMessage() . "\n\n";
}

// Example 2: Missing Property Error
echo "\nExample 2: Missing Property Error\n";
echo str_repeat('=', 80) . "\n\n";

try {
    throw DTOException::missingProperty(
        dtoClass: UserDTO::class,
        property: 'email',
        availableKeys: ['name', 'age', 'emial', 'mail'] // Note: typo in 'emial'
    );
} catch (DTOException $e) {
    echo "✅  Enhanced error with suggestions:\n";
    echo $e->getMessage() . "\n\n";
}

// Example 3: Invalid Cast Error
echo "\nExample 3: Invalid Cast Error\n";
echo str_repeat('=', 80) . "\n\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    protected function casts(): array
    {
        return [
            'createdAt' => 'datetime',
        ];
    }
}

try {
    throw DTOException::invalidCast(
        dtoClass: ProductDTO::class,
        property: 'createdAt',
        castType: 'datetime',
        value: 'invalid-date-format',
        reason: 'Failed to parse time string (invalid-date-format)'
    );
} catch (DTOException $e) {
    echo "✅  Enhanced cast error:\n";
    echo $e->getMessage() . "\n\n";
}

// Example 4: Nested DTO Error
echo "\nExample 4: Nested DTO Error\n";
echo str_repeat('=', 80) . "\n\n";

class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $zipCode,
    ) {}
}

class CustomerDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $address,
    ) {}
}

try {
    throw DTOException::nestedError(
        dtoClass: CustomerDTO::class,
        property: 'address',
        nestedDtoClass: AddressDTO::class,
        nestedProperty: 'street',
        originalMessage: 'Missing required property in App\AddressDTO::$street'
    );
} catch (DTOException $e) {
    echo "✅  Enhanced nested error with property path:\n";
    echo $e->getMessage() . "\n\n";
}

// Example 5: Numeric String to Int Suggestion
echo "\nExample 5: Numeric String to Int Suggestion\n";
echo str_repeat('=', 80) . "\n\n";

try {
    throw DTOException::typeMismatch(
        dtoClass: UserDTO::class,
        property: 'age',
        expectedType: 'int',
        actualValue: '25' // Numeric string
    );
} catch (DTOException $e) {
    echo "✅  Helpful suggestion for numeric string:\n";
    echo $e->getMessage() . "\n\n";
}

// Example 6: Null to Non-Nullable Suggestion
echo "\nExample 6: Null to Non-Nullable Suggestion\n";
echo str_repeat('=', 80) . "\n\n";

try {
    throw DTOException::typeMismatch(
        dtoClass: UserDTO::class,
        property: 'name',
        expectedType: 'string',
        actualValue: null
    );
} catch (DTOException $e) {
    echo "✅  Helpful suggestion for null value:\n";
    echo $e->getMessage() . "\n\n";
}

// Example 7: Array to Object Suggestion
echo "\nExample 7: Array to Object Suggestion\n";
echo str_repeat('=', 80) . "\n\n";

try {
    throw DTOException::typeMismatch(
        dtoClass: CustomerDTO::class,
        property: 'address',
        expectedType: AddressDTO::class,
        actualValue: ['street' => 'Main St', 'city' => 'New York', 'zipCode' => '10001']
    );
} catch (DTOException $e) {
    echo "✅  Helpful suggestion for array to DTO:\n";
    echo $e->getMessage() . "\n\n";
}

// Example 8: Long String Truncation
echo "\nExample 8: Long String Truncation\n";
echo str_repeat('=', 80) . "\n\n";

try {
    $longString = str_repeat('Lorem ipsum dolor sit amet, ', 10);
    throw DTOException::typeMismatch(
        dtoClass: ProductDTO::class,
        property: 'name',
        expectedType: 'string',
        actualValue: $longString
    );
} catch (DTOException $e) {
    echo "✅  Long values are truncated:\n";
    echo $e->getMessage() . "\n\n";
}

// Example 9: Boolean Formatting
echo "\nExample 9: Boolean Formatting\n";
echo str_repeat('=', 80) . "\n\n";

class SettingsDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $theme,
        public readonly bool $darkMode,
    ) {}
}

try {
    throw DTOException::typeMismatch(
        dtoClass: SettingsDTO::class,
        property: 'theme',
        expectedType: 'string',
        actualValue: true
    );
} catch (DTOException $e) {
    echo "✅  Boolean values are formatted:\n";
    echo $e->getMessage() . "\n\n";
}

echo "\n✅  All examples completed!\n";


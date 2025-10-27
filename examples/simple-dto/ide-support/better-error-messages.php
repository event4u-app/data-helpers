<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Exceptions\DtoException;

// Example 1: Type Mismatch Error
echo "Example 1: Type Mismatch Error\n";
echo str_repeat('=', 80) . "\n\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

try {
    // Wrong type for age (string instead of int)
    UserDto::fromArray([
        'name' => 'John Doe',
        'age' => 'thirty', // Should be int
        'email' => 'john@example.com',
    ]);
} catch (TypeError $typeError) {
    echo "❌  Standard PHP TypeError:\n";
    echo $typeError->getMessage() . "\n\n";
}

// With DtoException (enhanced error message)
try {
    /** @phpstan-ignore-next-line unknown */
    throw DtoException::typeMismatch(
        dtoClass: UserDto::class,
        property: 'age',
        expectedType: 'int',
        actualValue: 'thirty',
        propertyPath: 'user.age'
    );
/** @phpstan-ignore-next-line unknown */
} catch (DtoException $dtoException) {
    echo "✅  Enhanced DtoException:\n";
    /** @phpstan-ignore-next-line unknown */
    echo $dtoException->getMessage() . "\n\n";
}

// Example 2: Missing Property Error
echo "\nExample 2: Missing Property Error\n";
echo str_repeat('=', 80) . "\n\n";

try {
    /** @phpstan-ignore-next-line unknown */
    throw DtoException::missingProperty(
        dtoClass: UserDto::class,
        property: 'email',
        availableKeys: ['name', 'age', 'emial', 'mail'] // Note: typo in 'emial'
    );
/** @phpstan-ignore-next-line unknown */
} catch (DtoException $dtoException) {
    echo "✅  Enhanced error with suggestions:\n";
    /** @phpstan-ignore-next-line unknown */
    echo $dtoException->getMessage() . "\n\n";
}

// Example 3: Invalid Cast Error
echo "\nExample 3: Invalid Cast Error\n";
echo str_repeat('=', 80) . "\n\n";

class ProductDto extends SimpleDto
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
    /** @phpstan-ignore-next-line unknown */
    throw DtoException::invalidCast(
        dtoClass: ProductDto::class,
        property: 'createdAt',
        castType: 'datetime',
        value: 'invalid-date-format',
        reason: 'Failed to parse time string (invalid-date-format)'
    );
/** @phpstan-ignore-next-line unknown */
} catch (DtoException $dtoException) {
    echo "✅  Enhanced cast error:\n";
    /** @phpstan-ignore-next-line unknown */
    echo $dtoException->getMessage() . "\n\n";
}

// Example 4: Nested Dto Error
echo "\nExample 4: Nested Dto Error\n";
echo str_repeat('=', 80) . "\n\n";

class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $zipCode,
    ) {}
}

class CustomerDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}

try {
    /** @phpstan-ignore-next-line unknown */
    throw DtoException::nestedError(
        dtoClass: CustomerDto::class,
        property: 'address',
        nestedDtoClass: AddressDto::class,
        nestedProperty: 'street',
        originalMessage: 'Missing required property in App\AddressDto::$street'
    );
/** @phpstan-ignore-next-line unknown */
} catch (DtoException $dtoException) {
    echo "✅  Enhanced nested error with property path:\n";
    /** @phpstan-ignore-next-line unknown */
    echo $dtoException->getMessage() . "\n\n";
}

// Example 5: Numeric String to Int Suggestion
echo "\nExample 5: Numeric String to Int Suggestion\n";
echo str_repeat('=', 80) . "\n\n";

try {
    /** @phpstan-ignore-next-line unknown */
    throw DtoException::typeMismatch(
        dtoClass: UserDto::class,
        property: 'age',
        expectedType: 'int',
        actualValue: '25' // Numeric string
    );
/** @phpstan-ignore-next-line unknown */
} catch (DtoException $dtoException) {
    echo "✅  Helpful suggestion for numeric string:\n";
    /** @phpstan-ignore-next-line unknown */
    echo $dtoException->getMessage() . "\n\n";
}

// Example 6: Null to Non-Nullable Suggestion
echo "\nExample 6: Null to Non-Nullable Suggestion\n";
echo str_repeat('=', 80) . "\n\n";

try {
    /** @phpstan-ignore-next-line unknown */
    throw DtoException::typeMismatch(
        dtoClass: UserDto::class,
        property: 'name',
        expectedType: 'string',
        actualValue: null
    );
/** @phpstan-ignore-next-line unknown */
} catch (DtoException $dtoException) {
    echo "✅  Helpful suggestion for null value:\n";
    /** @phpstan-ignore-next-line unknown */
    echo $dtoException->getMessage() . "\n\n";
}

// Example 7: Array to Object Suggestion
echo "\nExample 7: Array to Object Suggestion\n";
echo str_repeat('=', 80) . "\n\n";

try {
    /** @phpstan-ignore-next-line unknown */
    throw DtoException::typeMismatch(
        dtoClass: CustomerDto::class,
        property: 'address',
        expectedType: AddressDto::class,
        actualValue: ['street' => 'Main St', 'city' => 'New York', 'zipCode' => '10001']
    );
/** @phpstan-ignore-next-line unknown */
} catch (DtoException $dtoException) {
    echo "✅  Helpful suggestion for array to Dto:\n";
    /** @phpstan-ignore-next-line unknown */
    echo $dtoException->getMessage() . "\n\n";
}

// Example 8: Long String Truncation
echo "\nExample 8: Long String Truncation\n";
echo str_repeat('=', 80) . "\n\n";

try {
    $longString = str_repeat('Lorem ipsum dolor sit amet, ', 10);
    /** @phpstan-ignore-next-line unknown */
    throw DtoException::typeMismatch(
        dtoClass: ProductDto::class,
        property: 'name',
        expectedType: 'string',
        actualValue: $longString
    );
/** @phpstan-ignore-next-line unknown */
} catch (DtoException $dtoException) {
    echo "✅  Long values are truncated:\n";
    /** @phpstan-ignore-next-line unknown */
    echo $dtoException->getMessage() . "\n\n";
}

// Example 9: Boolean Formatting
echo "\nExample 9: Boolean Formatting\n";
echo str_repeat('=', 80) . "\n\n";

class SettingsDto extends SimpleDto
{
    public function __construct(
        public readonly string $theme,
        public readonly bool $darkMode,
    ) {}
}

try {
    /** @phpstan-ignore-next-line unknown */
    throw DtoException::typeMismatch(
        dtoClass: SettingsDto::class,
        property: 'theme',
        expectedType: 'string',
        actualValue: true
    );
/** @phpstan-ignore-next-line unknown */
} catch (DtoException $dtoException) {
    echo "✅  Boolean values are formatted:\n";
    /** @phpstan-ignore-next-line unknown */
    echo $dtoException->getMessage() . "\n\n";
}

echo "\n✅  All examples completed!\n";

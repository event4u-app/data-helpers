---
title: Convert Empty to Null
description: Automatically convert empty values to null during hydration
sidebar:
  order: 8
---

The `#[ConvertEmptyToNull]` attribute automatically converts empty values (empty strings, empty arrays) to `null` during hydration. This is useful when APIs return empty strings or empty arrays for optional fields.

## What is considered "empty"?

By default, the following values are converted to `null`:

- Empty string: `""`
- Empty array: `[]`
- `null`

**Optional conversions** (disabled by default):
- Integer zero: `0` - enable with `convertZero: true`
- String zero: `"0"` - enable with `convertStringZero: true`
- Boolean false: `false` - enable with `convertFalse: true`

## Basic Usage

### Property-Level

Apply the attribute to individual properties:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

class ProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[ConvertEmptyToNull]
        public readonly ?string $bio = null,

        #[ConvertEmptyToNull]
        public readonly ?array $tags = null,
    ) {}
}

$profile = ProfileDto::fromArray([
    'name' => 'John Doe',
    'bio' => '',      // Empty string
    'tags' => [],     // Empty array
]);

echo $profile->name; // 'John Doe'
echo $profile->bio;  // null
echo $profile->tags; // null
```

### Class-Level

Apply the attribute to the entire class to convert all empty values:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

#[ConvertEmptyToNull]
class ProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $bio = null,
        public readonly ?array $tags = null,
        public readonly ?string $website = null,
    ) {}
}

$profile = ProfileDto::fromArray([
    'name' => 'John Doe',
    'bio' => '',
    'tags' => [],
    'website' => '',
]);

echo $profile->name;    // 'John Doe'
echo $profile->bio;     // null
echo $profile->tags;    // null
echo $profile->website; // null
```

## Common Use Cases

### API Responses with Empty Strings

Many APIs return empty strings for optional fields:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[ConvertEmptyToNull]
        public readonly ?string $phone = null,

        #[ConvertEmptyToNull]
        public readonly ?string $address = null,
    ) {}
}

// API returns empty strings for missing optional fields
$user = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '',    // API returns empty string
    'address' => '',  // API returns empty string
]);

// Now you can check with null instead of empty string
if ($user->phone !== null) {
    echo "Phone: {$user->phone}";
}
```

### Form Data with Empty Fields

HTML forms often submit empty strings for unfilled fields:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

class ContactFormDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $message,

        #[ConvertEmptyToNull]
        public readonly ?string $phone = null,

        #[ConvertEmptyToNull]
        public readonly ?string $company = null,
    ) {}
}

// Form submission with empty optional fields
$form = ContactFormDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'message' => 'Hello!',
    'phone' => '',    // Empty form field
    'company' => '',  // Empty form field
]);

// Clean null checks instead of empty string checks
if ($form->phone !== null) {
    echo "Phone: {$form->phone}";
}
```

### Database Results with Empty Arrays

Database queries might return empty arrays for JSON columns:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,

        #[ConvertEmptyToNull]
        public readonly ?array $images = null,

        #[ConvertEmptyToNull]
        public readonly ?array $tags = null,
    ) {}
}

$product = ProductDto::fromArray([
    'name' => 'Product',
    'price' => 99.99,
    'images' => [],  // No images
    'tags' => [],    // No tags
]);

// Check for null instead of empty array
if ($product->images !== null && count($product->images) > 0) {
    echo "Has images";
}
```

## Boolean Handling

By default, the attribute **does not** convert boolean `false` to `null`. You need to explicitly enable this with `convertFalse: true`:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

class SettingsDto extends SimpleDto
{
    public function __construct(
        // Default: false stays false
        #[ConvertEmptyToNull]
        public readonly ?bool $notifications = null,

        // Convert false to null
        #[ConvertEmptyToNull(convertFalse: true)]
        public readonly ?bool $newsletter = null,
    ) {}
}

$settings = SettingsDto::fromArray([
    'notifications' => false,  // Stays false (default behavior)
    'newsletter' => false,     // Becomes null (convertFalse: true)
]);

echo $settings->notifications; // false
echo $settings->newsletter;    // null
```

## Zero Values

By default, zero values are **NOT** converted to `null`. You need to explicitly enable this:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

class StatsDto extends SimpleDto
{
    public function __construct(
        // Convert integer zero to null
        #[ConvertEmptyToNull(convertZero: true)]
        public readonly ?int $count = null,

        // Convert string zero to null
        #[ConvertEmptyToNull(convertStringZero: true)]
        public readonly ?string $value = null,

        // Don't convert zero (default behavior)
        #[ConvertEmptyToNull]
        public readonly ?int $total = null,
    ) {}
}

$stats = StatsDto::fromArray([
    'count' => 0,   // Becomes null (convertZero: true)
    'value' => '0', // Becomes null (convertStringZero: true)
    'total' => 0,   // Stays 0 (default behavior)
]);

echo $stats->count; // null
echo $stats->value; // null
echo $stats->total; // 0
```

This gives you fine-grained control over which zero values should be converted.

## Combining All Options

You can combine all three optional conversions:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;

class FlexibleDto extends SimpleDto
{
    public function __construct(
        // Convert all: empty string, empty array, 0, "0", and false to null
        #[ConvertEmptyToNull(convertZero: true, convertStringZero: true, convertFalse: true)]
        public readonly mixed $value = null,
    ) {}
}

$dto1 = FlexibleDto::fromArray(['value' => '']);     // null
$dto2 = FlexibleDto::fromArray(['value' => []]);     // null
$dto3 = FlexibleDto::fromArray(['value' => 0]);      // null
$dto4 = FlexibleDto::fromArray(['value' => '0']);    // null
$dto5 = FlexibleDto::fromArray(['value' => false]);  // null
$dto6 = FlexibleDto::fromArray(['value' => true]);   // true (not converted)
```

## Combining with Other Attributes

You can combine `#[ConvertEmptyToNull]` with other attributes:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\Email;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Email]
        public readonly string $email,

        #[ConvertEmptyToNull]
        #[MapFrom('phone_number')]
        public readonly ?string $phone = null,
    ) {}
}

$user = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone_number' => '',  // Mapped from phone_number and converted to null
]);

echo $user->phone; // null
```

## Using with Casts

You can also use the cast directly in the `casts()` method:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Casts\ConvertEmptyToNullCast;

class ProfileDto extends SimpleDto
{
    protected function casts(): array
    {
        return [
            'bio' => ConvertEmptyToNullCast::class,
            'tags' => ConvertEmptyToNullCast::class,
            // With zero conversion enabled
            'count' => new ConvertEmptyToNullCast(convertZero: true),
            'value' => new ConvertEmptyToNullCast(convertStringZero: true),
            // With false conversion enabled
            'active' => new ConvertEmptyToNullCast(convertFalse: true),
            // With all conversions enabled
            'flexible' => new ConvertEmptyToNullCast(convertZero: true, convertStringZero: true, convertFalse: true),
        ];
    }

    public function __construct(
        public readonly ?string $bio = null,
        public readonly ?array $tags = null,
        public readonly ?int $count = null,
        public readonly ?string $value = null,
        public readonly ?bool $active = null,
        public readonly mixed $flexible = null,
    ) {}
}
```

## Using with DataMapper

You can also use `ConvertEmptyToNull` as a filter in DataMapper templates:

```php
use event4u\DataHelpers\DataMapper;

// Basic usage - converts "", [], null to null
$template = [
    'name' => '{{ data.name }}',
    'bio' => '{{ data.bio | empty_to_null }}',
];

// Convert integer zero (0) to null
$template = [
    'count' => '{{ data.count | empty_to_null:"zero" }}',
];

// Convert string zero ("0") to null
$template = [
    'value' => '{{ data.value | empty_to_null:"string_zero" }}',
];

// Convert both zero types to null
$template = [
    'count' => '{{ data.count | empty_to_null:"zero,string_zero" }}',
];

$result = DataMapper::source($data)->template($template)->map()->getTarget();
```

### DataMapper Filter Options

The filter accepts a comma-separated list of options:

- `"zero"` - Convert integer zero (0) to null
- `"string_zero"` - Convert string zero ("0") to null
- `"zero,string_zero"` - Convert both to null

This makes the template syntax self-documenting and easy to understand!

## See Also

- [Template Expressions](/data-helpers/advanced/template-expressions/) - DataMapper filter documentation
- [DataMapper](/data-helpers/main-classes/data-mapper/) - DataMapper guide
- [Type Casting](/data-helpers/simple-dto/type-casting/) - Other casting options


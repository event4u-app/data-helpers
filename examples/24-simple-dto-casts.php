<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DateTimeImmutable;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Casts\ArrayCast;
use event4u\DataHelpers\SimpleDTO\Casts\BooleanCast;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;

// ============================================================================
// Example 1: Built-in Casts
// ============================================================================

echo "Example 1: Built-in Casts\n";
echo str_repeat('=', 80) . "\n\n";

class UserDto extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly bool $is_active,
        public readonly array $roles,
        public readonly DateTimeImmutable $created_at,
    ) {}

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',  // Built-in cast alias
            'roles' => 'array',        // Built-in cast alias
            'created_at' => 'datetime', // Built-in cast alias
        ];
    }
}

$userData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'is_active' => '1',  // String will be cast to boolean
    'roles' => '["admin","editor"]',  // JSON string will be cast to array
    'created_at' => '2024-01-15 10:30:00',  // String will be cast to DateTimeImmutable
];

$user = UserDto::fromArray($userData);

echo sprintf('Name: %s%s', $user->name, PHP_EOL);
echo sprintf('Email: %s%s', $user->email, PHP_EOL);
echo "Is Active: " . ($user->is_active ? 'Yes' : 'No') . " (type: " . gettype($user->is_active) . ")\n";
echo "Roles: " . implode(', ', $user->roles) . " (type: " . gettype($user->roles) . ")\n";
echo "Created At: " . $user->created_at->format('Y-m-d H:i:s') . " (type: " . $user->created_at::class . ")\n";

echo "\n";

// ============================================================================
// Example 2: Custom Cast Classes
// ============================================================================

echo "Example 2: Custom Cast Classes\n";
echo str_repeat('=', 80) . "\n\n";

class ProductDto extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly bool $in_stock,
        public readonly array $tags,
        public readonly ?DateTimeImmutable $available_from = null,
    ) {}

    protected function casts(): array
    {
        return [
            'in_stock' => BooleanCast::class,
            'tags' => ArrayCast::class,
            'available_from' => DateTimeCast::class,
        ];
    }
}

$productData = [
    'name' => 'Laptop',
    'price' => 999.99,
    'in_stock' => 'yes',  // Will be cast to true
    'tags' => '["electronics","computers","sale"]',
    'available_from' => '2024-02-01 00:00:00',
];

$product = ProductDto::fromArray($productData);

echo sprintf('Product: %s%s', $product->name, PHP_EOL);
echo sprintf('Price: €%s%s', $product->price, PHP_EOL);
echo "In Stock: " . ($product->in_stock ? 'Yes' : 'No') . "\n";
echo "Tags: " . implode(', ', $product->tags) . "\n";
echo "Available From: " . $product->available_from->format('d.m.Y') . "\n";

echo "\n";

// ============================================================================
// Example 3: Cast with Parameters (Custom Date Format)
// ============================================================================

echo "Example 3: Cast with Parameters\n";
echo str_repeat('=', 80) . "\n\n";

class EventDto extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly DateTimeImmutable $event_date,
        public readonly ?DateTimeImmutable $registration_deadline = null,
    ) {}

    protected function casts(): array
    {
        return [
            'event_date' => DateTimeCast::class . ':Y-m-d',  // Custom format
            'registration_deadline' => 'datetime:d.m.Y',     // Alternative syntax
        ];
    }
}

$eventData = [
    'title' => 'Laravel Conference 2024',
    'event_date' => '2024-06-15',
    'registration_deadline' => '31.05.2024',
];

$event = EventDto::fromArray($eventData);

echo sprintf('Event: %s%s', $event->title, PHP_EOL);
echo "Date: " . $event->event_date->format('l, F j, Y') . "\n";
echo "Registration Deadline: " . $event->registration_deadline->format('d.m.Y') . "\n";

echo "\n";

// ============================================================================
// Example 4: Nested DTOs with Casts
// ============================================================================

echo "Example 4: Nested DTOs with Casts\n";
echo str_repeat('=', 80) . "\n\n";

class AddressDto extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class CompanyDto extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly bool $is_active,
        public readonly array $departments,
        public readonly DateTimeImmutable $founded_at,
        public readonly ?AddressDto $address = null,
    ) {}

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'departments' => 'array',
            'founded_at' => 'datetime:Y-m-d',
        ];
    }
}

$companyData = [
    'name' => 'Tech Corp',
    'is_active' => '1',
    'departments' => '["Engineering","Sales","Marketing"]',
    'founded_at' => '2010-03-15',
    'address' => AddressDto::fromArray([
        'street' => 'Main Street 123',
        'city' => 'Berlin',
        'country' => 'Germany',
    ]),
];

$company = CompanyDto::fromArray($companyData);

echo sprintf('Company: %s%s', $company->name, PHP_EOL);
echo "Active: " . ($company->is_active ? 'Yes' : 'No') . "\n";
echo "Departments: " . implode(', ', $company->departments) . "\n";
echo "Founded: " . $company->founded_at->format('Y') . "\n";
echo sprintf('Address: %s, %s, %s%s', $company->address->street, $company->address->city, $company->address->country, PHP_EOL);

echo "\n";

// ============================================================================
// Example 5: JSON Serialization with Casts
// ============================================================================

echo "Example 5: JSON Serialization\n";
echo str_repeat('=', 80) . "\n\n";

$json = json_encode($user, JSON_PRETTY_PRINT);
echo "User as JSON:\n{$json}\n";

echo "\n✅ All examples completed successfully!\n";


<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DateTimeImmutable;
use event4u\DataHelpers\SimpleDTO;

// ============================================================================
// Example: All Built-in Casts
// ============================================================================

echo "Example: All Built-in Casts\n";
echo str_repeat('=', 80) . "\n\n";

class ProductDto extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $sku,
        public readonly int $quantity,
        public readonly float $weight,
        public readonly string $price,
        public readonly bool $is_available,
        public readonly array $tags,
        public readonly array $metadata,
        public readonly DateTimeImmutable $created_at,
    ) {}

    protected function casts(): array
    {
        return [
            'sku' => 'string',           // Cast integer to string
            'quantity' => 'integer',     // Cast string to integer
            'weight' => 'float',         // Cast string to float
            'price' => 'decimal:2',      // Cast to decimal with 2 places
            'is_available' => 'boolean', // Cast string to boolean
            'tags' => 'array',           // Cast JSON string to array
            'metadata' => 'json',        // Cast JSON string to array
            'created_at' => 'datetime',  // Cast string to DateTimeImmutable
        ];
    }
}

// Simulate API response with various data types
$apiData = [
    'name' => 'Premium Laptop',
    'sku' => 12345,                              // Integer → String
    'quantity' => '50',                          // String → Integer
    'weight' => '2.5',                           // String → Float
    'price' => 999.9,                            // Float → Decimal String
    'is_available' => '1',                       // String → Boolean
    'tags' => '["electronics","computers"]',     // JSON String → Array
    'metadata' => '{"brand":"TechCorp","warranty":"2 years"}', // JSON String → Array
    'created_at' => '2024-01-15 10:30:00',      // String → DateTimeImmutable
];

$product = ProductDto::fromArray($apiData);

echo "Product Details:\n";
echo "----------------\n";
echo "Name: {$product->name}\n";
echo "SKU: {$product->sku} (type: " . gettype($product->sku) . ")\n";
echo "Quantity: {$product->quantity} (type: " . gettype($product->quantity) . ")\n";
echo "Weight: {$product->weight} kg (type: " . gettype($product->weight) . ")\n";
echo "Price: €{$product->price} (type: " . gettype($product->price) . ")\n";
echo "Available: " . ($product->is_available ? 'Yes' : 'No') . " (type: " . gettype($product->is_available) . ")\n";
echo "Tags: " . implode(', ', $product->tags) . " (type: " . gettype($product->tags) . ")\n";
echo "Metadata: " . json_encode($product->metadata) . " (type: " . gettype($product->metadata) . ")\n";
echo "Created: " . $product->created_at->format('Y-m-d H:i:s') . " (type: " . get_class($product->created_at) . ")\n";

echo "\n";

// ============================================================================
// Example: Type Conversions
// ============================================================================

echo "Example: Type Conversions\n";
echo str_repeat('=', 80) . "\n\n";

class ConversionDto extends SimpleDTO
{
    public function __construct(
        public readonly int $int_from_string,
        public readonly int $int_from_float,
        public readonly float $float_from_string,
        public readonly float $float_from_int,
        public readonly string $string_from_int,
        public readonly string $string_from_bool,
        public readonly bool $bool_from_string,
        public readonly bool $bool_from_int,
    ) {}

    protected function casts(): array
    {
        return [
            'int_from_string' => 'int',
            'int_from_float' => 'int',
            'float_from_string' => 'float',
            'float_from_int' => 'float',
            'string_from_int' => 'string',
            'string_from_bool' => 'string',
            'bool_from_string' => 'bool',
            'bool_from_int' => 'bool',
        ];
    }
}

$conversions = ConversionDto::fromArray([
    'int_from_string' => '42',
    'int_from_float' => 42.7,
    'float_from_string' => '99.99',
    'float_from_int' => 100,
    'string_from_int' => 12345,
    'string_from_bool' => true,
    'bool_from_string' => 'yes',
    'bool_from_int' => 1,
]);

echo "Type Conversions:\n";
echo "-----------------\n";
echo "String '42' → Integer: {$conversions->int_from_string}\n";
echo "Float 42.7 → Integer: {$conversions->int_from_float}\n";
echo "String '99.99' → Float: {$conversions->float_from_string}\n";
echo "Integer 100 → Float: {$conversions->float_from_int}\n";
echo "Integer 12345 → String: '{$conversions->string_from_int}'\n";
echo "Boolean true → String: '{$conversions->string_from_bool}'\n";
echo "String 'yes' → Boolean: " . ($conversions->bool_from_string ? 'true' : 'false') . "\n";
echo "Integer 1 → Boolean: " . ($conversions->bool_from_int ? 'true' : 'false') . "\n";

echo "\n";

// ============================================================================
// Example: Decimal Precision
// ============================================================================

echo "Example: Decimal Precision\n";
echo str_repeat('=', 80) . "\n\n";

class MoneyDto extends SimpleDTO
{
    public function __construct(
        public readonly string $amount_2_decimals,
        public readonly string $amount_4_decimals,
        public readonly string $tax_rate,
    ) {}

    protected function casts(): array
    {
        return [
            'amount_2_decimals' => 'decimal:2',
            'amount_4_decimals' => 'decimal:4',
            'tax_rate' => 'decimal:6',
        ];
    }
}

$money = MoneyDto::fromArray([
    'amount_2_decimals' => 99.9,
    'amount_4_decimals' => 0.12345,
    'tax_rate' => 0.19,
]);

echo "Decimal Precision:\n";
echo "------------------\n";
echo "99.9 with 2 decimals: {$money->amount_2_decimals}\n";
echo "0.12345 with 4 decimals: {$money->amount_4_decimals}\n";
echo "0.19 with 6 decimals: {$money->tax_rate}\n";

echo "\n";

// ============================================================================
// Example: JSON Handling
// ============================================================================

echo "Example: JSON Handling\n";
echo str_repeat('=', 80) . "\n\n";

class ConfigDto extends SimpleDTO
{
    public function __construct(
        public readonly array $settings,
        public readonly array $permissions,
    ) {}

    protected function casts(): array
    {
        return [
            'settings' => 'json',
            'permissions' => 'json',
        ];
    }
}

$config = ConfigDto::fromArray([
    'settings' => '{"theme":"dark","language":"en","notifications":true}',
    'permissions' => '{"read":true,"write":false,"admin":false}',
]);

echo "JSON Handling:\n";
echo "--------------\n";
echo "Settings: " . json_encode($config->settings, JSON_PRETTY_PRINT) . "\n";
echo "Permissions: " . json_encode($config->permissions, JSON_PRETTY_PRINT) . "\n";

echo "\n";

// ============================================================================
// Example: JSON Serialization
// ============================================================================

echo "Example: JSON Serialization\n";
echo str_repeat('=', 80) . "\n\n";

$json = json_encode($product, JSON_PRETTY_PRINT);
echo "Product as JSON:\n{$json}\n";

echo "\n✅ All examples completed successfully!\n";


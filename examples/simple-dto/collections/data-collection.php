<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;

echo "================================================================================\n";
echo "SimpleDto - DataCollection Examples\n";
echo "================================================================================\n\n";

// Example 1: Basic Collection
echo "Example 1: Basic Collection\n";
echo "----------------------------\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

$users = UserDto::collection([
    ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com'],
    ['name' => 'Jane Smith', 'age' => 25, 'email' => 'jane@example.com'],
    ['name' => 'Bob Johnson', 'age' => 35, 'email' => 'bob@example.com'],
]);

echo "Total users: " . $users->count() . "\n";
/** @phpstan-ignore-next-line unknown */
echo "First user: " . $users->first()->name . "\n";
/** @phpstan-ignore-next-line unknown */
echo "Last user: " . $users->last()->name . "\n";
echo "\n";

// Example 2: Filtering
echo "Example 2: Filtering\n";
echo "----------------------------\n";

/** @var DataCollection<SimpleDto> $adults */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$adults = $users->filter(fn(UserDto $user): bool => 30 <= $user->age);

echo "Adults (age >= 30):\n";
/** @phpstan-ignore-next-line unknown */
foreach ($adults as $user) {
    echo "  - {$user->name} ({$user->age} years)\n";
}
echo "\n";

// Example 3: Mapping
echo "Example 3: Mapping\n";
echo "----------------------------\n";

/** @var DataCollection<SimpleDto> $names */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$names = $users->map(fn(UserDto $user): string => $user->name);
/** @var DataCollection<SimpleDto> $emails */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$emails = $users->map(fn(UserDto $user): string => $user->email);

/** @phpstan-ignore-next-line unknown */
echo "Names: " . implode(', ', $names) . "\n";
/** @phpstan-ignore-next-line unknown */
echo "Emails: " . implode(', ', $emails) . "\n";
echo "\n";

// Example 4: Reducing
echo "Example 4: Reducing\n";
echo "----------------------------\n";

$totalAge = $users->reduce(
    /** @phpstan-ignore-next-line unknown */
    fn(int $carry, UserDto $user): int => $carry + $user->age,
    0
);

$averageAge = $totalAge / $users->count();

echo sprintf('Total age: %s%s', $totalAge, PHP_EOL);
echo sprintf('Average age: %s%s', $averageAge, PHP_EOL);
echo "\n";

// Example 5: Array Access
echo "Example 5: Array Access\n";
echo "----------------------------\n";

/** @phpstan-ignore-next-line unknown */
echo sprintf('User at index 0: %s%s', $users[0]->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('User at index 1: %s%s', $users[1]->name, PHP_EOL);
echo "User exists at index 0: " . (isset($users[0]) ? 'Yes' : 'No') . "\n";
echo "User exists at index 10: " . (isset($users[10]) ? 'Yes' : 'No') . "\n";
echo "\n";

// Example 6: Iteration
echo "Example 6: Iteration\n";
echo "----------------------------\n";

echo "All users:\n";
foreach ($users as $user) {
    echo "  - {$user->name} <{$user->email}>\n";
}
echo "\n";

// Example 7: Conversion to Array/JSON
echo "Example 7: Conversion to Array/JSON\n";
echo "----------------------------\n";

$array = $users->toArray();
echo "As Array:\n";
echo json_encode($array, JSON_PRETTY_PRINT) . PHP_EOL;

$json = $users->toJson();
echo "\nAs JSON:\n";
echo $json . "\n";
echo "\n";

// Example 8: Adding Items
echo "Example 8: Adding Items\n";
echo "----------------------------\n";

$newUsers = UserDto::collection([
    ['name' => 'Alice Brown', 'age' => 28, 'email' => 'alice@example.com'],
]);

$newUsers->push(['name' => 'Charlie Wilson', 'age' => 32, 'email' => 'charlie@example.com']);
$newUsers->prepend(['name' => 'David Lee', 'age' => 27, 'email' => 'david@example.com']);

echo "Users after push/prepend:\n";
foreach ($newUsers as $user) {
    echo sprintf('  - %s%s', $user->name, PHP_EOL);
}
echo "\n";

// Example 9: Complex Filtering
echo "Example 9: Complex Filtering\n";
echo "----------------------------\n";

class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly string $category,
        public readonly bool $inStock,
    ) {}
}

$products = ProductDto::collection([
    ['name' => 'Laptop', 'price' => 999.99, 'category' => 'Electronics', 'inStock' => true],
    ['name' => 'Mouse', 'price' => 29.99, 'category' => 'Electronics', 'inStock' => true],
    ['name' => 'Desk', 'price' => 299.99, 'category' => 'Furniture', 'inStock' => false],
    ['name' => 'Chair', 'price' => 199.99, 'category' => 'Furniture', 'inStock' => true],
    ['name' => 'Monitor', 'price' => 399.99, 'category' => 'Electronics', 'inStock' => true],
]);

/** @var DataCollection<SimpleDto> $availableElectronics */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$availableElectronics = $products->filter(
    /** @phpstan-ignore-next-line unknown */
    fn(ProductDto $p): bool => 'Electronics' === $p->category && $p->inStock
);

echo "Available Electronics:\n";
/** @phpstan-ignore-next-line unknown */
foreach ($availableElectronics as $product) {
    echo sprintf('  - %s: $%s%s', $product->name, $product->price, PHP_EOL);
}
echo "\n";

// Example 10: Chaining Operations
echo "Example 10: Chaining Operations\n";
echo "----------------------------\n";

$expensiveProductNames = $products
    ->filter(fn(ProductDto $p): bool => 200 < $p->price)
    ->map(fn(ProductDto $p): string => $p->name);

echo "Expensive products (> \$200):\n";
foreach ($expensiveProductNames as $name) {
    echo sprintf('  - %s%s', $name, PHP_EOL);
}
echo "\n";

// Example 11: Empty Collection
echo "Example 11: Empty Collection\n";
echo "----------------------------\n";

$emptyCollection = UserDto::collection();

echo "Is empty: " . ($emptyCollection->isEmpty() ? 'Yes' : 'No') . "\n";
echo "Is not empty: " . ($emptyCollection->isNotEmpty() ? 'Yes' : 'No') . "\n";
echo "Count: " . $emptyCollection->count() . "\n";
echo "\n";

// Example 12: Finding Items
echo "Example 12: Finding Items\n";
echo "----------------------------\n";

/** @phpstan-ignore-next-line unknown */
$youngUser = $users->first(fn(UserDto $u): bool => 30 > $u->age);
/** @phpstan-ignore-next-line unknown */
$oldUser = $users->last(fn(UserDto $u): bool => 30 <= $u->age);

echo "Youngest user (< 30): " . ($youngUser ? $youngUser->name : 'None') . "\n";
echo "Oldest user (>= 30): " . ($oldUser ? $oldUser->name : 'None') . "\n";
echo "\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";

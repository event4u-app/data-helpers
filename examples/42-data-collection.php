<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

echo "================================================================================\n";
echo "SimpleDTO - DataCollection Examples\n";
echo "================================================================================\n\n";

// Example 1: Basic Collection
echo "Example 1: Basic Collection\n";
echo "----------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

$users = UserDTO::collection([
    ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com'],
    ['name' => 'Jane Smith', 'age' => 25, 'email' => 'jane@example.com'],
    ['name' => 'Bob Johnson', 'age' => 35, 'email' => 'bob@example.com'],
]);

echo "Total users: " . $users->count() . "\n";
/** @phpstan-ignore-next-line phpstan-error */
echo "First user: " . $users->first()->name . "\n";
/** @phpstan-ignore-next-line phpstan-error */
echo "Last user: " . $users->last()->name . "\n";
echo "\n";

// Example 2: Filtering
echo "Example 2: Filtering\n";
echo "----------------------------\n";

/** @var DataCollection<SimpleDTO> $adults */
/** @phpstan-ignore-next-line phpstan-error */
$adults = $users->filter(fn(UserDTO $user): bool => 30 <= $user->age);

echo "Adults (age >= 30):\n";
/** @phpstan-ignore-next-line phpstan-error */
foreach ($adults as $user) {
    echo "  - {$user->name} ({$user->age} years)\n";
}
echo "\n";

// Example 3: Mapping
echo "Example 3: Mapping\n";
echo "----------------------------\n";

/** @var DataCollection<SimpleDTO> $names */
/** @phpstan-ignore-next-line phpstan-error */
$names = $users->map(fn(UserDTO $user): string => $user->name);
/** @var DataCollection<SimpleDTO> $emails */
/** @phpstan-ignore-next-line phpstan-error */
$emails = $users->map(fn(UserDTO $user): string => $user->email);

/** @phpstan-ignore-next-line phpstan-error */
echo "Names: " . implode(', ', $names) . "\n";
/** @phpstan-ignore-next-line phpstan-error */
echo "Emails: " . implode(', ', $emails) . "\n";
echo "\n";

// Example 4: Reducing
echo "Example 4: Reducing\n";
echo "----------------------------\n";

$totalAge = $users->reduce(
    /** @phpstan-ignore-next-line phpstan-error */
    fn(int $carry, UserDTO $user): int => $carry + $user->age,
    0
);

$averageAge = $totalAge / $users->count();

echo sprintf('Total age: %s%s', $totalAge, PHP_EOL);
echo sprintf('Average age: %s%s', $averageAge, PHP_EOL);
echo "\n";

// Example 5: Array Access
echo "Example 5: Array Access\n";
echo "----------------------------\n";

/** @phpstan-ignore-next-line phpstan-error */
echo sprintf('User at index 0: %s%s', $users[0]->name, PHP_EOL);
/** @phpstan-ignore-next-line phpstan-error */
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

$newUsers = UserDTO::collection([
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

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly string $category,
        public readonly bool $inStock,
    ) {}
}

$products = ProductDTO::collection([
    ['name' => 'Laptop', 'price' => 999.99, 'category' => 'Electronics', 'inStock' => true],
    ['name' => 'Mouse', 'price' => 29.99, 'category' => 'Electronics', 'inStock' => true],
    ['name' => 'Desk', 'price' => 299.99, 'category' => 'Furniture', 'inStock' => false],
    ['name' => 'Chair', 'price' => 199.99, 'category' => 'Furniture', 'inStock' => true],
    ['name' => 'Monitor', 'price' => 399.99, 'category' => 'Electronics', 'inStock' => true],
]);

/** @var DataCollection<SimpleDTO> $availableElectronics */
/** @phpstan-ignore-next-line phpstan-error */
$availableElectronics = $products->filter(
    /** @phpstan-ignore-next-line phpstan-error */
    fn(ProductDTO $p): false => 'Electronics' === $p->category && $p->inStock
);

echo "Available Electronics:\n";
/** @phpstan-ignore-next-line phpstan-error */
foreach ($availableElectronics as $product) {
    echo sprintf('  - %s: $%s%s', $product->name, $product->price, PHP_EOL);
}
echo "\n";

// Example 10: Chaining Operations
echo "Example 10: Chaining Operations\n";
echo "----------------------------\n";

$expensiveProductNames = $products
    ->filter(fn(ProductDTO $p): bool => 200 < $p->price)
    ->map(fn(ProductDTO $p): string => $p->name);

echo "Expensive products (> \$200):\n";
foreach ($expensiveProductNames as $name) {
    echo sprintf('  - %s%s', $name, PHP_EOL);
}
echo "\n";

// Example 11: Empty Collection
echo "Example 11: Empty Collection\n";
echo "----------------------------\n";

$emptyCollection = UserDTO::collection();

echo "Is empty: " . ($emptyCollection->isEmpty() ? 'Yes' : 'No') . "\n";
echo "Is not empty: " . ($emptyCollection->isNotEmpty() ? 'Yes' : 'No') . "\n";
echo "Count: " . $emptyCollection->count() . "\n";
echo "\n";

// Example 12: Finding Items
echo "Example 12: Finding Items\n";
echo "----------------------------\n";

/** @phpstan-ignore-next-line phpstan-error */
$youngUser = $users->first(fn(UserDTO $u): bool => 30 > $u->age);
/** @phpstan-ignore-next-line phpstan-error */
$oldUser = $users->last(fn(UserDTO $u): bool => 30 <= $u->age);

echo "Youngest user (< 30): " . ($youngUser ? $youngUser->name : 'None') . "\n";
echo "Oldest user (>= 30): " . ($oldUser ? $oldUser->name : 'None') . "\n";
echo "\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";


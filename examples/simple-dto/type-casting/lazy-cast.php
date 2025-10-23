<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDTO;

echo "=== SimpleDTO Lazy Cast Resolution ===\n\n";

// Example 1: Skip Missing Properties
echo "1. Skip Missing Properties\n";
echo "-------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $age = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
    ) {}

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'age' => 'int',
            'email' => 'string',
            'phone' => 'string',
        ];
    }
}

// Only provide 'name' and 'age', skip 'email' and 'phone'
$user = UserDTO::fromArray(['name' => 'John Doe', 'age' => '30']);

echo "Provided: name, age\n";
echo sprintf('Name: %s%s', $user->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Age: %s%s', $user->age, PHP_EOL);
echo "Email: " . ($user->email ?? 'null') . "\n";
echo "Phone: " . ($user->phone ?? 'null') . "\n\n";

// Example 2: Skip Null Values
echo "2. Skip Null Values\n";
echo "------------------\n";

$userWithNull = UserDTO::fromArray([
    'name' => 'Jane Doe',
    'age' => null,
    'email' => 'jane@example.com',
]);

echo "Provided: name (value), age (null), email (value)\n";
echo sprintf('Name: %s%s', $userWithNull->name, PHP_EOL);
echo "Age: " . ($userWithNull->age ?? 'null') . "\n";
echo "Email: {$userWithNull->email}\n\n";

// Example 3: Performance Comparison
echo "3. Performance Comparison\n";
echo "------------------------\n";

class LargeDTO extends SimpleDTO
{
    public function __construct(
        public readonly ?string $field1 = null,
        public readonly ?string $field2 = null,
        public readonly ?string $field3 = null,
        public readonly ?string $field4 = null,
        public readonly ?string $field5 = null,
        public readonly ?string $field6 = null,
        public readonly ?string $field7 = null,
        public readonly ?string $field8 = null,
        public readonly ?string $field9 = null,
        public readonly ?string $field10 = null,
    ) {}

    protected function casts(): array
    {
        return [
            'field1' => 'string',
            'field2' => 'string',
            'field3' => 'string',
            'field4' => 'string',
            'field5' => 'string',
            'field6' => 'string',
            'field7' => 'string',
            'field8' => 'string',
            'field9' => 'string',
            'field10' => 'string',
        ];
    }
}

// Scenario 1: Provide all fields
$allFieldsData = [
    'field1' => 'value1',
    'field2' => 'value2',
    'field3' => 'value3',
    'field4' => 'value4',
    'field5' => 'value5',
    'field6' => 'value6',
    'field7' => 'value7',
    'field8' => 'value8',
    'field9' => 'value9',
    'field10' => 'value10',
];

$start = microtime(true);
for ($i = 0; 1000 > $i; $i++) {
    LargeDTO::fromArray($allFieldsData);
}
$allFieldsDuration = microtime(true) - $start;

// Scenario 2: Provide only 2 fields (lazy cast resolution benefits)
$fewFieldsData = [
    'field1' => 'value1',
    'field5' => 'value5',
];

$start = microtime(true);
for ($i = 0; 1000 > $i; $i++) {
    LargeDTO::fromArray($fewFieldsData);
}
$fewFieldsDuration = microtime(true) - $start;

echo "1000 instances with all 10 fields: " . number_format($allFieldsDuration * 1000, 2) . " ms\n";
echo "1000 instances with only 2 fields: " . number_format($fewFieldsDuration * 1000, 2) . " ms\n";
echo "Speedup: " . number_format($allFieldsDuration / $fewFieldsDuration, 2) . "x\n\n";

// Example 4: Cast Statistics
echo "4. Cast Statistics\n";
echo "-----------------\n";

$partialUser = UserDTO::fromArray(['name' => 'Bob', 'age' => '25']);
$stats = $partialUser->getCastStatistics();

echo sprintf('Total properties: %d%s', $stats['total'], PHP_EOL);
echo sprintf('Casted properties: %d%s', $stats['casted'], PHP_EOL);
echo "Uncasted properties: {$stats['uncasted']}\n\n";

// Example 5: Complex Casts with Lazy Resolution
echo "5. Complex Casts with Lazy Resolution\n";
echo "-------------------------------------\n";

class ComplexDTO extends SimpleDTO
{
    /**
     * @param array<mixed>|null $data
     */
    /** @param array<mixed> $data */
    public function __construct(
        public readonly ?array $data = null,
        public readonly ?string $json = null,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?bool $active = null,
    ) {}

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'json' => 'json',
            'createdAt' => 'datetime',
            'active' => 'bool',
        ];
    }
}

// Only provide 'data' and 'active'
$complex = ComplexDTO::fromArray([
    'data' => ['key' => 'value'],
    'active' => '1',
]);

echo "Provided: data, active\n";
/** @phpstan-ignore-next-line unknown */
echo "Data: " . json_encode($complex->data) . "\n";
/** @phpstan-ignore-next-line unknown */
echo "Active: " . ($complex->active ? 'true' : 'false') . "\n";
echo "JSON: " . ($complex->json ?? 'null') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "Created At: " . ($complex->createdAt?->format('Y-m-d') ?? 'null') . "\n\n";

// Example 6: Memory Efficiency
echo "6. Memory Efficiency\n";
echo "-------------------\n";

$memoryBefore = memory_get_usage();

$instances = [];
for ($i = 0; 1000 > $i; $i++) {
    // Only provide 2 fields out of 10
    $instances[] = LargeDTO::fromArray([
        'field1' => 'value' . $i,
        'field5' => 'value' . $i,
    ]);
}

$memoryAfter = memory_get_usage();
$memoryUsed = $memoryAfter - $memoryBefore;

echo "Memory for 1000 instances (2/10 fields): " . number_format($memoryUsed / 1024, 2) . " KB\n";
echo "Memory per instance: " . number_format($memoryUsed / 1000) . " bytes\n\n";

// Example 7: Partial Updates
echo "7. Partial Updates\n";
echo "-----------------\n";

$originalUser = UserDTO::fromArray([
    'name' => 'Alice',
    'age' => '28',
    'email' => 'alice@example.com',
]);

echo "Original user:\n";
echo sprintf('  Name: %s%s', $originalUser->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('  Age: %s%s', $originalUser->age, PHP_EOL);
echo "  Email: {$originalUser->email}\n\n";

// Update only name (lazy cast resolution only applies to 'name')
$updatedUser = UserDTO::fromArray([
    'name' => 'Alice Smith',
    'age' => '28',
    'email' => 'alice@example.com',
]);

echo "Updated user (only name changed):\n";
echo sprintf('  Name: %s%s', $updatedUser->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('  Age: %s%s', $updatedUser->age, PHP_EOL);
echo "  Email: {$updatedUser->email}\n\n";

echo "=== Lazy Cast Resolution Complete ===\n";

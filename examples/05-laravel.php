<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\DataMapper;
use Illuminate\Support\Collection;

echo "=== Laravel Support Examples ===\n\n";

// Example 1: Working with Laravel Collections
echo "1. Laravel Collections\n";
echo "----------------------\n";

$collection = new Collection([
    'users' => [
        ['name' => 'John', 'email' => 'john@example.com', 'role' => 'admin'],
        ['name' => 'Jane', 'email' => 'jane@example.com', 'role' => 'user'],
        ['name' => 'Bob', 'email' => 'bob@example.com', 'role' => 'user'],
    ],
]);

$accessor = new DataAccessor($collection);
$names = $accessor->get('users.*.name');
$adminEmails = $accessor->get('users.*.email');

echo "All names: " . json_encode($names) . "\n";
echo "All emails: " . json_encode($adminEmails) . "\n";

echo "\n";

// Example 2: Working with Eloquent Models
echo "2. Eloquent Models\n";
echo "------------------\n";

// Using test model
require_once __DIR__ . '/../tests/utils/Models/User.php';

$user = new Tests\utils\Models\User([
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'role' => 'admin',
]);

$accessor = new DataAccessor($user);
echo "User name: " . $accessor->get('name') . "\n";
echo "User email: " . $accessor->get('email') . "\n";
echo "User role: " . $accessor->get('role') . "\n";

echo "\n";

// Example 3: Mutating Collections
echo "3. Mutating Collections\n";
echo "-----------------------\n";

$data = new Collection([
    'products' => [
        ['name' => 'Widget', 'price' => 10],
        ['name' => 'Gadget', 'price' => 20],
    ],
]);

$mutator = new DataMutator();
$updated = $mutator->set($data, 'products.*.discount', 0.1);

echo "Updated collection:\n";
echo json_encode($updated->all(), JSON_PRETTY_PRINT) . "\n";

echo "\n";

// Example 4: Mapping with Collections
echo "4. Mapping with Collections\n";
echo "---------------------------\n";

$sourceCollection = new Collection([
    'users' => [
        ['user_name' => 'John', 'user_email' => 'john@example.com'],
        ['user_name' => 'Jane', 'user_email' => 'jane@example.com'],
    ],
]);

$mapping = [
    'users.*.user_name' => 'people.*.name',
    'users.*.user_email' => 'people.*.email',
];

$mapper = new DataMapper();
$result = $mapper->map($sourceCollection->all(), $mapping, []);

echo "Mapped data:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

echo "\n";

// Example 5: Nested Collections
echo "5. Nested Collections\n";
echo "---------------------\n";

$nested = new Collection([
    'departments' => new Collection([
        'engineering' => new Collection([
            'name' => 'Engineering',
            'employees' => new Collection([
                ['name' => 'Alice', 'title' => 'Senior Dev'],
                ['name' => 'Bob', 'title' => 'Junior Dev'],
            ]),
        ]),
        'sales' => new Collection([
            'name' => 'Sales',
            'employees' => new Collection([
                ['name' => 'Charlie', 'title' => 'Sales Rep'],
            ]),
        ]),
    ]),
]);

$accessor = new DataAccessor($nested);
$deptNames = $accessor->get('departments.*.name');
$allEmployees = $accessor->get('departments.*.employees.*.name');

echo "Department names: " . json_encode($deptNames) . "\n";
echo "All employee names: " . json_encode($allEmployees) . "\n";

echo "\n";

// Example 6: Arrayable Interface
echo "6. Arrayable Interface\n";
echo "----------------------\n";

class CustomArrayable implements \Illuminate\Contracts\Support\Arrayable
{
    public function __construct(private array $data) {}
    
    public function toArray(): array
    {
        return $this->data;
    }
}

$arrayable = new CustomArrayable([
    'config' => [
        'app_name' => 'My App',
        'version' => '1.0.0',
    ],
]);

$accessor = new DataAccessor($arrayable);
echo "App name: " . $accessor->get('config.app_name') . "\n";
echo "Version: " . $accessor->get('config.version') . "\n";

echo "\n";

echo "=== Examples Complete ===\n";
echo "\nNote: These examples use Laravel Collections and Eloquent Models.\n";
echo "The package works with or without Laravel installed.\n";

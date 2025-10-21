<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMutator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Tests\utils\Models\User;

echo '=== Laravel Support Examples ===' . PHP_EOL . PHP_EOL;

// Example 1: Working with Laravel Collections
echo '1. Laravel Collections' . PHP_EOL;
echo '----------------------' . PHP_EOL;

$collection = new Collection([
    'users' => [
        [
            'name' => 'John',
            'email' => 'john@example.com',
            'role' => 'admin',
        ],
        [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'role' => 'user',
        ],
        [
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'role' => 'user',
        ],
    ],
]);

$accessor = new DataAccessor($collection);
$names = $accessor->get('users.*.name');
$adminEmails = $accessor->get('users.*.email');

echo 'All names: ' . json_encode($names) . PHP_EOL;
echo 'All emails: ' . json_encode($adminEmails) . PHP_EOL;

echo PHP_EOL;

// Example 2: Working with Eloquent Models
echo '2. Eloquent Models' . PHP_EOL;
echo '------------------' . PHP_EOL;

// Using test model
require_once __DIR__ . '/../tests/utils/Models/User.php';

$user = new User([
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'role' => 'admin',
]);

$accessor = new DataAccessor($user);
echo 'User name: ' . $accessor->getString('name') . PHP_EOL;
echo 'User email: ' . $accessor->getString('email') . PHP_EOL;
echo 'User role: ' . $accessor->getString('role') . PHP_EOL;

echo PHP_EOL;

// Example 3: Mutating Collections
echo '3. Mutating Collections' . PHP_EOL;
echo '-----------------------' . PHP_EOL;

$data = new Collection([
    'products' => [
        [
            'name' => 'Widget',
            'price' => 10,
        ],
        [
            'name' => 'Gadget',
            'price' => 20,
        ],
    ],
]);

$mutator = new DataMutator();
$updated = $mutator->set($data, 'products.*.discount', 0.1);

echo 'Updated collection:' . PHP_EOL;

/** @var Collection<int, mixed> $updated */
echo json_encode($updated->all(), JSON_PRETTY_PRINT) . PHP_EOL;

echo PHP_EOL;

// Example 4: Mapping with Collections
echo '4. Mapping with Collections' . PHP_EOL;
echo '---------------------------' . PHP_EOL;

$sourceCollection = new Collection([
    'users' => [
        [
            'user_name' => 'John',
            'user_email' => 'john@example.com',
        ],
        [
            'user_name' => 'Jane',
            'user_email' => 'jane@example.com',
        ],
    ],
]);

$mapping = [
    'users.*.user_name' => '{{ people.*.name }}',
    'users.*.user_email' => '{{ people.*.email }}',
];

$mapper = new DataMapper();
/** @var DataCollection<SimpleDTO> $result */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$result = $mapper->map($sourceCollection->all(), $mapping, []);

echo 'Mapped data:' . PHP_EOL;
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;

echo PHP_EOL;

// Example 5: Nested Collections
echo '5. Nested Collections' . PHP_EOL;
echo '---------------------' . PHP_EOL;

$nested = new Collection([
    'departments' => new Collection([
        'engineering' => new Collection([
            'name' => 'Engineering',
            'employees' => new Collection([
                [
                    'name' => 'Alice',
                    'title' => 'Senior Dev',
                ],
                [
                    'name' => 'Bob',
                    'title' => 'Junior Dev',
                ],
            ]),
        ]),
        'sales' => new Collection([
            'name' => 'Sales',
            'employees' => new Collection([
                [
                    'name' => 'Charlie',
                    'title' => 'Sales Rep',
                ],
            ]),
        ]),
    ]),
]);

$accessor = new DataAccessor($nested);
$deptNames = $accessor->get('departments.*.name');
$allEmployees = $accessor->get('departments.*.employees.*.name');

echo 'Department names: ' . json_encode($deptNames) . PHP_EOL;
echo 'All employee names: ' . json_encode($allEmployees) . PHP_EOL;

echo PHP_EOL;

// Example 6: Arrayable Interface
echo '6. Arrayable Interface' . PHP_EOL;
echo '----------------------' . PHP_EOL;

/**
 * @implements Arrayable<int|string, mixed>
 */
class CustomArrayable implements Arrayable
{
    /** @param array<int|string, mixed> $data */
    public function __construct(
        private readonly array $data,
    ) {}

    /** @return array<mixed> */
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
echo 'App name: ' . $accessor->getString('config.app_name') . PHP_EOL;
echo 'Version: ' . $accessor->getString('config.version') . PHP_EOL;

echo PHP_EOL;

echo '=== Examples Complete ===' . PHP_EOL . PHP_EOL;
echo 'Note: These examples use Laravel Collections and Eloquent Models.' . PHP_EOL;
echo 'The package works with or without Laravel installed.' . PHP_EOL;

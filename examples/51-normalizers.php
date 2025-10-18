<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Normalizers\CamelCaseNormalizer;
use event4u\DataHelpers\SimpleDTO\Normalizers\DefaultValuesNormalizer;
use event4u\DataHelpers\SimpleDTO\Normalizers\NormalizerInterface;
use event4u\DataHelpers\SimpleDTO\Normalizers\SnakeCaseNormalizer;
use event4u\DataHelpers\SimpleDTO\Normalizers\TypeNormalizer;

echo "================================================================================\n";
echo "SimpleDTO - Normalizers Examples\n";
echo "================================================================================\n\n";

// Example 1: TypeNormalizer - String to Int
echo "Example 1: TypeNormalizer - String to Int\n";
echo "------------------------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly bool $active,
    ) {}
}

$data = [
    'name' => 'John Doe',
    'age' => '30',  // String
    'active' => '1',  // String
];

$user = UserDTO::fromArrayWithNormalizer($data, new TypeNormalizer([
    'age' => 'int',
    'active' => 'bool',
]));

echo "Original: age='{$data['age']}' (string), active='{$data['active']}' (string)\n";
echo "Normalized: age={$user->age} (int), active=" . ($user->active ? 'true' : 'false') . " (bool)\n\n";

// Example 2: TypeNormalizer - Bool String Variations
echo "Example 2: TypeNormalizer - Bool String Variations\n";
echo "---------------------------------------------------\n";

$normalizer = new TypeNormalizer(['active' => 'bool']);

$variations = ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'];

foreach ($variations as $value) {
    $result = $normalizer->normalize(['active' => $value]);
    echo "'{$value}' -> " . ($result['active'] ? 'true' : 'false') . "\n";
}

echo "\n";

// Example 3: DefaultValuesNormalizer
echo "Example 3: DefaultValuesNormalizer\n";
echo "-----------------------------------\n";

$data = ['name' => 'Jane Smith'];

$user = UserDTO::fromArrayWithNormalizer($data, new DefaultValuesNormalizer([
    'age' => 25,
    'active' => true,
]));

echo "Original data: " . json_encode($data) . "\n";
echo "With defaults: name={$user->name}, age={$user->age}, active=" . ($user->active ? 'true' : 'false') . "\n\n";

// Example 4: SnakeCaseNormalizer
echo "Example 4: SnakeCaseNormalizer\n";
echo "-------------------------------\n";

$data = [
    'firstName' => 'Bob',
    'lastName' => 'Johnson',
    'emailAddress' => 'bob@example.com',
];

$normalizer = new SnakeCaseNormalizer();
$result = $normalizer->normalize($data);

echo "Original keys: " . implode(', ', array_keys($data)) . "\n";
echo "Normalized keys: " . implode(', ', array_keys($result)) . "\n";
echo "Values: " . json_encode($result) . "\n\n";

// Example 5: CamelCaseNormalizer
echo "Example 5: CamelCaseNormalizer\n";
echo "-------------------------------\n";

$data = [
    'first_name' => 'Alice',
    'last_name' => 'Williams',
    'email_address' => 'alice@example.com',
];

$normalizer = new CamelCaseNormalizer();
$result = $normalizer->normalize($data);

echo "Original keys: " . implode(', ', array_keys($data)) . "\n";
echo "Normalized keys: " . implode(', ', array_keys($result)) . "\n";
echo "Values: " . json_encode($result) . "\n\n";

// Example 6: Multiple Normalizers
echo "Example 6: Multiple Normalizers\n";
echo "--------------------------------\n";

$data = ['name' => 'Charlie'];

$user = UserDTO::fromArrayWithNormalizers($data, [
    new DefaultValuesNormalizer(['age' => 35, 'active' => true]),
    new TypeNormalizer(['age' => 'int', 'active' => 'bool']),
]);

echo "Original: " . json_encode($data) . "\n";
echo "After normalizers: name={$user->name}, age={$user->age}, active=" . ($user->active ? 'true' : 'false') . "\n\n";

// Example 7: Custom Normalizer
echo "Example 7: Custom Normalizer\n";
echo "-----------------------------\n";

class UppercaseNameNormalizer implements NormalizerInterface
{
    public function normalize(array $data): array
    {
        if (isset($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }

        return $data;
    }
}

$data = ['name' => 'david brown', 'age' => 40, 'active' => true];

$user = UserDTO::fromArrayWithNormalizer($data, new UppercaseNameNormalizer());

echo "Original: {$data['name']}\n";
echo "Normalized: {$user->name}\n\n";

// Example 8: Email Normalizer
echo "Example 8: Email Normalizer\n";
echo "----------------------------\n";

class EmailDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

class EmailNormalizer implements NormalizerInterface
{
    public function normalize(array $data): array
    {
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        return $data;
    }
}

$data = ['name' => 'Eve', 'email' => '  EVE@EXAMPLE.COM  '];

$user = EmailDTO::fromArrayWithNormalizer($data, new EmailNormalizer());

echo "Original email: '{$data['email']}'\n";
echo "Normalized email: '{$user->email}'\n\n";

// Example 9: Chaining Normalizers
echo "Example 9: Chaining Normalizers\n";
echo "--------------------------------\n";

$data = [
    'name' => 'frank',
    'age' => '45',
];

$user = UserDTO::fromArrayWithNormalizers($data, [
    new UppercaseNameNormalizer(),
    new DefaultValuesNormalizer(['active' => true]),
    new TypeNormalizer(['age' => 'int', 'active' => 'bool']),
]);

echo "Original: name={$data['name']}, age={$data['age']}\n";
echo "After chain: name={$user->name}, age={$user->age}, active=" . ($user->active ? 'true' : 'false') . "\n\n";

// Example 10: API Data Normalization
echo "Example 10: API Data Normalization\n";
echo "-----------------------------------\n";

// Simulate API response with snake_case and string types
$apiResponse = [
    'user_name' => 'Grace Hopper',
    'user_age' => '55',
    'is_active' => '1',
];

class ApiUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $userName,
        public readonly int $userAge,
        public readonly bool $isActive,
    ) {}
}

$user = ApiUserDTO::fromArrayWithNormalizers($apiResponse, [
    new CamelCaseNormalizer(),
    new TypeNormalizer([
        'userAge' => 'int',
        'isActive' => 'bool',
    ]),
]);

echo "API Response: " . json_encode($apiResponse) . "\n";
echo "Normalized DTO: userName={$user->userName}, userAge={$user->userAge}, isActive=" . ($user->isActive ? 'true' : 'false') . "\n\n";

// Example 11: Conditional Normalizer
echo "Example 11: Conditional Normalizer\n";
echo "-----------------------------------\n";

class AgeRangeNormalizer implements NormalizerInterface
{
    public function normalize(array $data): array
    {
        if (isset($data['age'])) {
            $age = (int) $data['age'];

            if ($age < 0) {
                $data['age'] = 0;
            } elseif ($age > 120) {
                $data['age'] = 120;
            }
        }

        return $data;
    }
}

$data1 = ['name' => 'Young', 'age' => '-5', 'active' => true];
$data2 = ['name' => 'Old', 'age' => '150', 'active' => true];

$user1 = UserDTO::fromArrayWithNormalizers($data1, [
    new TypeNormalizer(['age' => 'int']),
    new AgeRangeNormalizer(),
]);

$user2 = UserDTO::fromArrayWithNormalizers($data2, [
    new TypeNormalizer(['age' => 'int']),
    new AgeRangeNormalizer(),
]);

echo "Age -5 normalized to: {$user1->age}\n";
echo "Age 150 normalized to: {$user2->age}\n\n";

// Example 12: normalizeWith Method
echo "Example 12: normalizeWith Method\n";
echo "---------------------------------\n";

$user = UserDTO::fromArray(['name' => 'henry', 'age' => 50, 'active' => true]);

echo "Before: name={$user->name}\n";

$normalized = $user->normalizeWith(new UppercaseNameNormalizer());

echo "After: name={$normalized->name}\n";
echo "Original unchanged: name={$user->name}\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";


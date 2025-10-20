<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

echo "================================================================================\n";
echo "SimpleDTO - Wrapping Examples\n";
echo "================================================================================\n\n";

// Example 1: Basic Wrapping
echo "Example 1: Basic Wrapping\n";
echo "-------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

$user = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Without wrapping
$array = $user->toArray();
echo "Without wrapping:\n";
echo json_encode($array, JSON_PRETTY_PRINT) . "\n\n";

// With wrapping in 'data' key
$wrapped = $user->wrap('data')->toArray();
echo "Wrapped in 'data' key:\n";
echo json_encode($wrapped, JSON_PRETTY_PRINT) . "\n\n";

// Example 2: Custom Wrap Keys
echo "Example 2: Custom Wrap Keys\n";
echo "----------------------------\n";

$wrappedUser = $user->wrap('user')->toArray();
echo "Wrapped in 'user' key:\n";
echo json_encode($wrappedUser, JSON_PRETTY_PRINT) . "\n\n";

$wrappedResult = $user->wrap('result')->toArray();
echo "Wrapped in 'result' key:\n";
echo json_encode($wrappedResult, JSON_PRETTY_PRINT) . "\n\n";

// Example 3: Wrapping with JSON
echo "Example 3: Wrapping with JSON\n";
echo "------------------------------\n";

$json = json_encode($user->wrap('data'), JSON_PRETTY_PRINT);
echo "JSON with wrapping:\n";
echo $json . "\n\n";

// Example 4: Unwrapping Data
echo "Example 4: Unwrapping Data\n";
echo "--------------------------\n";

$wrappedData = [
    'data' => [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'age' => 25,
    ],
];

echo "Wrapped data:\n";
echo json_encode($wrappedData, JSON_PRETTY_PRINT) . "\n\n";

$unwrapped = UserDTO::unwrap($wrappedData, 'data');
echo "Unwrapped data:\n";
echo json_encode($unwrapped, JSON_PRETTY_PRINT) . "\n\n";

$user2 = UserDTO::fromArray($unwrapped);
echo "Created DTO from unwrapped data: {$user2->name} ({$user2->email})\n\n";

// Example 5: Wrap Key Methods
echo "Example 5: Wrap Key Methods\n";
echo "---------------------------\n";

$normalUser = UserDTO::fromArray(['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 35]);
$wrappedUser = $normalUser->wrap('data');

echo "Normal user is wrapped: " . ($normalUser->isWrapped() ? 'Yes' : 'No') . "\n";
echo "Wrapped user is wrapped: " . ($wrappedUser->isWrapped() ? 'Yes' : 'No') . "\n";
echo "Normal user wrap key: " . ($normalUser->getWrapKey() ?? 'null') . "\n";
echo "Wrapped user wrap key: " . ($wrappedUser->getWrapKey() ?? 'null') . "\n\n";

// Example 6: Immutability
echo "Example 6: Immutability\n";
echo "-----------------------\n";

$original = UserDTO::fromArray(['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 28]);
$wrapped1 = $original->wrap('data');
$wrapped2 = $original->wrap('user');

echo "Original is wrapped: " . ($original->isWrapped() ? 'Yes' : 'No') . "\n";
echo "Wrapped1 wrap key: " . $wrapped1->getWrapKey() . "\n";
echo "Wrapped2 wrap key: " . $wrapped2->getWrapKey() . "\n\n";

// Example 7: API Response Pattern
echo "Example 7: API Response Pattern\n";
echo "--------------------------------\n";

class ApiResponse
{
    public static function success(mixed $data, string $message = 'Success'): array
    {
        if ($data instanceof SimpleDTO) {
            $data = $data->wrap('data')->toArray();
        }

        return [
            'success' => true,
            'message' => $message,
            ...$data,
        ];
    }

    public static function error(string $message, int $code = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'code' => $code,
        ];
    }
}

$user = UserDTO::fromArray([
    'name' => 'Charlie',
    'email' => 'charlie@example.com',
    'age' => 40,
]);

$response = ApiResponse::success($user, 'User retrieved successfully');
echo "API Success Response:\n";
echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

$errorResponse = ApiResponse::error('User not found', 404);
echo "API Error Response:\n";
echo json_encode($errorResponse, JSON_PRETTY_PRINT) . "\n\n";

// Example 8: Wrapping Collections
echo "Example 8: Wrapping Collections\n";
echo "--------------------------------\n";

use event4u\DataHelpers\SimpleDTO\DataCollection;

$users = DataCollection::forDto(UserDTO::class, [
    ['name' => 'User 1', 'email' => 'user1@example.com', 'age' => 25],
    ['name' => 'User 2', 'email' => 'user2@example.com', 'age' => 30],
    ['name' => 'User 3', 'email' => 'user3@example.com', 'age' => 35],
]);

// Wrap each user in the collection
$wrappedUsers = $users->map(fn($user): array => $user->wrap('user')->toArray());
echo "Wrapped users in collection:\n";
echo json_encode($wrappedUsers, JSON_PRETTY_PRINT) . "\n\n";

// Example 9: Chaining Wrap with Other Methods
echo "Example 9: Chaining Wrap with Other Methods\n";
echo "--------------------------------------------\n";

$user = UserDTO::fromArray([
    'name' => 'David',
    'email' => 'david@example.com',
    'age' => 40,
]);

// Chain wrap with only/except
$wrappedFiltered = $user->only(['name', 'email'])->wrap('user')->toArray();
echo "Wrapped with only(['name', 'email']):\n";
echo json_encode($wrappedFiltered, JSON_PRETTY_PRINT) . "\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";


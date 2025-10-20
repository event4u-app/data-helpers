<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Transformers\LowercaseKeysTransformer;
use event4u\DataHelpers\SimpleDTO\Transformers\RemoveNullValuesTransformer;
use event4u\DataHelpers\SimpleDTO\Transformers\TransformerInterface;
use event4u\DataHelpers\SimpleDTO\Transformers\TransformerPipeline;
use event4u\DataHelpers\SimpleDTO\Transformers\TrimStringsTransformer;

echo "================================================================================\n";
echo "SimpleDTO - Transformers Examples\n";
echo "================================================================================\n\n";

// Example 1: TrimStringsTransformer
echo "Example 1: TrimStringsTransformer\n";
echo "----------------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

$data = [
    'name' => '  John Doe  ',
    'email' => '  john@example.com  ',
    'age' => 30,
];

$user = UserDTO::fromArrayWithTransformer($data, new TrimStringsTransformer());

echo "Original data: name='{$data['name']}', email='{$data['email']}'\n";
echo "Transformed: name='{$user->name}', email='{$user->email}'\n\n";

// Example 2: LowercaseKeysTransformer
echo "Example 2: LowercaseKeysTransformer\n";
echo "------------------------------------\n";

$data = [
    'Name' => 'Jane Smith',
    'EMAIL' => 'jane@example.com',
    'Age' => 25,
];

$user = UserDTO::fromArrayWithTransformer($data, new LowercaseKeysTransformer());

echo "Original keys: " . implode(', ', array_keys($data)) . "\n";
echo "Transformed user: {$user->name}, {$user->email}, {$user->age}\n\n";

// Example 3: RemoveNullValuesTransformer
echo "Example 3: RemoveNullValuesTransformer\n";
echo "---------------------------------------\n";

$transformer = new RemoveNullValuesTransformer();
$data = [
    'name' => 'Bob',
    'email' => null,
    'age' => 35,
];

$cleaned = $transformer->transform($data);

echo "Original data: " . json_encode($data) . "\n";
echo "Cleaned data: " . json_encode($cleaned) . "\n\n";

// Example 4: TransformerPipeline
echo "Example 4: TransformerPipeline\n";
echo "-------------------------------\n";

$pipeline = new TransformerPipeline();
$pipeline->pipe(new TrimStringsTransformer());
$pipeline->pipe(new LowercaseKeysTransformer());

$data = [
    'Name' => '  Alice Johnson  ',
    'EMAIL' => '  alice@example.com  ',
    'Age' => 28,
];

$user = UserDTO::fromArrayWithTransformer($data, $pipeline);

echo "Original: Name='{$data['Name']}', EMAIL='{$data['EMAIL']}'\n";
echo "After pipeline: name='{$user->name}', email='{$user->email}'\n\n";

// Example 5: transformWith Method
echo "Example 5: transformWith Method\n";
echo "--------------------------------\n";

$user = UserDTO::fromArray([
    'name' => '  Charlie Brown  ',
    'email' => 'charlie@example.com',
    'age' => 40,
]);

echo "Before transform: name='{$user->name}'\n";

$transformed = $user->transformWith(new TrimStringsTransformer());

echo "After transform: name='{$transformed->name}'\n";
echo "Original unchanged: name='{$user->name}'\n\n";

// Example 6: Custom Transformer
echo "Example 6: Custom Transformer\n";
echo "------------------------------\n";

class UppercaseNameTransformer implements TransformerInterface
{
    public function transform(array $data): array
    {
        if (isset($data['name'])) {
            $data['name'] = strtoupper((string) $data['name']);
        }

        return $data;
    }
}

$user = UserDTO::fromArray(['name' => 'david', 'email' => 'david@example.com', 'age' => 32]);
$transformed = $user->transformWith(new UppercaseNameTransformer());

echo sprintf('Original: %s%s', $user->name, PHP_EOL);
echo "Transformed: {$transformed->name}\n\n";

// Example 7: Chaining Custom Transformers
echo "Example 7: Chaining Custom Transformers\n";
echo "----------------------------------------\n";

class AddPrefixTransformer implements TransformerInterface
{
    public function __construct(private readonly string $prefix) {}

    public function transform(array $data): array
    {
        if (isset($data['name'])) {
            $data['name'] = $this->prefix . ' ' . $data['name'];
        }

        return $data;
    }
}

$pipeline = new TransformerPipeline();
$pipeline->pipe(new TrimStringsTransformer());
$pipeline->pipe(new UppercaseNameTransformer());
$pipeline->pipe(new AddPrefixTransformer('Mr.'));

$user = UserDTO::fromArray(['name' => '  edward  ', 'email' => 'edward@example.com', 'age' => 45]);
$transformed = $user->transformWith($pipeline);

echo "Original: '{$user->name}'\n";
echo "After pipeline: '{$transformed->name}'\n\n";

// Example 8: Email Normalizer Transformer
echo "Example 8: Email Normalizer Transformer\n";
echo "----------------------------------------\n";

class EmailNormalizerTransformer implements TransformerInterface
{
    public function transform(array $data): array
    {
        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        return $data;
    }
}

$data = [
    'name' => 'Frank',
    'email' => '  FRANK@EXAMPLE.COM  ',
    'age' => 50,
];

$user = UserDTO::fromArrayWithTransformer($data, new EmailNormalizerTransformer());

echo "Original email: '{$data['email']}'\n";
echo "Normalized email: '{$user->email}'\n\n";

// Example 9: Complex Pipeline
echo "Example 9: Complex Pipeline\n";
echo "---------------------------\n";

$pipeline = new TransformerPipeline();
$pipeline->pipe(new TrimStringsTransformer());
$pipeline->pipe(new LowercaseKeysTransformer());
$pipeline->pipe(new RemoveNullValuesTransformer());
$pipeline->pipe(new EmailNormalizerTransformer());

$data = [
    'Name' => '  Grace Hopper  ',
    'EMAIL' => '  GRACE@EXAMPLE.COM  ',
    'Age' => 55,
    'Phone' => null,
];

$user = UserDTO::fromArrayWithTransformer($data, $pipeline);

echo sprintf("Original: Name='%s', EMAIL='%s', Phone=", $data['Name'], $data['EMAIL']) . json_encode($data['Phone']) . "\n";
echo "After pipeline: name='{$user->name}', email='{$user->email}'\n\n";

// Example 10: Conditional Transformer
echo "Example 10: Conditional Transformer\n";
echo "------------------------------------\n";

class ConditionalAgeTransformer implements TransformerInterface
{
    public function transform(array $data): array
    {
        if (isset($data['age']) && 0 > $data['age']) {
            $data['age'] = 0;
        }

        if (isset($data['age']) && 120 < $data['age']) {
            $data['age'] = 120;
        }

        return $data;
    }
}

$user1 = UserDTO::fromArrayWithTransformer(
    ['name' => 'Young', 'email' => 'young@example.com', 'age' => -5],
    new ConditionalAgeTransformer()
);

$user2 = UserDTO::fromArrayWithTransformer(
    ['name' => 'Old', 'email' => 'old@example.com', 'age' => 150],
    new ConditionalAgeTransformer()
);

echo sprintf('Age -5 becomes: %s%s', $user1->age, PHP_EOL);
echo "Age 150 becomes: {$user2->age}\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";


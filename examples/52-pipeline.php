<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDTO\Normalizers\DefaultValuesNormalizer;
use event4u\DataHelpers\SimpleDTO\Normalizers\TypeNormalizer;
use event4u\DataHelpers\SimpleDTO\Pipeline\CallbackStage;
use event4u\DataHelpers\SimpleDTO\Pipeline\DTOPipeline;
use event4u\DataHelpers\SimpleDTO\Pipeline\NormalizerStage;
use event4u\DataHelpers\SimpleDTO\Pipeline\PipelineStageInterface;
use event4u\DataHelpers\SimpleDTO\Pipeline\TransformerStage;
use event4u\DataHelpers\SimpleDTO\Pipeline\ValidationStage;
use event4u\DataHelpers\SimpleDTO\Transformers\TrimStringsTransformer;

echo "================================================================================\n";
echo "SimpleDTO - Pipeline Examples\n";
echo "================================================================================\n\n";

// Example 1: Basic Pipeline
echo "Example 1: Basic Pipeline\n";
echo "-------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly bool $active,
    ) {}
}

$pipeline = new DTOPipeline();
$pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int', 'active' => 'bool'])));
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

$data = ['name' => '  John Doe  ', 'age' => '30', 'active' => '1'];
$user = UserDTO::fromArrayWithPipeline($data, $pipeline);

echo "Original: name='{$data['name']}', age='{$data['age']}', active='{$data['active']}'\n";
echo "After pipeline: name='{$user->name}', age={$user->age}, active=" . ($user->active ? 'true' : 'false') . "\n\n";

// Example 2: Pipeline with Validation
echo "Example 2: Pipeline with Validation\n";
echo "------------------------------------\n";

$pipeline = new DTOPipeline();
$pipeline->addStage(new NormalizerStage(new DefaultValuesNormalizer(['active' => true])));
$pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int', 'active' => 'bool'])));
$pipeline->addStage(new ValidationStage([
    'name' => ['required'],
    'age' => ['required', 'min:18', 'max:100'],
]));

try {
    $data = ['name' => 'Jane Smith', 'age' => 25];
    $user = UserDTO::fromArrayWithPipeline($data, $pipeline);
    echo "✅  Validation passed: {$user->name}, age {$user->age}\n";
} catch (ValidationException $e) {
    echo "❌  Validation failed: {$e->getMessage()}\n";
}

try {
    $data = ['name' => 'Too Young', 'age' => 15, 'active' => true];
    $user = UserDTO::fromArrayWithPipeline($data, $pipeline);
} catch (ValidationException $e) {
    echo "❌  Validation failed for age 15: " . implode(', ', $e->getFieldErrors('age')) . "\n";
}

echo "\n";

// Example 3: Pipeline with Default Values
echo "Example 3: Pipeline with Default Values\n";
echo "----------------------------------------\n";

$pipeline = new DTOPipeline();
$pipeline->addStage(new NormalizerStage(new DefaultValuesNormalizer(['age' => 0, 'active' => true])));
$pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int', 'active' => 'bool'])));

$data = ['name' => 'Bob'];
$user = UserDTO::fromArrayWithPipeline($data, $pipeline);

echo "Input: " . json_encode($data) . "\n";
echo "After pipeline: name={$user->name}, age={$user->age}, active=" . ($user->active ? 'true' : 'false') . "\n\n";

// Example 4: CallbackStage
echo "Example 4: CallbackStage\n";
echo "------------------------\n";

$pipeline = new DTOPipeline();
$pipeline->addStage(new CallbackStage(function(array $data): array {
    if (isset($data['name'])) {
        $data['name'] = strtoupper($data['name']);
    }

    return $data;
}, 'uppercase_name'));

$data = ['name' => 'alice', 'age' => 28, 'active' => true];
$user = UserDTO::fromArrayWithPipeline($data, $pipeline);

echo "Original: {$data['name']}\n";
echo "After callback: {$user->name}\n\n";

// Example 5: Complex Pipeline
echo "Example 5: Complex Pipeline\n";
echo "---------------------------\n";

$pipeline = new DTOPipeline();
$pipeline->addStage(new NormalizerStage(new DefaultValuesNormalizer(['active' => true]), 'defaults'));
$pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int', 'active' => 'bool']), 'types'));
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer(), 'trim'));
$pipeline->addStage(new ValidationStage(['name' => ['required'], 'age' => ['min:0']], 'validation'));

$data = ['name' => '  Charlie  ', 'age' => '35'];
$user = UserDTO::fromArrayWithPipeline($data, $pipeline);

echo "Processed: name={$user->name}, age={$user->age}, active=" . ($user->active ? 'true' : 'false') . "\n";

$context = $pipeline->getContext();
echo "Pipeline stages executed:\n";
foreach ($context as $stageName => $stageContext) {
    echo "  - {$stageName}: {$stageContext['status']}\n";
}

echo "\n";

// Example 6: Custom Pipeline Stage
echo "Example 6: Custom Pipeline Stage\n";
echo "---------------------------------\n";

class EmailNormalizerStage implements PipelineStageInterface
{
    public function process(array $data): array
    {
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        return $data;
    }

    public function getName(): string
    {
        return 'email_normalizer';
    }
}

class EmailUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

$pipeline = new DTOPipeline();
$pipeline->addStage(new EmailNormalizerStage());
$pipeline->addStage(new ValidationStage(['email' => ['required', 'email']]));

$data = ['name' => 'David', 'email' => '  DAVID@EXAMPLE.COM  '];
$user = EmailUserDTO::fromArrayWithPipeline($data, $pipeline);

echo "Original email: '{$data['email']}'\n";
echo "Normalized email: '{$user->email}'\n\n";

// Example 7: Error Handling - Stop on Error
echo "Example 7: Error Handling - Stop on Error\n";
echo "------------------------------------------\n";

$pipeline = new DTOPipeline();
$pipeline->setStopOnError(true);
$pipeline->addStage(new ValidationStage(['name' => ['required']], 'validation'));
$pipeline->addStage(new CallbackStage(fn($data) => $data, 'never_reached'));

try {
    $data = ['age' => 30];
    $pipeline->process($data);
} catch (ValidationException $e) {
    echo "❌  Pipeline stopped on validation error\n";
    $context = $pipeline->getContext();
    echo "Stages executed: " . implode(', ', array_keys($context)) . "\n";
    echo "Validation stage status: {$context['validation']['status']}\n";
}

echo "\n";

// Example 8: Error Handling - Continue on Error
echo "Example 8: Error Handling - Continue on Error\n";
echo "----------------------------------------------\n";

$pipeline = new DTOPipeline();
$pipeline->setStopOnError(false);
$pipeline->addStage(new ValidationStage(['name' => ['required']], 'validation'));
$pipeline->addStage(new CallbackStage(function($data) {
    $data['processed'] = true;

    return $data;
}, 'callback'));

$data = ['age' => 30];
$result = $pipeline->process($data);

echo "Pipeline continued despite validation error\n";
echo "Result: " . json_encode($result) . "\n";

$context = $pipeline->getContext();
echo "Validation status: {$context['validation']['status']}\n";
echo "Callback status: {$context['callback']['status']}\n\n";

// Example 9: processWith Method
echo "Example 9: processWith Method\n";
echo "------------------------------\n";

$user = UserDTO::fromArray(['name' => '  eve  ', 'age' => 40, 'active' => true]);

$pipeline = new DTOPipeline();
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));
$pipeline->addStage(new CallbackStage(function($data) {
    if (isset($data['name'])) {
        $data['name'] = ucwords($data['name']);
    }

    return $data;
}));

echo "Before: name='{$user->name}'\n";

$processed = $user->processWith($pipeline);

echo "After: name='{$processed->name}'\n";
echo "Original unchanged: name='{$user->name}'\n\n";

// Example 10: Reusable Pipeline
echo "Example 10: Reusable Pipeline\n";
echo "------------------------------\n";

$userPipeline = new DTOPipeline();
$userPipeline->addStage(new NormalizerStage(new DefaultValuesNormalizer(['active' => true])));
$userPipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int', 'active' => 'bool'])));
$userPipeline->addStage(new TransformerStage(new TrimStringsTransformer()));
$userPipeline->addStage(new ValidationStage(['name' => ['required'], 'age' => ['min:0']]));

$users = [
    ['name' => '  Frank  ', 'age' => '45'],
    ['name' => '  Grace  ', 'age' => '50'],
    ['name' => '  Henry  ', 'age' => '55'],
];

echo "Processing multiple users with same pipeline:\n";
foreach ($users as $userData) {
    $userPipeline->clearContext();
    $user = UserDTO::fromArrayWithPipeline($userData, $userPipeline);
    echo "  - {$user->name}, age {$user->age}\n";
}

echo "\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";


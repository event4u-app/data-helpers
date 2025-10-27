<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDto\Normalizers\DefaultValuesNormalizer;
use event4u\DataHelpers\SimpleDto\Normalizers\TypeNormalizer;
use event4u\DataHelpers\SimpleDto\Pipeline\CallbackStage;
use event4u\DataHelpers\SimpleDto\Pipeline\DtoPipeline;
use event4u\DataHelpers\SimpleDto\Pipeline\NormalizerStage;
use event4u\DataHelpers\SimpleDto\Pipeline\PipelineStageInterface;
use event4u\DataHelpers\SimpleDto\Pipeline\TransformerStage;
use event4u\DataHelpers\SimpleDto\Pipeline\ValidationStage;
use event4u\DataHelpers\SimpleDto\Transformers\TrimStringsTransformer;

echo "================================================================================\n";
echo "SimpleDto - Pipeline Examples\n";
echo "================================================================================\n\n";

// Example 1: Basic Pipeline
echo "Example 1: Basic Pipeline\n";
echo "-------------------------\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly bool $active,
    ) {}
}

$pipeline = new DtoPipeline();
$pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int', 'active' => 'bool'])));
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

$data = ['name' => '  John Doe  ', 'age' => '30', 'active' => '1'];
$user = UserDto::fromArrayWithPipeline($data, $pipeline);

echo "Original: name='{$data['name']}', age='{$data['age']}', active='{$data['active']}'\n";
echo sprintf(
    "After pipeline: name='%s', age=%s, active=",
    $user->name,
    /** @phpstan-ignore-next-line unknown */
    $user->age
/** @phpstan-ignore-next-line unknown */
) . ($user->active ? 'true' : 'false') . "\n\n";

// Example 2: Pipeline with Validation
echo "Example 2: Pipeline with Validation\n";
echo "------------------------------------\n";

$pipeline = new DtoPipeline();
$pipeline->addStage(new NormalizerStage(new DefaultValuesNormalizer(['active' => true])));
$pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int', 'active' => 'bool'])));
$pipeline->addStage(new ValidationStage([
    'name' => ['required'],
    'age' => ['required', 'min:18', 'max:100'],
]));

try {
    $data = ['name' => 'Jane Smith', 'age' => 25];
    $user = UserDto::fromArrayWithPipeline($data, $pipeline);
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('✅  Validation passed: %s, age %s%s', $user->name, $user->age, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('❌  Validation failed: %s%s', $validationException->getMessage(), PHP_EOL);
}

try {
    $data = ['name' => 'Too Young', 'age' => 15, 'active' => true];
    $user = UserDto::fromArrayWithPipeline($data, $pipeline);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    /** @phpstan-ignore-next-line unknown */
    echo "❌  Validation failed for age 15: " . implode(', ', $validationException->getFieldErrors('age')) . "\n";
}

echo "\n";

// Example 3: Pipeline with Default Values
echo "Example 3: Pipeline with Default Values\n";
echo "----------------------------------------\n";

$pipeline = new DtoPipeline();
$pipeline->addStage(new NormalizerStage(new DefaultValuesNormalizer(['age' => 0, 'active' => true])));
$pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int', 'active' => 'bool'])));

$data = ['name' => 'Bob'];
$user = UserDto::fromArrayWithPipeline($data, $pipeline);

echo "Input: " . json_encode($data) . "\n";
echo sprintf(
    'After pipeline: name=%s, age=%s, active=',
    $user->name,
    /** @phpstan-ignore-next-line unknown */
    $user->age
/** @phpstan-ignore-next-line unknown */
) . ($user->active ? 'true' : 'false') . "\n\n";

// Example 4: CallbackStage
echo "Example 4: CallbackStage\n";
echo "------------------------\n";

$pipeline = new DtoPipeline();
$pipeline->addStage(new CallbackStage(function(array $data): array {
    if (isset($data['name'])) {
        $data['name'] = strtoupper((string)$data['name']);
    }

    return $data;
}, 'uppercase_name'));

$data = ['name' => 'alice', 'age' => 28, 'active' => true];
$user = UserDto::fromArrayWithPipeline($data, $pipeline);

echo sprintf('Original: %s%s', $data['name'], PHP_EOL);
echo "After callback: {$user->name}\n\n";

// Example 5: Complex Pipeline
echo "Example 5: Complex Pipeline\n";
echo "---------------------------\n";

$pipeline = new DtoPipeline();
$pipeline->addStage(new NormalizerStage(new DefaultValuesNormalizer(['active' => true]), 'defaults'));
$pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int', 'active' => 'bool']), 'types'));
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer(), 'trim'));
$pipeline->addStage(new ValidationStage(['name' => ['required'], 'age' => ['min:0']], 'validation'));

$data = ['name' => '  Charlie  ', 'age' => '35'];
$user = UserDto::fromArrayWithPipeline($data, $pipeline);

echo sprintf(
    'Processed: name=%s, age=%s, active=',
    $user->name,
    /** @phpstan-ignore-next-line unknown */
    $user->age
/** @phpstan-ignore-next-line unknown */
) . ($user->active ? 'true' : 'false') . "\n";

$context = $pipeline->getContext();
echo "Pipeline stages executed:\n";
foreach ($context as $stageName => $stageContext) {
    echo sprintf('  - %s: %s%s', $stageName, $stageContext['status'], PHP_EOL);
}

echo "\n";

// Example 6: Custom Pipeline Stage
echo "Example 6: Custom Pipeline Stage\n";
echo "---------------------------------\n";

class EmailNormalizerStage implements PipelineStageInterface
{
    /**
     * @return array<mixed>
     */
    /** @param array<mixed> $data */
    public function process(array $data): array
    {
        if (isset($data['email'])) {
            $data['email'] = strtolower(trim((string)$data['email']));
        }

        return $data;
    }

    public function getName(): string
    {
        return 'email_normalizer';
    }
}

class EmailUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

$pipeline = new DtoPipeline();
$pipeline->addStage(new EmailNormalizerStage());
$pipeline->addStage(new ValidationStage(['email' => ['required', 'email']]));

$data = ['name' => 'David', 'email' => '  DAVID@EXAMPLE.COM  '];
$user = EmailUserDto::fromArrayWithPipeline($data, $pipeline);

echo "Original email: '{$data['email']}'\n";
echo "Normalized email: '{$user->email}'\n\n";

// Example 7: Error Handling - Stop on Error
echo "Example 7: Error Handling - Stop on Error\n";
echo "------------------------------------------\n";

$pipeline = new DtoPipeline();
$pipeline->setStopOnError(true);
$pipeline->addStage(new ValidationStage(['name' => ['required']], 'validation'));
$pipeline->addStage(new CallbackStage(fn($data): array => $data, 'never_reached'));

try {
    $data = ['age' => 30];
    $pipeline->process($data);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException) {
    echo "❌  Pipeline stopped on validation error\n";
    $context = $pipeline->getContext();
    echo "Stages executed: " . implode(', ', array_keys($context)) . "\n";
    echo sprintf('Validation stage status: %s%s', $context['validation']['status'], PHP_EOL);
}

echo "\n";

// Example 8: Error Handling - Continue on Error
echo "Example 8: Error Handling - Continue on Error\n";
echo "----------------------------------------------\n";

$pipeline = new DtoPipeline();
$pipeline->setStopOnError(false);
$pipeline->addStage(new ValidationStage(['name' => ['required']], 'validation'));
$pipeline->addStage(new CallbackStage(function($data): array {
    $data['processed'] = true;

    return $data;
}, 'callback'));

$data = ['age' => 30];
$result = $pipeline->process($data);

echo "Pipeline continued despite validation error\n";
echo "Result: " . json_encode($result) . "\n";

$context = $pipeline->getContext();
echo sprintf('Validation status: %s%s', $context['validation']['status'], PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Callback status: {$context['callback']['status']}\n\n";

// Example 9: processWith Method
echo "Example 9: processWith Method\n";
echo "------------------------------\n";

$user = UserDto::fromArray(['name' => '  eve  ', 'age' => 40, 'active' => true]);

$pipeline = new DtoPipeline();
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));
$pipeline->addStage(new CallbackStage(function($data): array {
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

$userPipeline = new DtoPipeline();
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
    $user = UserDto::fromArrayWithPipeline($userData, $userPipeline);
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('  - %s, age %s%s', $user->name, $user->age, PHP_EOL);
}

echo "\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";

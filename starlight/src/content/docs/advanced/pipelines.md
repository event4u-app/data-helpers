---
title: Pipelines
description: Process data through multiple stages with pipelines
---

Process data through multiple stages with pipelines.

## Introduction

Pipelines allow you to chain multiple processing stages:

- ✅ **Transformers** - Transform data
- ✅ **Normalizers** - Normalize data types
- ✅ **Validators** - Validate data
- ✅ **Custom Stages** - Create custom stages

## Basic Usage

### Creating a Pipeline

```php
use event4u\DataHelpers\SimpleDto\Pipeline\DtoPipeline;
use event4u\DataHelpers\SimpleDto\Pipeline\Stages\TransformerStage;
use event4u\DataHelpers\SimpleDto\Transformers\TrimStringsTransformer;

$pipeline = new DtoPipeline();
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

$dto = UserDto::fromArrayWithPipeline($data, $pipeline);
```

### Multiple Stages

```php
$pipeline = new DtoPipeline();
$pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int'])));
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));
$pipeline->addStage(new ValidationStage());

$dto = UserDto::fromArrayWithPipeline($data, $pipeline);
```

## Built-in Transformers

### TrimStringsTransformer

```php
use event4u\DataHelpers\SimpleDto\Transformers\TrimStringsTransformer;

$pipeline = new DtoPipeline();
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

$data = ['name' => '  John Doe  '];
$dto = UserDto::fromArrayWithPipeline($data, $pipeline);
// name: 'John Doe'
```

### LowerCaseTransformer

```php
use event4u\DataHelpers\SimpleDto\Transformers\LowerCaseTransformer;

$pipeline = new DtoPipeline();
$pipeline->addStage(new TransformerStage(new LowerCaseTransformer(['email'])));

$data = ['email' => 'JOHN@EXAMPLE.COM'];
$dto = UserDto::fromArrayWithPipeline($data, $pipeline);
// email: 'john@example.com'
```

### UpperCaseTransformer

```php
use event4u\DataHelpers\SimpleDto\Transformers\UpperCaseTransformer;

$pipeline = new DtoPipeline();
$pipeline->addStage(new TransformerStage(new UpperCaseTransformer(['code'])));

$data = ['code' => 'abc123'];
$dto = UserDto::fromArrayWithPipeline($data, $pipeline);
// code: 'ABC123'
```

## Custom Transformers

### Creating a Custom Transformer

```php
use event4u\DataHelpers\SimpleDto\Contracts\Transformer;

class SlugifyTransformer implements Transformer
{
    public function __construct(
        private array $fields = [],
    ) {}

    public function transform(array $data): array
    {
        foreach ($this->fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->slugify($data[$field]);
            }
        }

        return $data;
    }

    private function slugify(string $value): string
    {
        $slug = strtolower($value);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }
}

// Usage
$pipeline = new DtoPipeline();
$pipeline->addStage(new TransformerStage(new SlugifyTransformer(['title'])));
```

## Custom Stages

### Creating a Custom Stage

```php
use event4u\DataHelpers\SimpleDto\Contracts\PipelineStage;

class SanitizeStage implements PipelineStage
{
    public function process(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }

        return $data;
    }

    public function getName(): string
    {
        return 'sanitize';
    }
}

// Usage
$pipeline = new DtoPipeline();
$pipeline->addStage(new SanitizeStage());
```

## Real-World Examples

### User Registration Pipeline

```php
$pipeline = new DtoPipeline();

// 1. Trim all strings
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

// 2. Lowercase email
$pipeline->addStage(new TransformerStage(new LowerCaseTransformer(['email'])));

// 3. Normalize types
$pipeline->addStage(new NormalizerStage(new TypeNormalizer([
    'age' => 'int',
    'active' => 'bool',
])));

// 4. Validate
$pipeline->addStage(new ValidationStage());

$dto = UserDto::fromArrayWithPipeline($_POST, $pipeline);
```

### API Data Processing

```php
$pipeline = new DtoPipeline();

// 1. Remove null values
$pipeline->addStage(new FilterStage(fn($v) => $v !== null));

// 2. Trim strings
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

// 3. Convert dates
$pipeline->addStage(new TransformerStage(new DateTransformer([
    'created_at',
    'updated_at',
])));

$dto = ProductDto::fromArrayWithPipeline($apiResponse, $pipeline);
```

### Form Data Processing

```php
$pipeline = new DtoPipeline();

// 1. Sanitize HTML
$pipeline->addStage(new SanitizeStage());

// 2. Trim strings
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

// 3. Generate slug from title
$pipeline->addStage(new TransformerStage(new SlugifyTransformer(['slug'])));

// 4. Validate
$pipeline->addStage(new ValidationStage());

$dto = PostDto::fromArrayWithPipeline($_POST, $pipeline);
```

## Pipeline Context

### Passing Context

```php
$pipeline = new DtoPipeline();
$pipeline->setContext('user_id', auth()->id());
$pipeline->setContext('ip_address', request()->ip());

$pipeline->addStage(new AuditStage());

$dto = UserDto::fromArrayWithPipeline($data, $pipeline);
```

### Reading Context

```php
class AuditStage implements PipelineStage
{
    public function process(array $data): array
    {
        $userId = $this->getContext('user_id');
        $ipAddress = $this->getContext('ip_address');

        // Log audit trail
        AuditLog::create([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'data' => $data,
        ]);

        return $data;
    }

    public function getName(): string
    {
        return 'audit';
    }
}
```

## Error Handling

### Stop on Error

```php
$pipeline = new DtoPipeline();
$pipeline->stopOnError(true); // Default

$pipeline->addStage(new ValidationStage());

try {
    $dto = UserDto::fromArrayWithPipeline($data, $pipeline);
} catch (Exception $e) {
    // Handle error
}
```

### Continue on Error

```php
$pipeline = new DtoPipeline();
$pipeline->stopOnError(false);

$pipeline->addStage(new ValidationStage());
$pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

$dto = UserDto::fromArrayWithPipeline($data, $pipeline);

// Check for errors
$errors = $pipeline->getErrors();
```

## Best Practices

### Order Matters

```php
// ✅ Good - correct order
$pipeline->addStage(new TrimStage());        // 1. Clean data
$pipeline->addStage(new NormalizeStage());   // 2. Normalize types
$pipeline->addStage(new ValidationStage());  // 3. Validate

// ❌ Bad - wrong order
$pipeline->addStage(new ValidationStage());  // Validates dirty data
$pipeline->addStage(new TrimStage());
```

### Reusable Pipelines

```php
// ✅ Good - reusable
class UserPipeline
{
    public static function create(): DtoPipeline
    {
        $pipeline = new DtoPipeline();
        $pipeline->addStage(new TrimStage());
        $pipeline->addStage(new ValidationStage());

        return $pipeline;
    }
}

$dto = UserDto::fromArrayWithPipeline($data, UserPipeline::create());

// ❌ Bad - not reusable
$pipeline = new DtoPipeline();
$pipeline->addStage(new TrimStage());
// ... repeat everywhere
```

### Single Responsibility

```php
// ✅ Good - single responsibility
class TrimStage implements PipelineStage
{
    public function process(array $data): array
    {
        // Only trim strings
    }
}

// ❌ Bad - multiple responsibilities
class ProcessStage implements PipelineStage
{
    public function process(array $data): array
    {
        // Trim, validate, transform, etc.
    }
}
```

## See Also

- [Custom Casts](/data-helpers/advanced/custom-casts/) - Custom type casts
- [Custom Validation](/data-helpers/advanced/custom-validation/) - Custom validation rules
- [Hooks & Events](/data-helpers/advanced/hooks-events/) - Hooks and events


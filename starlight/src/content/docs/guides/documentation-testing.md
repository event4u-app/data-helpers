---
title: Documentation Testing
description: How to write and test documentation examples in Data Helpers
---

This guide explains how to write documentation with testable code examples and how to run the documentation tests.

All PHP code examples in the documentation are automatically tested to ensure they work correctly. This helps maintain high-quality documentation that stays in sync with the codebase.

## Running Documentation Tests

### Quick Start

Run all documentation tests using the Task command:

```bash
task test:documentation
```

Or using the short alias:

```bash
task test:docs
```

### What Gets Tested

The documentation tests include:
- **All Markdown files** in `starlight/src/content/docs/` (via `StarlightAllExamplesTest`)
- **README.md** examples (via `ReadmeExamplesTest`)
- **Examples directory** (`examples/`) files (via `ExamplesTest`)
- **Specific class examples** (DataAccessor, DataFilter, DataMapper tests)

### Manual Execution

You can also run the tests directly with Pest:

```bash
# Run all documentation tests (using testsuite)
docker exec data-helpers-php84 vendor/bin/pest --testsuite=Documentation

# Or using the docs group
docker exec data-helpers-php84 vendor/bin/pest --group=docs
```

With a specific PHP version:

```bash
docker exec data-helpers-php82 vendor/bin/pest --testsuite=Documentation
docker exec data-helpers-php83 vendor/bin/pest --testsuite=Documentation
```

## Writing Testable Documentation

### Basic PHP Code Blocks

Use standard PHP code blocks with the `php` language identifier:

````markdown
```php
use event4u\DataHelpers\DataAccessor;

$data = ['name' => 'John', 'age' => 30];
$accessor = new DataAccessor($data);

$name = $accessor->get('name');
// $name is 'John'
```
````

### Code Block Requirements

For code to be tested, it must:

1. **Be executable** - Not just class/interface/trait declarations
2. **Be complete** - No placeholders like `...` or `// ...`
3. **Have proper imports** - Include all necessary `use` statements
4. **Not require external dependencies** - Use only classes from the package

### Examples That Are Automatically Skipped

The test system automatically skips code blocks that contain:

- **Placeholders**: `...` or `// ...`
- **Class declarations**: `class MyClass`, `interface MyInterface`, `trait MyTrait`, `enum MyEnum`
- **Property-only code**: Only property declarations without executable code
- **Incomplete arrays**: Lines ending with `=>`
- **External dependencies**: References to `Spatie\`, `extends Model`, etc.
- **Specific files**: `architecture.md`, `contributing.md`, `migration-from-spatie.md`

## Skipping Examples

### Method 1: HTML Comment (Recommended)

Add an HTML comment before the code block:

````markdown
<!-- skip-test -->
```php
// This example will not be tested
$result = someUndefinedFunction();
```
````

### Method 2: Inline Marker

Add `skip-test` after the language identifier:

````markdown
```php skip-test
// This example will not be tested
$result = someUndefinedFunction();
```
````

### When to Skip Examples

Skip examples when:

- **Showing incomplete code** for illustration purposes
- **Demonstrating error cases** that would fail intentionally
- **Using external dependencies** not available in tests
- **Showing framework-specific code** that requires a full framework setup
- **Illustrating concepts** without executable code

## Best Practices

### 1. Make Examples Self-Contained

Each example should be complete and runnable:

```php
use event4u\DataHelpers\DataMapper;

// ✅ Good - Complete example
$result = DataMapper::source(['name' => 'John'])
    ->template(['full_name' => '{{ name }}'])
    ->map();

// ❌ Bad - Incomplete
$result = DataMapper::source($data)  // Where does $data come from?
    ->template($template)             // Where does $template come from?
    ->map();
```

### 2. Include All Imports

Always include necessary `use` statements:

```php
// ✅ Good
use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMutator;

$accessor = new DataAccessor($data);
$mutator = new DataMutator($data);

// ❌ Bad - Missing imports
$accessor = new DataAccessor($data);  // Will fail: Class not found
```

### 3. Use Assertions for Verification

Add assertions to verify the expected behavior:

```php
use event4u\DataHelpers\DataAccessor;

$data = ['name' => 'John', 'age' => 30];
$accessor = new DataAccessor($data);

$name = $accessor->get('name');
assert($name === 'John');  // Verify the result
```

### 4. Avoid External Dependencies

Don't reference classes that aren't part of the package:

```php
// ❌ Bad - External dependency
use App\Models\User;

$user = User::find(1);

// ✅ Good - Use package classes only
use event4u\DataHelpers\SimpleDto;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}
```

### 5. Keep Examples Focused

Each example should demonstrate one concept:

```php
// ✅ Good - Focused on one feature
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John']];
$accessor = new DataAccessor($data);
$name = $accessor->get('user.name');

// ❌ Bad - Too many concepts at once
use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\DataFilter;

$accessor = new DataAccessor($data);
$mutator = new DataMutator($data);
$filter = DataFilter::query($data);
// ... too much happening
```

## Troubleshooting

### Example Fails in Tests

If an example fails during testing:

1. **Check the error message** - The validation script shows detailed errors
2. **Run the example manually** - Copy the code and run it in isolation
3. **Verify imports** - Make sure all `use` statements are present
4. **Check for typos** - Variable names, method names, etc.
5. **Add skip-test** - If the example is intentionally incomplete

### Example Should Be Skipped But Isn't

If an example should be skipped but is still being tested:

1. **Add explicit skip marker** - Use `<!-- skip-test -->` or `skip-test` inline
2. **Check skip conditions** - Review the automatic skip conditions above
3. **Verify file path** - Some files are automatically skipped

### All Examples in a File Fail

If all examples in a file fail:

1. **Check file encoding** - Must be UTF-8
2. **Verify code block syntax** - Must use ` ```php ` (with backticks)
3. **Check for global issues** - Missing autoload, wrong PHP version, etc.

## Test Output

The validation script provides detailed output:

```
🔍 Validating all documentation examples...

✅ data-accessor.md: 15 executed, 3 skipped, 0 failed
✅ data-mutator.md: 12 executed, 2 skipped, 0 failed
❌ data-mapper.md: 18 executed, 4 skipped, 2 failed

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 Summary
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total files:     45
Total examples:  523
Executed:        487
Skipped:         34
Failed:          2
Success rate:    99.6%
```

## Related

- [Contributing Guide](/data-helpers/guides/contributing/) - General contribution guidelines
- [Development Setup](/data-helpers/guides/development-setup/) - Setting up your development environment
- [Test Matrix](/data-helpers/guides/test-matrix/) - Understanding the test matrix


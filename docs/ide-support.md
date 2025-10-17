# IDE Support for SimpleDTO

This document describes the IDE support features available for SimpleDTO, including autocomplete, type hints, and code navigation.

## PHPStorm / IntelliJ IDEA

### Automatic Type Inference

The `.phpstorm.meta.php` file in the root directory provides enhanced IDE support for PHPStorm and IntelliJ IDEA.

#### Static Method Return Types

The IDE will automatically infer the correct return type for static factory methods:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// IDE knows this returns UserDTO, not SimpleDTO
$user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);
$user->name; // ✅ Autocomplete works!

// IDE knows this returns UserDTO
$user = UserDTO::validateAndCreate(['name' => 'John', 'age' => 30]);

// IDE knows this returns DataCollection<UserDTO>
$users = UserDTO::collection([
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
]);
```

#### Cast Type Autocomplete

When defining casts, the IDE will suggest valid cast types:

```php
protected function casts(): array
{
    return [
        'createdAt' => 'datetime', // ✅ Autocomplete suggests: datetime, date, timestamp, etc.
        'price' => 'decimal:2',    // ✅ Autocomplete suggests: decimal, decimal:2, etc.
        'active' => 'boolean',     // ✅ Autocomplete suggests: boolean, bool
    ];
}
```

Available cast types:
- `array`
- `boolean` / `bool`
- `collection`
- `datetime` / `date`
- `decimal` / `decimal:2`
- `encrypted`
- `enum`
- `float`
- `hashed`
- `integer` / `int`
- `json`
- `string`
- `timestamp`

#### Validation Attribute Autocomplete

The IDE provides autocomplete for validation attribute parameters:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Between(18, 120)] // ✅ Autocomplete suggests common values
        public readonly int $age,
        
        #[Min(3)] // ✅ Autocomplete suggests: 0, 1, 3, 100, etc.
        public readonly string $name,
        
        #[In(['active', 'inactive'])] // ✅ Autocomplete suggests array syntax
        public readonly string $status,
    ) {}
}
```

#### Property Mapping Autocomplete

The IDE suggests common property names for mapping attributes:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('user_id')] // ✅ Autocomplete suggests: id, user_id, created_at, etc.
        public readonly int $id,
        
        #[MapFrom('user.name')] // ✅ Autocomplete suggests: user.name, user.email, etc.
        public readonly string $name,
    ) {}
}
```

#### Naming Convention Autocomplete

The IDE suggests naming conventions for input/output mapping:

```php
#[MapInputName('snake_case')] // ✅ Autocomplete suggests: snake_case, camelCase, kebab-case, PascalCase
class UserDTO extends SimpleDTO
{
    // ...
}
```

#### TypeScript Generator Autocomplete

The IDE provides autocomplete for TypeScript generator options:

```php
$generator = new TypeScriptGenerator();
$typescript = $generator->generate(
    [UserDTO::class],
    'export', // ✅ Autocomplete suggests: export, declare, ''
    [
        'includeComments' => true, // ✅ Autocomplete suggests options
        'sortProperties' => false,
    ]
);
```

### Code Navigation

#### Jump to Definition

You can jump to the definition of:
- DTO classes
- Attributes
- Cast classes
- Validation rules

```php
// Ctrl+Click (Cmd+Click on Mac) on any of these to jump to definition:
$user = UserDTO::fromArray([...]); // Jump to UserDTO
#[Email] // Jump to Email attribute
'datetime' // Jump to DateTimeCast (in casts() method)
```

#### Find Usages

You can find all usages of:
- DTO classes
- Static methods (fromArray, validateAndCreate, etc.)
- Attributes

Right-click on any symbol and select "Find Usages" (Alt+F7 / Cmd+F7).

### Type Hints

#### Generic Type Hints

The IDE understands generic types for collections:

```php
/** @var DataCollection<UserDTO> $users */
$users = UserDTO::collection([...]);

// IDE knows $user is UserDTO
foreach ($users as $user) {
    $user->name; // ✅ Autocomplete works!
}
```

#### PHPDoc Annotations

You can add PHPDoc annotations for better IDE support:

```php
class UserDTO extends SimpleDTO
{
    /**
     * @param string $name User's full name
     * @param int $age User's age (must be 18+)
     */
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}
```

## VS Code

### PHP Intelephense

For VS Code users, we recommend using [PHP Intelephense](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client).

#### Configuration

Add this to your `.vscode/settings.json`:

```json
{
    "intelephense.stubs": [
        "Core",
        "standard",
        "PDO",
        "json",
        "mbstring"
    ],
    "intelephense.completion.triggerParameterHints": true,
    "intelephense.completion.insertUseDeclaration": true,
    "intelephense.format.braces": "k&r"
}
```

#### Type Hints

Add PHPDoc annotations for better autocomplete:

```php
class UserDTO extends SimpleDTO
{
    // ...
}

// Add type hint for better autocomplete
/** @var UserDTO $user */
$user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);
$user->name; // ✅ Autocomplete works!
```

### PHP DocBlocker

Install [PHP DocBlocker](https://marketplace.visualstudio.com/items?itemName=neilbrayfield.php-docblocker) for automatic PHPDoc generation.

## PHPStan

### Configuration

SimpleDTO is fully compatible with PHPStan Level 9. Add this to your `phpstan.neon`:

```neon
parameters:
    level: 9
    paths:
        - src
        - tests
    
    # Enable strict rules
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    
    # SimpleDTO-specific rules
    ignoreErrors:
        # Ignore "new static" in SimpleDTOTrait
        - '#Unsafe usage of new static\(\)#'
```

### Generic Types

PHPStan understands generic types for collections:

```php
/**
 * @param DataCollection<UserDTO> $users
 * @return array<int, string>
 */
function getUserNames(DataCollection $users): array
{
    return $users->map(fn(UserDTO $user) => $user->name)->toArray();
}
```

## Tips & Tricks

### 1. Use Type Hints

Always add type hints for better IDE support:

```php
// ❌ Bad - IDE doesn't know the type
$user = UserDTO::fromArray($data);

// ✅ Good - IDE knows the type
/** @var UserDTO $user */
$user = UserDTO::fromArray($data);

// ✅ Better - Use static analysis
$user = UserDTO::fromArray($data);
assert($user instanceof UserDTO);
```

### 2. Use PHPDoc for Complex Types

For complex types, add PHPDoc annotations:

```php
/**
 * @param array<string, mixed> $data
 * @return DataCollection<UserDTO>
 */
function createUsers(array $data): DataCollection
{
    return UserDTO::collection($data);
}
```

### 3. Enable Strict Types

Always use strict types for better type safety:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    // ...
}
```

### 4. Use Readonly Properties

Use readonly properties for immutability:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name, // ✅ Readonly
        public readonly int $age,     // ✅ Readonly
    ) {}
}
```

### 5. Use Attributes Instead of Arrays

Use attributes for better IDE support:

```php
// ❌ Bad - No autocomplete
protected function rules(): array
{
    return [
        'email' => ['required', 'email'],
        'age' => ['required', 'integer', 'min:18'],
    ];
}

// ✅ Good - Autocomplete works
public function __construct(
    #[Required]
    #[Email]
    public readonly string $email,
    
    #[Required]
    #[Between(18, 120)]
    public readonly int $age,
) {}
```

## Troubleshooting

### IDE Not Recognizing Types

1. **Clear IDE cache**: File → Invalidate Caches / Restart
2. **Rebuild project**: Build → Rebuild Project
3. **Check `.phpstorm.meta.php`**: Make sure it's in the project root
4. **Update IDE**: Make sure you're using the latest version

### Autocomplete Not Working

1. **Check PHP version**: Make sure you're using PHP 8.2+
2. **Check composer autoload**: Run `composer dump-autoload`
3. **Check IDE settings**: Make sure PHP language level is set to 8.2+
4. **Restart IDE**: Sometimes a simple restart helps

### Type Hints Not Working

1. **Add PHPDoc annotations**: Use `@var`, `@param`, `@return`
2. **Use type declarations**: Add type hints to method parameters and return types
3. **Enable strict types**: Add `declare(strict_types=1);`
4. **Use PHPStan**: Run PHPStan to catch type errors

## Resources

- [PHPStorm Meta Files Documentation](https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html)
- [PHP Intelephense Documentation](https://intelephense.com/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [SimpleDTO Documentation](simple-dto.md)


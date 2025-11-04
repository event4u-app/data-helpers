---
title: UniqueCallback Attribute
description: Custom uniqueness validation with callback functions
---

The `#[UniqueCallback]` attribute allows you to implement **custom uniqueness validation logic** that works in LiteDto, including Plain PHP environments without framework dependencies.

## Overview

Unlike `#[Unique]` which is a marker attribute for Laravel/Symfony validators, `#[UniqueCallback]` performs actual validation in LiteDto using your custom callback function.

## Syntax

```php
#[UniqueCallback(array [ClassName::class, 'methodName'])]
```

**Parameters:**
- `$callback` - Callable array `[ClassName::class, 'methodName']` that performs the uniqueness check

## Callback Signature

```php
public static function callbackName(
    mixed $value,           // The value to validate
    string $propertyName,   // The property name
    array $allData          // All DTO data (for context, e.g., ID for updates)
): bool
```

**Returns:** `true` if value is unique, `false` if duplicate exists

## Basic Usage

### With PDO (Plain PHP)

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Validation\UniqueCallback;

class UserDto extends LiteDto
{
    private static ?PDO $pdo = null;

    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public function __construct(
        #[UniqueCallback([self::class, 'validateUniqueEmail'])]
        public readonly string $email,

        public readonly string $name,
    ) {}

    public static function validateUniqueEmail(
        mixed $value,
        string $propertyName,
        array $allData
    ): bool {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$value]);

        return $stmt->fetchColumn() === 0;
    }
}

// Usage
$pdo = new PDO('sqlite::memory:');
UserDto::setPdo($pdo);

$result = UserDto::validate(['email' => 'test@example.com', 'name' => 'John']);
if ($result->isValid()) {
    $user = UserDto::validateAndCreate(['email' => 'test@example.com', 'name' => 'John']);
}
```

### With Laravel Eloquent

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Validation\UniqueCallback;
use App\Models\User;

class UserDto extends LiteDto
{
    public function __construct(
        #[UniqueCallback([self::class, 'validateUniqueEmail'])]
        public readonly string $email,

        public readonly string $name,
    ) {}

    public static function validateUniqueEmail(
        mixed $value,
        string $propertyName,
        array $allData
    ): bool {
        return !User::where('email', $value)->exists();
    }
}
```

### With Doctrine ORM

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Validation\UniqueCallback;
use Doctrine\ORM\EntityManagerInterface;

class UserDto extends LiteDto
{
    private static ?EntityManagerInterface $em = null;

    public static function setEntityManager(EntityManagerInterface $em): void
    {
        self::$em = $em;
    }

    public function __construct(
        #[UniqueCallback([self::class, 'validateUniqueEmail'])]
        public readonly string $email,

        public readonly string $name,
    ) {}

    public static function validateUniqueEmail(
        mixed $value,
        string $propertyName,
        array $allData
    ): bool {
        $count = self::$em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.email = :email')
            ->setParameter('email', $value)
            ->getQuery()
            ->getSingleScalarResult();

        return $count === 0;
    }
}
```

## Advanced Usage

### Handling Updates (Ignore Current Record)

```php
class UserDto extends LiteDto
{
    public function __construct(
        #[UniqueCallback([self::class, 'validateUniqueEmail'])]
        public readonly string $email,

        public readonly string $name,
        public readonly ?int $id = null,
    ) {}

    public static function validateUniqueEmail(
        mixed $value,
        string $propertyName,
        array $allData
    ): bool {
        $query = 'SELECT COUNT(*) FROM users WHERE email = ?';
        $params = [$value];

        // Exclude current record when updating
        if (isset($allData['id'])) {
            $query .= ' AND id != ?';
            $params[] = $allData['id'];
        }

        $stmt = self::$pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchColumn() === 0;
    }
}

// Usage for update
$result = UserDto::validate([
    'id' => 123,
    'email' => 'john@example.com',
    'name' => 'John Doe'
]);
```

### Multiple Uniqueness Checks

```php
class ProductDto extends LiteDto
{
    public function __construct(
        #[UniqueCallback([self::class, 'validateUniqueSku'])]
        public readonly string $sku,

        #[UniqueCallback([self::class, 'validateUniqueName'])]
        public readonly string $name,
    ) {}

    public static function validateUniqueSku(mixed $value, string $propertyName, array $allData): bool
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM products WHERE sku = ?');
        $stmt->execute([$value]);
        return $stmt->fetchColumn() === 0;
    }

    public static function validateUniqueName(mixed $value, string $propertyName, array $allData): bool
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM products WHERE name = ?');
        $stmt->execute([$value]);
        return $stmt->fetchColumn() === 0;
    }
}
```

### Conditional Uniqueness

```php
class ProductDto extends LiteDto
{
    public function __construct(
        #[UniqueCallback([self::class, 'validateUniqueSku'])]
        public readonly string $sku,

        public readonly string $category,
    ) {}

    public static function validateUniqueSku(
        mixed $value,
        string $propertyName,
        array $allData
    ): bool {
        // SKU must be unique within the same category
        $stmt = self::$pdo->prepare(
            'SELECT COUNT(*) FROM products WHERE sku = ? AND category = ?'
        );
        $stmt->execute([$value, $allData['category']]);

        return $stmt->fetchColumn() === 0;
    }
}
```

## Important Notes

### Null Handling

The callback is **automatically skipped** when the value is `null`. Use `#[Required]` if you want to enforce non-null values:

```php
#[Required]
#[UniqueCallback([self::class, 'validateUniqueEmail'])]
public readonly string $email;
```

### Error Messages

Default error message: `"The {property} has already been taken."`

Custom error messages are not yet supported for callback attributes.

### Performance Considerations

- Callback validation performs database queries, which can be slow
- Consider caching or batch validation for better performance
- Use database indexes on columns being checked for uniqueness

## Comparison with #[Unique]

| Feature | #[Unique] | #[UniqueCallback] |
|---------|-----------|-------------------|
| **Works in Plain PHP** | ❌ No | ✅ Yes |
| **Works in Laravel** | ✅ Yes | ✅ Yes |
| **Works in Symfony** | ✅ Yes | ✅ Yes |
| **Validation Location** | Framework validator | LiteDto |
| **Custom Logic** | ❌ No | ✅ Yes |
| **Database Agnostic** | ❌ No | ✅ Yes |

## See Also

- [ExistsCallback](/data-helpers/attributes/validation/exists-callback/) - Check if value exists
- [FileCallback](/data-helpers/attributes/validation/file-callback/) - Custom file validation
- [Validation Attributes](/data-helpers/attributes/validation/) - All validation attributes
- [LiteDto Validation](/data-helpers/lite-dto/validation/) - Validation guide


---
title: ExistsCallback Attribute
description: Custom existence validation with callback functions
---

The `#[ExistsCallback]` attribute allows you to implement **custom existence validation logic** that works in LiteDto, including Plain PHP environments without framework dependencies.

## Overview

Unlike `#[Exists]` which is a marker attribute for Laravel/Symfony validators, `#[ExistsCallback]` performs actual validation in LiteDto using your custom callback function.

## Syntax

```php
#[ExistsCallback(array [ClassName::class, 'methodName'])]
```

**Parameters:**
- `$callback` - Callable array `[ClassName::class, 'methodName']` that performs the existence check

## Callback Signature

```php
public static function callbackName(
    mixed $value,           // The value to validate
    string $propertyName    // The property name
): bool
```

**Returns:** `true` if value exists, `false` if not found

## Basic Usage

### With PDO (Plain PHP)

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Validation\ExistsCallback;

class OrderDto extends LiteDto
{
    private static ?PDO $pdo = null;

    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public function __construct(
        #[ExistsCallback([self::class, 'validateUserExists'])]
        public readonly int $userId,

        #[ExistsCallback([self::class, 'validateProductExists'])]
        public readonly int $productId,

        public readonly int $quantity,
    ) {}

    public static function validateUserExists(mixed $value, string $propertyName): bool
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
        $stmt->execute([$value]);

        return $stmt->fetchColumn() > 0;
    }

    public static function validateProductExists(mixed $value, string $propertyName): bool
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM products WHERE id = ?');
        $stmt->execute([$value]);

        return $stmt->fetchColumn() > 0;
    }
}

// Usage
$pdo = new PDO('sqlite::memory:');
OrderDto::setPdo($pdo);

$result = OrderDto::validate([
    'userId' => 1,
    'productId' => 42,
    'quantity' => 5
]);

if ($result->isValid()) {
    $order = OrderDto::validateAndCreate([
        'userId' => 1,
        'productId' => 42,
        'quantity' => 5
    ]);
}
```

### With Laravel Eloquent

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Validation\ExistsCallback;
use App\Models\User;
use App\Models\Product;

class OrderDto extends LiteDto
{
    public function __construct(
        #[ExistsCallback([self::class, 'validateUserExists'])]
        public readonly int $userId,

        #[ExistsCallback([self::class, 'validateProductExists'])]
        public readonly int $productId,

        public readonly int $quantity,
    ) {}

    public static function validateUserExists(mixed $value, string $propertyName): bool
    {
        return User::where('id', $value)->exists();
    }

    public static function validateProductExists(mixed $value, string $propertyName): bool
    {
        return Product::where('id', $value)->exists();
    }
}
```

### With Doctrine ORM

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Validation\ExistsCallback;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Product;

class OrderDto extends LiteDto
{
    private static ?EntityManagerInterface $em = null;

    public static function setEntityManager(EntityManagerInterface $em): void
    {
        self::$em = $em;
    }

    public function __construct(
        #[ExistsCallback([self::class, 'validateUserExists'])]
        public readonly int $userId,

        #[ExistsCallback([self::class, 'validateProductExists'])]
        public readonly int $productId,

        public readonly int $quantity,
    ) {}

    public static function validateUserExists(mixed $value, string $propertyName): bool
    {
        return self::$em->find(User::class, $value) !== null;
    }

    public static function validateProductExists(mixed $value, string $propertyName): bool
    {
        return self::$em->find(Product::class, $value) !== null;
    }
}
```

## Advanced Usage

### Nullable Foreign Keys

```php
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,

        #[ExistsCallback([self::class, 'validateManagerExists'])]
        public readonly ?int $managerId = null,
    ) {}

    public static function validateManagerExists(mixed $value, string $propertyName): bool
    {
        // Null is automatically allowed by the callback attribute
        // This check is redundant but shown for clarity
        if ($value === null) {
            return true;
        }

        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
        $stmt->execute([$value]);

        return $stmt->fetchColumn() > 0;
    }
}
```

### Complex Existence Checks

```php
class ProductDto extends LiteDto
{
    public function __construct(
        public readonly string $name,

        #[ExistsCallback([self::class, 'validateRelatedProductExists'])]
        public readonly ?int $relatedProductId = null,
    ) {}

    public static function validateRelatedProductExists(mixed $value, string $propertyName): bool
    {
        if ($value === null) {
            return true;
        }

        // Check if product exists AND is active
        $stmt = self::$pdo->prepare(
            'SELECT COUNT(*) FROM products WHERE id = ? AND status = ?'
        );
        $stmt->execute([$value, 'active']);

        return $stmt->fetchColumn() > 0;
    }
}
```

### Multiple Table Checks

```php
class CommentDto extends LiteDto
{
    public function __construct(
        #[ExistsCallback([self::class, 'validateCommentableExists'])]
        public readonly int $commentableId,

        public readonly string $commentableType, // 'post' or 'product'
        public readonly string $content,
    ) {}

    public static function validateCommentableExists(mixed $value, string $propertyName): bool
    {
        // This is a simplified example - in real code, you'd need access to $allData
        // to get the commentableType

        // Check in posts table
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM posts WHERE id = ?');
        $stmt->execute([$value]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        // Check in products table
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM products WHERE id = ?');
        $stmt->execute([$value]);

        return $stmt->fetchColumn() > 0;
    }
}
```

## Important Notes

### Null Handling

The callback is **automatically skipped** when the value is `null`. Use `#[Required]` if you want to enforce non-null values:

```php
#[Required]
#[ExistsCallback([self::class, 'validateUserExists'])]
public readonly int $userId;
```

### Error Messages

Default error message: `"The selected {property} is invalid."`

Custom error messages are not yet supported for callback attributes.

### Performance Considerations

- Callback validation performs database queries, which can be slow
- Consider caching or batch validation for better performance
- Use database indexes on columns being checked for existence
- For multiple foreign keys, consider using a single query with JOINs

## Comparison with #[Exists]

| Feature | #[Exists] | #[ExistsCallback] |
|---------|-----------|-------------------|
| **Works in Plain PHP** | ❌ No | ✅ Yes |
| **Works in Laravel** | ✅ Yes | ✅ Yes |
| **Works in Symfony** | ✅ Yes | ✅ Yes |
| **Validation Location** | Framework validator | LiteDto |
| **Custom Logic** | ❌ No | ✅ Yes |
| **Database Agnostic** | ❌ No | ✅ Yes |

## See Also

- [UniqueCallback](/data-helpers/attributes/validation/unique-callback/) - Check if value is unique
- [FileCallback](/data-helpers/attributes/validation/file-callback/) - Custom file validation
- [Validation Attributes](/data-helpers/attributes/validation/) - All validation attributes
- [LiteDto Validation](/data-helpers/lite-dto/validation/) - Validation guide


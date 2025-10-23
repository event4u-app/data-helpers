---
title: Custom Casts
description: Create custom type casts for SimpleDTO
---

Create custom type casts for SimpleDTO.

## Overview

Custom casts allow you to transform data during DTO creation:

- ✅ **Implement Cast Interface** - Simple interface
- ✅ **Bidirectional** - Cast and uncast
- ✅ **Reusable** - Use across multiple DTOs
- ✅ **Type-Safe** - Full type hints

## Creating a Custom Cast

### Basic Cast

```php
use event4u\DataHelpers\SimpleDTO\Contracts\Cast;

class UpperCaseCast implements Cast
{
    public function cast(mixed $value): string
    {
        return strtoupper((string) $value);
    }
    
    public function uncast(mixed $value): string
    {
        return strtolower((string) $value);
    }
}
```

### Using the Cast

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Cast(UpperCaseCast::class)]
        public readonly string $name,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'john doe',
]);

echo $dto->name; // "JOHN DOE"
```

## Advanced Examples

### Cast with Options

```php
class TruncateCast implements Cast
{
    public function __construct(
        private int $length = 100,
        private string $suffix = '...',
    ) {}
    
    public function cast(mixed $value): string
    {
        $str = (string) $value;
        
        if (strlen($str) <= $this->length) {
            return $str;
        }
        
        return substr($str, 0, $this->length) . $this->suffix;
    }
    
    public function uncast(mixed $value): string
    {
        return (string) $value;
    }
}

// Usage
#[Cast(TruncateCast::class, length: 50, suffix: '...')]
public readonly string $description;
```

### Nullable Cast

```php
class NullableUpperCaseCast implements Cast
{
    public function cast(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        return strtoupper((string) $value);
    }
    
    public function uncast(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        return strtolower((string) $value);
    }
}
```

### Complex Cast

```php
class MoneyC ast implements Cast
{
    public function __construct(
        private string $currency = 'USD',
    ) {}
    
    public function cast(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        // Convert cents to dollars
        $amount = (int) $value / 100;
        
        return [
            'amount' => $amount,
            'currency' => $this->currency,
            'formatted' => number_format($amount, 2) . ' ' . $this->currency,
        ];
    }
    
    public function uncast(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        
        // Convert dollars to cents
        return (int) ($value['amount'] * 100);
    }
}

// Usage
#[Cast(MoneyCast::class, currency: 'EUR')]
public readonly array $price;
```

## Real-World Examples

### Phone Number Cast

```php
class PhoneNumberCast implements Cast
{
    public function cast(mixed $value): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', (string) $value);
        
        // Format as (XXX) XXX-XXXX
        if (strlen($phone) === 10) {
            return sprintf('(%s) %s-%s',
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6, 4)
            );
        }
        
        return $phone;
    }
    
    public function uncast(mixed $value): string
    {
        return preg_replace('/[^0-9]/', '', (string) $value);
    }
}
```

### JSON Cast

```php
class JsonCast implements Cast
{
    public function cast(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        
        return [];
    }
    
    public function uncast(mixed $value): string
    {
        return json_encode($value);
    }
}
```

### Slug Cast

```php
class SlugCast implements Cast
{
    public function cast(mixed $value): string
    {
        $slug = strtolower((string) $value);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    public function uncast(mixed $value): string
    {
        return (string) $value;
    }
}
```

### Color Cast

```php
class ColorCast implements Cast
{
    public function cast(mixed $value): array
    {
        $hex = ltrim((string) $value, '#');
        
        return [
            'hex' => '#' . $hex,
            'rgb' => [
                'r' => hexdec(substr($hex, 0, 2)),
                'g' => hexdec(substr($hex, 2, 2)),
                'b' => hexdec(substr($hex, 4, 2)),
            ],
        ];
    }
    
    public function uncast(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        
        return $value['hex'];
    }
}
```

## Best Practices

### Type Hints

```php
// ✅ Good - specific return type
public function cast(mixed $value): string

// ❌ Bad - mixed return type
public function cast(mixed $value): mixed
```

### Null Handling

```php
// ✅ Good - handle null
public function cast(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }
    
    return strtoupper((string) $value);
}

// ❌ Bad - no null handling
public function cast(mixed $value): string
{
    return strtoupper((string) $value); // Error if null
}
```

### Validation

```php
// ✅ Good - validate input
public function cast(mixed $value): string
{
    if (!is_string($value) && !is_numeric($value)) {
        throw new InvalidArgumentException('Value must be string or numeric');
    }
    
    return (string) $value;
}
```

## See Also

- [Type Casting](/simple-dto/type-casting/) - Built-in casts
- [Custom Validation](/advanced/custom-validation/) - Custom validation rules
- [Custom Attributes](/advanced/custom-attributes/) - Custom attributes


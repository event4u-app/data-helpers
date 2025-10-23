---
title: Custom Attributes
description: Create custom PHP attributes for DTOs
---

Create custom PHP attributes for DTOs.

## Introduction

Custom attributes extend DTO functionality:

- ✅ **Metadata** - Add metadata to properties
- ✅ **Behavior** - Modify DTO behavior
- ✅ **Validation** - Custom validation logic
- ✅ **Transformation** - Transform data

## Creating Custom Attributes

### Basic Attribute

```php
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Description
{
    public function __construct(
        public readonly string $text,
    ) {}
}
```

### Using the Attribute

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Description('User full name')]
        public readonly string $name,
        
        #[Description('User email address')]
        public readonly string $email,
    ) {}
}
```

## Advanced Examples

### Metadata Attribute

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiField
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $required = false,
        public readonly ?string $example = null,
    ) {}
}

// Usage
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[ApiField('user_name', 'Full name of the user', required: true, example: 'John Doe')]
        public readonly string $name,
    ) {}
}
```

### Transformation Attribute

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class Transform
{
    public function __construct(
        public readonly string $transformer,
    ) {}
}

// Usage
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Transform('trim')]
        public readonly string $name,
        
        #[Transform('strtolower')]
        public readonly string $email,
    ) {}
}
```

### Conditional Attribute

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class ShowIf
{
    public function __construct(
        public readonly string $condition,
    ) {}
}

// Usage
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[ShowIf('isAdmin')]
        public readonly ?string $adminNotes = null,
    ) {}
}
```

## Real-World Examples

### Database Column Mapping

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $type = null,
        public readonly bool $nullable = false,
    ) {}
}

// Usage
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Column('user_name', type: 'varchar', nullable: false)]
        public readonly string $name,
        
        #[Column('user_email', type: 'varchar', nullable: false)]
        public readonly string $email,
    ) {}
}
```

### API Documentation

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiProperty
{
    public function __construct(
        public readonly string $description,
        public readonly ?string $example = null,
        public readonly ?array $enum = null,
        public readonly ?string $format = null,
    ) {}
}

// Usage
class ProductDTO extends SimpleDTO
{
    public function __construct(
        #[ApiProperty('Product name', example: 'iPhone 15')]
        public readonly string $name,
        
        #[ApiProperty('Product price in cents', example: 99900, format: 'int32')]
        public readonly int $price,
        
        #[ApiProperty('Product status', enum: ['active', 'inactive', 'draft'])]
        public readonly string $status,
    ) {}
}
```

### Audit Trail

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class Auditable
{
    public function __construct(
        public readonly bool $logChanges = true,
        public readonly ?string $label = null,
    ) {}
}

// Usage
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Auditable(label: 'User Name')]
        public readonly string $name,
        
        #[Auditable(label: 'Email Address')]
        public readonly string $email,
        
        #[Auditable(logChanges: false)]
        public readonly ?string $avatar = null,
    ) {}
}
```

### Searchable Fields

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class Searchable
{
    public function __construct(
        public readonly int $weight = 1,
        public readonly bool $exact = false,
    ) {}
}

// Usage
class ProductDTO extends SimpleDTO
{
    public function __construct(
        #[Searchable(weight: 10, exact: false)]
        public readonly string $name,
        
        #[Searchable(weight: 5)]
        public readonly string $description,
        
        #[Searchable(weight: 3, exact: true)]
        public readonly string $sku,
    ) {}
}
```

### Encryption

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class Encrypted
{
    public function __construct(
        public readonly string $algorithm = 'AES-256-CBC',
    ) {}
}

// Usage
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Encrypted]
        public readonly string $ssn,
        
        #[Encrypted(algorithm: 'AES-128-CBC')]
        public readonly string $creditCard,
    ) {}
}
```

## Reading Attributes

### Using Reflection

```php
use ReflectionClass;
use ReflectionProperty;

$reflection = new ReflectionClass(UserDTO::class);

foreach ($reflection->getProperties() as $property) {
    $attributes = $property->getAttributes(Description::class);
    
    foreach ($attributes as $attribute) {
        $instance = $attribute->newInstance();
        echo "{$property->getName()}: {$instance->text}\n";
    }
}
```

### Helper Method

```php
class AttributeReader
{
    public static function getPropertyAttributes(
        string $class,
        string $property,
        string $attributeClass
    ): array {
        $reflection = new ReflectionClass($class);
        $prop = $reflection->getProperty($property);
        $attributes = $prop->getAttributes($attributeClass);
        
        return array_map(
            fn($attr) => $attr->newInstance(),
            $attributes
        );
    }
}

// Usage
$descriptions = AttributeReader::getPropertyAttributes(
    UserDTO::class,
    'name',
    Description::class
);
```

## Combining Attributes

### Multiple Attributes

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Min(3)]
        #[Max(50)]
        #[Description('User full name')]
        #[ApiField('user_name', required: true)]
        #[Searchable(weight: 10)]
        public readonly string $name,
    ) {}
}
```

### Attribute Groups

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class UserField
{
    public function __construct(
        public readonly string $description,
        public readonly bool $required = false,
        public readonly bool $searchable = false,
        public readonly int $searchWeight = 1,
    ) {}
}

// Usage
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[UserField('User full name', required: true, searchable: true, searchWeight: 10)]
        public readonly string $name,
    ) {}
}
```

## Best Practices

### Clear Naming

```php
// ✅ Good - clear name
#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiField

// ❌ Bad - vague name
#[Attribute(Attribute::TARGET_PROPERTY)]
class Field
```

### Readonly Properties

```php
// ✅ Good - readonly
public function __construct(
    public readonly string $name,
) {}

// ❌ Bad - mutable
public function __construct(
    public string $name,
) {}
```

### Type Hints

```php
// ✅ Good - type hints
public function __construct(
    public readonly string $name,
    public readonly int $weight,
) {}

// ❌ Bad - no type hints
public function __construct(
    public readonly $name,
    public readonly $weight,
) {}
```

## See Also

- [Custom Casts](/advanced/custom-casts/) - Custom type casts
- [Custom Validation](/advanced/custom-validation/) - Custom validation rules
- [Attributes Overview](/attributes/overview/) - Built-in attributes


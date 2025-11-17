---
title: HasObject & HasDto Attributes
description: Link DTOs to plain PHP objects for bidirectional conversion
---

Link DTOs to plain PHP objects for seamless bidirectional conversion.

:::tip[Framework-Specific Alternatives]
If you're using **Laravel** or **Symfony/Doctrine**, use the framework-specific attributes instead:

- **Laravel**: Use [`#[HasModel]`](/data-helpers/framework-integration/laravel/) for Eloquent models
- **Symfony/Doctrine**: Use [`#[HasEntity]`](/data-helpers/framework-integration/doctrine/) for Doctrine entities

These provide better integration with framework features like relationships, lazy loading, and ORM-specific functionality.
:::

## Introduction

The `HasObject` and `HasDto` attributes enable seamless integration between SimpleDtos and plain PHP objects (like Zend Framework models, legacy classes, or any plain PHP objects).

**Use Cases:**
- ✅ Legacy PHP applications
- ✅ Zend Framework models
- ✅ Plain PHP classes without ORM
- ✅ Simple data objects
- ✅ Value objects
- ✅ Custom framework models (non-Laravel, non-Doctrine)

## HasObject Attribute

The `#[HasObject]` attribute links a DTO to a plain PHP object class, enabling automatic conversion.

### Basic Usage

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasObject;

// Plain PHP object
class Product
{
    public int $id;
    public string $name;
    public float $price;
}

// DTO with HasObject attribute
#[HasObject(Product::class)]
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
    ) {}
}

// Object → DTO
$product = new Product();
$product->id = 1;
$product->name = 'Laptop';
$product->price = 999.99;
$dto = ProductDto::fromObject($product);

// DTO → Object (uses HasObject attribute)
$newProduct = $dto->toObject();
```

:::tip[No Manual Trait Import Needed]
The `SimpleDtoObjectTrait` is **automatically included** in `SimpleDto`. You don't need to manually import it!

The same applies to framework-specific traits:
- `SimpleDtoEloquentTrait` - **Always included**, methods throw `BadMethodCallException` if Laravel is not installed
- `SimpleDtoDoctrineTrait` - **Always included**, methods throw `BadMethodCallException` if Doctrine is not installed
- `SimpleDtoObjectTrait` - **Always included** (no framework required)

**How it works:**
- All traits are always available at runtime
- Methods check if the required framework is installed when called
- If framework is missing, a clear `BadMethodCallException` is thrown with installation instructions
:::

### Without Attribute

You can also specify the class explicitly:

```php
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
    ) {}
}

// Specify class explicitly
$dto = ProductDto::fromObject($product);
$newProduct = $dto->toObject(Product::class);
```

## HasDto Attribute

The `#[HasDto]` attribute links a plain PHP object to a DTO class, enabling conversion from the object side.

### Basic Usage

```php
use event4u\DataHelpers\SimpleDto\Attributes\HasDto;
use event4u\DataHelpers\Traits\ObjectMappingTrait;

class ProductDto extends SimpleDto
{
    // No need to use SimpleDtoObjectTrait - it's automatically included!

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
    ) {}
}

// Plain object with HasDto attribute
#[HasDto(ProductDto::class)]
class Product
{
    use ObjectMappingTrait;

    public int $id;
    public string $name;
    public float $price;
}

$product = new Product();
$product->id = 1;
$product->name = 'Laptop';
$product->price = 999.99;

// Convert to DTO using attribute
$dto = $product->toDto();

// Or specify DTO class explicitly
$dto = $product->toDto(AdminProductDto::class);
```

## Working with Getters and Setters

Both attributes support objects with getter and setter methods:

### Object with Getters

```php
class Customer
{
    private int $id;
    private string $name;
    private string $email;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}

#[HasObject(Customer::class)]
class CustomerDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

$customer = new Customer();
$customer->setId(42);
$customer->setName('John Doe');
$customer->setEmail('john@example.com');

// fromObject() automatically uses getters
$dto = CustomerDto::fromObject($customer);

// toObject() automatically uses setters
$newCustomer = $dto->toObject();
```

### Supported Getter Patterns

The integration automatically detects these getter patterns:

- `getId()` → `id`
- `getName()` → `name`
- `isActive()` → `active`
- `hasPermission()` → `permission`

### Supported Setter Patterns

The integration automatically detects these setter patterns:

- `setId(int $id)` → `id`
- `setName(string $name)` → `name`

## Round-Trip Conversion

Data is preserved during round-trip conversions:

```php
// Original object
$originalProduct = new Product();
$originalProduct->id = 1;
$originalProduct->name = 'Laptop';
$originalProduct->price = 999.99;

// Object → DTO → Object
$dto = ProductDto::fromObject($originalProduct);
$newProduct = $dto->toObject();

// Data is preserved
assert($newProduct->id === $originalProduct->id);
assert($newProduct->name === $originalProduct->name);
assert($newProduct->price === $originalProduct->price);
```

## Error Handling

Both attributes throw `InvalidArgumentException` in these cases:

- Object/DTO class does not exist
- No class provided and no attribute found
- DTO class does not have `fromArray()` method

```php
use event4u\DataHelpers\Traits\ObjectMappingTrait;

class MyObject
{
    use ObjectMappingTrait;

    public string $name = 'Test';
}

$object = new MyObject();

try {
    $dto = $object->toDto();
} catch (InvalidArgumentException $e) {
    // Handle error: No DTO class provided and no #[HasDto] attribute found
}
```

## Framework-Specific Integration

### Laravel (Eloquent Models)

For Laravel applications, use `#[HasModel]` instead:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;

#[HasModel(User::class)]
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// Eloquent Model → DTO
$user = User::find(1);
$dto = UserDto::fromModel($user);

// DTO → Eloquent Model
$newUser = $dto->toModel();
$newUser->save();
```

**Advantages over HasObject:**
- ✅ Automatic relationship handling (hasMany, belongsTo, etc.)
- ✅ Lazy loading support
- ✅ Eloquent-specific features (timestamps, soft deletes, etc.)
- ✅ Integration with Laravel's validation
- ✅ Query builder integration

📖 **[Complete Laravel Integration Guide](/data-helpers/framework-integration/laravel/)**

### Symfony/Doctrine (Entities)

For Symfony applications with Doctrine, use `#[HasEntity]` instead:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasEntity;

#[HasEntity(User::class)]
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// Doctrine Entity → DTO
$user = $entityManager->find(User::class, 1);
$dto = UserDto::fromEntity($user);

// DTO → Doctrine Entity
$newUser = $dto->toEntity();
$entityManager->persist($newUser);
$entityManager->flush();
```

**Advantages over HasObject:**
- ✅ Automatic relationship handling (OneToMany, ManyToOne, etc.)
- ✅ Doctrine Collections support
- ✅ Lazy loading with proxies
- ✅ Entity lifecycle events
- ✅ Integration with Symfony's validation
- ✅ EntityManager integration

📖 **[Complete Doctrine Integration Guide](/data-helpers/framework-integration/doctrine/)**

## LiteDto Support

All attributes and traits are also available for **LiteDto**:

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\HasObject;

#[HasObject(Product::class)]
class ProductDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
    ) {}
}

// Usage
$product = new Product();
$product->name = 'Laptop';
$product->price = 999.99;

$dto = ProductDto::fromObject($product);
$object = $dto->toObject(); // Uses HasObject attribute
```

:::tip[No Manual Trait Import Needed]
Framework integration traits are **automatically included** in `LiteDto`. You don't need to manually import them!

- `LiteDtoObjectTrait` - **Always included** (no framework required)
- `LiteDtoEloquentTrait` - **Always included**, methods throw `BadMethodCallException` if Laravel is not installed
- `LiteDtoDoctrineTrait` - **Always included**, methods throw `BadMethodCallException` if Doctrine is not installed

**How it works:**
- All traits are always available at runtime
- Methods check if the required framework is installed when called
- If framework is missing, a clear `BadMethodCallException` is thrown with installation instructions
:::

**Available LiteDto Attributes:**
- `#[HasObject]` - Links DTO to plain PHP object
- `#[HasModel]` - Links DTO to Laravel Eloquent Model
- `#[HasEntity]` - Links DTO to Symfony Doctrine Entity
- `#[HasDto]` - Links object/model/entity to DTO

## Comparison with Framework Integration

| Feature | Plain Object | Eloquent Model | Doctrine Entity |
|---------|-------------|----------------|-----------------|
| DTO Trait | `SimpleDtoObjectTrait` | `SimpleDtoEloquentTrait` | `SimpleDtoDoctrineTrait` |
| Object Trait | `ObjectMappingTrait` | `DtoMappingTrait` | `DtoMappingTrait` |
| Attribute | `HasObject` | `HasModel` | `HasEntity` |
| Dependencies | None | Laravel/Eloquent | Symfony/Doctrine |
| Use Case | Plain PHP | Laravel apps | Symfony apps |
| Relationships | ❌ Manual | ✅ Automatic | ✅ Automatic |
| Lazy Loading | ✅ Yes | ✅ Yes | ✅ Yes |
| Collections | ✅ DataCollections | ✅ DataCollections | ✅ DataCollections |
| ORM Features | ❌ No | ✅ Full Eloquent | ✅ Full Doctrine |
| Auto-Included | ✅ Always | ✅ Always (runtime check) | ✅ Always (runtime check) |
| Runtime Check | ❌ No | ✅ Yes | ✅ Yes |

## Best Practices

### Use Attributes

Define `HasObject` or `HasDto` attributes to avoid passing class names:

```php
// ✅ Good: Uses attribute
#[HasObject(Product::class)]
class ProductDto extends SimpleDto
{
    // No need to use SimpleDtoObjectTrait - it's automatically included!
    // ...
}

$product = $dto->toObject(); // No class needed

// ❌ Bad: Always passing class
$product = $dto->toObject(Product::class);
```

### Consistent Naming

Use consistent property names between objects and DTOs:

```php
// ✅ Good: Same property names
class Product
{
    public int $id;
    public string $name;
}

class ProductDto extends SimpleDto
{
    public readonly int $id;
    public readonly string $name;
}

// ❌ Bad: Different property names
class Product
{
    public int $productId;
    public string $productName;
}

class ProductDto extends SimpleDto
{
    public readonly int $id;
    public readonly string $name;
}
```

### Type Safety

Use readonly properties in DTOs for immutability:

```php
// ✅ Good: Readonly properties
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

// ❌ Bad: Mutable properties
class ProductDto extends SimpleDto
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
```

## See Also

- [Plain PHP Integration](/data-helpers/framework-integration/plain-php/) - Complete guide
- [HasModel Attribute](/data-helpers/framework-integration/laravel/) - Laravel integration
- [HasEntity Attribute](/data-helpers/framework-integration/doctrine/) - Doctrine integration
- [Mapping Attributes](/data-helpers/attributes/mapping/) - Property mapping


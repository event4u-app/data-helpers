# Basic Usage

Learn the core concepts and basic usage patterns of SimpleDTO.

---

## 🎯 Creating a DTO

### Basic DTO Structure

```php
<?php

use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}
```

**Key Points:**
- ✅ Extend `SimpleDTO` base class
- ✅ Use `readonly` properties for immutability
- ✅ Use constructor property promotion (PHP 8.0+)
- ✅ Type hint all properties

---

## 📥 Creating Instances

### From Array

```php
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);
```

### From JSON

```php
$json = '{"name":"John Doe","email":"john@example.com","age":30}';
$dto = UserDTO::fromJson($json);
```

### From Constructor

```php
$dto = new UserDTO(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);
```

### From Request (Laravel)

```php
// In controller
public function store(Request $request)
{
    $dto = UserDTO::fromRequest($request);
}
```

### From Model (Laravel)

```php
$user = User::find(1);
$dto = UserDTO::fromModel($user);
```

### From Entity (Symfony)

```php
$user = $entityManager->find(User::class, 1);
$dto = UserDTO::fromEntity($user);
```

---

## 📤 Converting to Other Formats

### To Array

```php
$array = $dto->toArray();
// ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30]
```

### To JSON

```php
$json = $dto->toJson();
// {"name":"John Doe","email":"john@example.com","age":30}

// Pretty print
$json = $dto->toJson(JSON_PRETTY_PRINT);
```

### To XML

```php
$xml = $dto->toXml();
// <?xml version="1.0"?>
// <UserDTO>
//   <name>John Doe</name>
//   <email>john@example.com</email>
//   <age>30</age>
// </UserDTO>
```

### To YAML

```php
$yaml = $dto->toYaml();
// name: John Doe
// email: john@example.com
// age: 30
```

### To CSV

```php
$csv = $dto->toCsv();
// "name","email","age"
// "John Doe","john@example.com",30
```

---

## 🔄 Property Access

### Reading Properties

```php
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

echo $dto->name;  // John Doe
echo $dto->email; // john@example.com
echo $dto->age;   // 30
```

### Immutability

```php
// ❌ This will throw an error
$dto->name = 'Jane Doe';
// Error: Cannot modify readonly property

// ✅ Create a new instance instead
$newDto = new UserDTO(
    name: 'Jane Doe',
    email: $dto->email,
    age: $dto->age
);
```

---

## 🎨 Optional Properties

### Using Nullable Types

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,  // Optional
        public readonly ?int $age = null,       // Optional
    ) {}
}

// With optional properties
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+1234567890',
    // age is omitted
]);

echo $dto->phone; // +1234567890
echo $dto->age;   // null
```

### Default Values

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $role = 'user',      // Default value
        public readonly bool $active = true,        // Default value
        public readonly array $permissions = [],    // Default value
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    // role, active, permissions use defaults
]);

echo $dto->role;   // user
echo $dto->active; // true
print_r($dto->permissions); // []
```

---

## 📦 Nested DTOs

### Single Nested DTO

```php
class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly AddressDTO $address,
    ) {}
}

// Create with nested data
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'country' => 'USA',
    ],
]);

echo $dto->address->city; // New York
```

### Array of Nested DTOs

```php
class OrderItemDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $product,
        public readonly int $quantity,
        public readonly float $price,
    ) {}
}

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        /** @var OrderItemDTO[] */
        public readonly array $items,
    ) {}
}

$dto = OrderDTO::fromArray([
    'orderId' => 123,
    'items' => [
        ['product' => 'Widget', 'quantity' => 2, 'price' => 9.99],
        ['product' => 'Gadget', 'quantity' => 1, 'price' => 19.99],
    ],
]);
```

---

## 🔍 Checking Property Existence

### Using isset()

```php
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => null,
]);

isset($dto->name);  // true
isset($dto->phone); // false (null)
isset($dto->age);   // false (doesn't exist)
```

### Using property_exists()

```php
property_exists($dto, 'name');  // true
property_exists($dto, 'phone'); // true
property_exists($dto, 'age');   // false
```

---

## 🎯 Working with Arrays

### Get All Properties

```php
$array = $dto->toArray();
```

### Get Only Specific Properties

```php
$array = $dto->only(['name', 'email']);
// ['name' => 'John Doe', 'email' => 'john@example.com']
```

### Exclude Specific Properties

```php
$array = $dto->except(['age']);
// ['name' => 'John Doe', 'email' => 'john@example.com']
```

---

## 🔄 Cloning and Modifying

### Clone with Changes

```php
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Create a modified copy
$newDto = new UserDTO(
    name: 'Jane Doe',  // Changed
    email: $dto->email,
    age: $dto->age
);
```

---

## 🎨 Magic Methods

### __toString()

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
    
    public function __toString(): string
    {
        return $this->toJson();
    }
}

$dto = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);
echo $dto; // {"name":"John","email":"john@example.com"}
```

### JsonSerializable

```php
$dto = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);

// Automatically uses jsonSerialize()
echo json_encode($dto);
// {"name":"John","email":"john@example.com"}
```

---

## 💡 Best Practices

### 1. Use Type Hints

```php
// ✅ Good
public readonly string $name

// ❌ Bad
public readonly $name
```

### 2. Use Readonly Properties

```php
// ✅ Good
public readonly string $name

// ❌ Bad
public string $name
```

### 3. Use Named Arguments

```php
// ✅ Good - clear and explicit
$dto = new UserDTO(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);

// ❌ Bad - unclear order
$dto = new UserDTO('John Doe', 'john@example.com', 30);
```

### 4. Use Nullable Types for Optional Properties

```php
// ✅ Good
public readonly ?string $phone = null

// ❌ Bad
public readonly string $phone = ''
```

### 5. Document Array Types

```php
// ✅ Good
/** @var string[] */
public readonly array $tags

// ❌ Bad
public readonly array $tags
```

---

## 📚 Next Steps

Now that you understand the basics, explore more advanced features:

1. [Creating DTOs](05-creating-dtos.md) - Different creation methods
2. [Type Casting](06-type-casting.md) - Automatic type conversion
3. [Validation](07-validation.md) - Validate your data
4. [Property Mapping](08-property-mapping.md) - Map property names
5. [Conditional Properties](10-conditional-properties.md) - Dynamic properties

---

**Previous:** [Quick Start](03-quick-start.md)  
**Next:** [Creating DTOs](05-creating-dtos.md)


# DataMapper Integration

Learn how to integrate DataMapper functionality directly into your DTOs with templates and filters.

---

## 🎯 What is DataMapper Integration?

DataMapper Integration allows you to use DataMapper's powerful template and filter system directly within your DTOs, with the following mapping priority:

1. **Template** (highest priority) - defined in `template()` method or passed as parameter
2. **Attributes** (#[MapFrom], #[MapTo]) - fallback if no template
3. **Automapping** - fallback if no template and no attributes

---

## 📋 Quick Start

### Basic Template Definition

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseEmails;

class UserDTO extends SimpleDTO
{
    /**
     * Define DataMapper template in DTO.
     * Template has HIGHEST priority!
     */
    protected function mapperTemplate(): array
    {
        return [
            'id' => '{{ user.id }}',
            'name' => '{{ user.full_name | trim | ucfirst }}',
            'email' => '{{ user.email | lower }}',
        ];
    }

    /**
     * Define DataMapper property filters in DTO.
     * Applied to specific properties via setFilters().
     */
    protected function mapperFilters(): array
    {
        return [
            'name' => new TrimFilter(),
            'email' => new LowercaseFilter(),
        ];
    }

    /**
     * Define DataMapper pipeline filters in DTO.
     * Applied globally to all values via pipe().
     */
    protected function mapperPipeline(): array
    {
        return [
            new TrimStrings(),
            new LowercaseEmails(),
        ];
    }

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// API response with nested structure
$apiResponse = [
    'user' => [
        'id' => 1,
        'full_name' => '  john doe  ',
        'email' => 'JOHN@EXAMPLE.COM',
    ],
];

// Create DTO - uses template automatically
$user = UserDTO::fromSource($apiResponse);
// or
$user = UserDTO::fromArray($apiResponse);

echo $user->name;  // "John doe" (trimmed, ucfirst)
echo $user->email; // "john@example.com" (lowercased)
```

---

## 📚 The Three DataMapper Concepts

### 1. Template (`mapperTemplate()`)

**Purpose:** Define mapping rules with template expressions.

**Syntax:** `{{ path.to.value | filter1 | filter2 }}`

**Priority:** Highest - overrides attributes and automapping

**Return Type:** `array<string, mixed>|null` (associative array)

**Example:**
```php
protected function mapperTemplate(): array
{
    return [
        'id' => '{{ user.id }}',
        'name' => '{{ user.full_name | trim | ucfirst }}',
        'email' => '{{ user.email | lower }}',
        'age' => '{{ user.profile.age }}',
    ];
}
```

**Features:**
- Dot notation for nested data: `{{ user.profile.name }}`
- Multiple filters per expression: `{{ value | trim | ucfirst }}`
- Default values: `{{ user.name | default:"Unknown" }}`
- Multiple expressions: `{{ user.first_name }} {{ user.last_name }}`

---

### 2. Property Filters (`mapperFilters()`)

**Purpose:** Apply filters to specific properties.

**Type:** Associative array `['property' => filter]`

**Applied via:** `setFilters()` method on DataMapper

**Return Type:** `array<string, FilterInterface|array<int, FilterInterface>>|null`

**Example:**
```php
protected function mapperFilters(): array
{
    return [
        'name' => new TrimFilter(),
        'email' => new LowercaseFilter(),
        'phone' => [new TrimFilter(), new FormatPhoneFilter()],  // Multiple filters
    ];
}
```

**Use Cases:**
- Property-specific transformations
- Multiple filters per property (array of filters)
- Fine-grained control over individual fields

**Difference from Pipeline:**
- Property filters target **specific properties**
- Pipeline filters apply to **all values**

---

### 3. Pipeline Filters (`mapperPipeline()`)

**Purpose:** Apply filters globally to all values.

**Type:** Numeric array `[filter1, filter2]`

**Applied via:** `pipe()` method on DataMapper

**Return Type:** `array<int, FilterInterface>|null`

**Example:**
```php
protected function mapperPipeline(): array
{
    return [
        new TrimStrings(),
        new LowercaseEmails(),
        new RemoveNullValues(),
    ];
}
```

**Use Cases:**
- Global transformations
- Data cleanup (trim, lowercase, etc.)
- Consistent formatting across all properties

**Difference from Property Filters:**
- Pipeline filters apply to **all values**
- Property filters target **specific properties**

---

## 🔄 Processing Order

When creating a DTO with `fromSource()`, the following steps are executed:

1. **Get Template** - Parameter > DTO definition (`mapperTemplate()`)
2. **Get Property Filters** - Parameter > DTO definition (`mapperFilters()`)
3. **Get Pipeline Filters** - DTO definition (`mapperPipeline()`) + Parameter (merged)
4. **Apply DataMapper** - Template, property filters, and pipeline filters
5. **Convert to Array** - If source is string/object
6. **Apply Attributes** - #[MapFrom] attributes (skipped if template was used)
7. **Apply Casts** - Type casting via `casts()` method
8. **Auto-Validate** - If enabled
9. **Wrap Lazy Properties** - Lazy loading support
10. **Wrap Optional Properties** - Optional support
11. **Construct DTO** - Create instance with mapped data

---

## 🗺️ Mapping Priority

### Priority Order

1. **Template** (highest priority)
2. **Attributes** (#[MapFrom], #[MapTo])
3. **Automapping** (fallback)

### Example: Template Wins

```php
class ProductDTO extends SimpleDTO
{
    protected function template(): array
    {
        return [
            'id' => '{{ product.product_id }}',  // Template wins!
            'name' => '{{ product.title }}',     // Template wins!
        ];
    }

    public function __construct(
        #[MapFrom('id')]  // This is ignored because template exists!
        public readonly int $id,

        #[MapFrom('product_name')]  // This is ignored because template exists!
        public readonly string $name,
    ) {}
}

$data = [
    'product' => [
        'product_id' => 123,
        'title' => 'Laptop',
    ],
    // These are ignored because template exists:
    'id' => 999,
    'product_name' => 'Wrong Name',
];

$product = ProductDTO::fromSource($data);
echo $product->id;   // 123 (from template)
echo $product->name; // "Laptop" (from template)
```

---

## 📝 Template Syntax

### Simple Mapping

```php
protected function template(): array
{
    return [
        'id' => '{{ user.id }}',
        'name' => '{{ user.name }}',
    ];
}
```

### With Filters

```php
protected function template(): array
{
    return [
        'name' => '{{ user.name | trim | ucfirst }}',
        'email' => '{{ user.email | lower }}',
        'slug' => '{{ post.title | slug }}',
    ];
}
```

### Dot Notation for Nested Data

```php
protected function template(): array
{
    return [
        'author' => '{{ post.author.name }}',
        'city' => '{{ user.address.city }}',
    ];
}
```

### Default Values

```php
protected function template(): array
{
    return [
        'name' => "{{ user.name ?? 'Unknown' }}",
        'age' => '{{ user.age ?? 18 }}',
    ];
}
```

---

## 🔧 Filter Definition

### Built-in Filters

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseEmails;

protected function filters(): array
{
    return [
        new TrimStrings(),
        new LowercaseEmails(),
    ];
}
```

### Custom Filters

```php
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

class UppercaseFilter implements FilterInterface
{
    public function apply(mixed $value, string $key): mixed
    {
        return is_string($value) ? strtoupper($value) : $value;
    }
}

protected function filters(): array
{
    return [
        new UppercaseFilter(),
    ];
}
```

---

## 🚀 Usage

### Method 1: fromSource()

```php
// With template from DTO
$dto = UserDTO::fromSource($apiResponse);

// With dynamic template override
$dto = UserDTO::fromSource($apiResponse, [
    'id' => '{{ user.id }}',
    'name' => '{{ user.custom_name }}',
]);

// With property filters (setFilters)
$dto = UserDTO::fromSource($apiResponse, null, [
    'name' => new TrimFilter(),
    'email' => new LowercaseFilter(),
]);

// With pipeline filters (pipe)
$dto = UserDTO::fromSource($apiResponse, null, null, [
    new TrimStrings(),
    new LowercaseEmails(),
]);

// All together
$dto = UserDTO::fromSource(
    $apiResponse,
    ['id' => '{{ user.id }}'],           // Template
    ['name' => new TrimFilter()],        // Property filters
    [new LowercaseEmails()]              // Pipeline filters
);
```

### Method 2: fromArray() (Alias)

```php
// fromArray() is an alias for fromSource()
$dto = UserDTO::fromArray($apiResponse);

// With template override
$dto = UserDTO::fromArray($apiResponse, [
    'id' => '{{ user.id }}',
]);

// With all parameters
$dto = UserDTO::fromArray(
    $apiResponse,
    ['id' => '{{ user.id }}'],           // Template
    ['name' => new TrimFilter()],        // Property filters
    [new LowercaseEmails()]              // Pipeline filters
);
```

---

## 💡 Best Practices

### 1. Use Templates for Complex Mappings

**Good:**
```php
protected function mapperTemplate(): array
{
    return [
        'fullName' => '{{ user.first_name }} {{ user.last_name }}',
        'email' => '{{ user.email | lower }}',
    ];
}
```

**Why:** Templates handle nested data and transformations elegantly.

---

### 2. Use Property Filters for Specific Transformations

**Good:**
```php
protected function mapperFilters(): array
{
    return [
        'email' => new LowercaseFilter(),
        'phone' => new FormatPhoneFilter(),
    ];
}
```

**Why:** Property filters give fine-grained control over individual fields.

---

### 3. Use Pipeline Filters for Global Cleanup

**Good:**
```php
protected function mapperPipeline(): array
{
    return [
        new TrimStrings(),
        new RemoveNullValues(),
    ];
}
```

**Why:** Pipeline filters ensure consistent data cleanup across all properties.

---

### 4. Combine All Three for Maximum Control

**Good:**
```php
class UserDTO extends SimpleDTO
{
    // Template for complex mapping
    protected function mapperTemplate(): array
    {
        return [
            'id' => '{{ user.id }}',
            'name' => '{{ user.first_name }} {{ user.last_name }}',
        ];
    }

    // Property filters for specific fields
    protected function mapperFilters(): array
    {
        return [
            'email' => new LowercaseFilter(),
        ];
    }

    // Pipeline for global cleanup
    protected function mapperPipeline(): array
    {
        return [
            new TrimStrings(),
        ];
    }
}
```

**Why:** Each concept serves a specific purpose and they work together seamlessly.

---

### 5. Override Dynamically When Needed

**Good:**
```php
// Default behavior
$user = UserDTO::fromSource($apiResponse);

// Override for specific case
$user = UserDTO::fromSource(
    $apiResponse,
    ['name' => '{{ user.custom_name }}'],  // Different template
    ['email' => new CustomEmailFilter()],  // Different filter
    [new CustomPipeline()]                 // Additional pipeline
);
```

**Why:** Flexibility for edge cases without modifying the DTO class.

---

## 📊 Complete Example

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;

class BlogPostDTO extends SimpleDTO
{
    protected function mapperTemplate(): array
    {
        return [
            'id' => '{{ post.id }}',
            'title' => '{{ post.title | trim | ucfirst }}',
            'slug' => '{{ post.slug | lower }}',
            'author' => '{{ post.author.name | trim }}',
            'published' => '{{ post.published_at }}',
        ];
    }

    protected function mapperPipeline(): array
    {
        return [
            new TrimStrings(),
        ];
    }

    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $author,
        public readonly ?string $published = null,
    ) {}
}

$blogData = [
    'post' => [
        'id' => 1,
        'title' => '  my first post  ',
        'slug' => 'MY-FIRST-POST',
        'author' => [
            'name' => '  John Doe  ',
        ],
        'published_at' => '2024-01-15',
    ],
];

$post = BlogPostDTO::fromSource($blogData);

echo $post->title;  // "My first post" (trimmed, ucfirst)
echo $post->slug;   // "my-first-post" (lowercased)
echo $post->author; // "John Doe" (trimmed)
```

---

## 🔄 Fallback Behavior

### No Template → Use Attributes

```php
class CustomerDTO extends SimpleDTO
{
    // No template() method defined!
    // Attributes will be used instead.

    public function __construct(
        #[MapFrom('customer_id')]
        public readonly int $id,

        #[MapFrom('customer_name')]
        public readonly string $name,
    ) {}
}

$data = [
    'customer_id' => 1,
    'customer_name' => 'Jane Doe',
];

$customer = CustomerDTO::fromArray($data);
// Uses #[MapFrom] attributes
```

### No Template, No Attributes → Automapping

```php
class SimpleUserDTO extends SimpleDTO
{
    // No template() method
    // No #[MapFrom] attributes
    // Uses automapping!

    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

$data = [
    'id' => 1,
    'name' => 'Bob Smith',
];

$user = SimpleUserDTO::fromArray($data);
// Uses automapping (property names match array keys)
```

---

## ⚙️ Advanced Features

### Dynamic Template Override

```php
$defaultDto = OrderDTO::fromSource($data);

$customTemplate = [
    'id' => '{{ order.id }}',
    'total' => '{{ order.total }}',
    'status' => '{{ order_status }}',  // Add extra field!
];

$customDto = OrderDTO::fromSource($data, $customTemplate);
```

### Combining with Other Features

```php
class UserDTO extends SimpleDTO
{
    protected function template(): array
    {
        return [
            'id' => '{{ user.id }}',
            'name' => '{{ user.name }}',
        ];
    }

    public function __construct(
        public readonly int $id,
        public readonly string $name,

        #[WhenAuth]  // Conditional visibility
        public readonly ?string $email = null,
    ) {}
}
```

---

## 📚 See Also

- [Property Mapping](08-property-mapping.md) - #[MapFrom], #[MapTo] attributes
- [DataMapper Documentation](../data-mapper.md) - Full DataMapper guide
- [Template Expressions](../template-expressions.md) - Template syntax reference
- [Pipeline Filters](../data-mapper-pipeline.md) - Available filters

---

**[← Back to Documentation](README.md)**


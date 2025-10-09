# Data Transformers

Data transformers are pipeline components that modify values during the mapping process. They can be used with `DataMapper::pipe()`, in `MappedDataModel::pipes()`, or as filters in template expressions.

## Quick Overview

Transformers can be used in two ways:

1. **Pipeline Mode**: Apply transformations to all mapped values
2. **Template Expression Mode**: Apply transformations to specific fields using aliases

**Pipeline Example:**
```php
DataMapper::pipe([TrimStrings::class, LowercaseStrings::class])
    ->map($source, $target, $mapping);
```

**Template Expression Example:**
```php
$template = [
    'name' => '{{ user.name | trim | upper }}',
    'count' => '{{ items | count }}',
];
```

## Available Transformers

### String Transformers

#### TrimStrings
Removes whitespace from the beginning and end of all string values.

**Template Aliases:** `trim`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;

// Pipeline usage
protected function pipes(): array
{
    return [TrimStrings::class];
}

// Template expression usage
$template = ['name' => '{{ user.name | trim }}'];
```

**Example:**
- Input: `"  hello  "` → Output: `"hello"`

---

#### LowercaseStrings
Converts all string values to lowercase.

**Template Aliases:** `lower`, `lowercase`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseStrings;

// Pipeline usage
protected function pipes(): array
{
    return [LowercaseStrings::class];
}

// Template expression usage
$template = ['name' => '{{ user.name | lower }}'];
```

**Example:**
- Input: `"HELLO"` → Output: `"hello"`

---

#### UppercaseStrings
Converts all string values to uppercase.

**Template Aliases:** `upper`, `uppercase`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\UppercaseStrings;

// Pipeline usage
protected function pipes(): array
{
    return [UppercaseStrings::class];
}

// Template expression usage
$template = ['name' => '{{ user.name | upper }}'];
```

**Example:**
- Input: `"hello"` → Output: `"HELLO"`

---

#### Ucfirst
Capitalizes the first character of a string.

**Template Aliases:** `ucfirst`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Ucfirst;

// Template expression usage
$template = ['name' => '{{ user.name | ucfirst }}'];
```

**Example:**
- Input: `"hello world"` → Output: `"Hello world"`

---

#### Ucwords
Capitalizes the first character of each word in a string.

**Template Aliases:** `ucwords`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Ucwords;

// Template expression usage
$template = ['name' => '{{ user.name | ucwords }}'];
```

**Example:**
- Input: `"hello world"` → Output: `"Hello World"`

---

#### LowercaseEmails
Converts email addresses to lowercase. Only applies to fields containing "email" in the path.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseEmails;

protected function pipes(): array
{
    return [LowercaseEmails::class];
}
```

**Example:**
- Input: `"USER@EXAMPLE.COM"` (in email field) → Output: `"user@example.com"`
- Input: `"HELLO"` (in name field) → Output: `"HELLO"` (unchanged)

---

#### StripTags
Removes HTML and PHP tags from string values.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\StripTags;

protected function pipes(): array
{
    return [StripTags::class];
}
```

**Example:**
- Input: `"<p>Hello</p>"` → Output: `"Hello"`

---

#### NormalizeLineEndings
Converts Windows (`\r\n`) and Mac (`\r`) line endings to Unix (`\n`).

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\NormalizeLineEndings;

protected function pipes(): array
{
    return [NormalizeLineEndings::class];
}
```

**Example:**
- Input: `"Line1\r\nLine2"` → Output: `"Line1\nLine2"`

---

### Array Transformers

#### Count
Returns the count of elements in an array or characters in a string.

**Template Aliases:** `count`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Count;

// Template expression usage
$template = ['item_count' => '{{ items | count }}'];
```

**Example:**
- Input: `['a', 'b', 'c']` → Output: `3`
- Input: `"hello"` → Output: `5`

---

#### First
Returns the first element of an array.

**Template Aliases:** `first`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\First;

// Template expression usage
$template = ['first_item' => '{{ items | first }}'];
```

**Example:**
- Input: `['a', 'b', 'c']` → Output: `'a'`

---

#### Last
Returns the last element of an array.

**Template Aliases:** `last`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Last;

// Template expression usage
$template = ['last_item' => '{{ items | last }}'];
```

**Example:**
- Input: `['a', 'b', 'c']` → Output: `'c'`

---

#### Keys
Returns the keys of an array.

**Template Aliases:** `keys`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Keys;

// Template expression usage
$template = ['field_names' => '{{ data | keys }}'];
```

**Example:**
- Input: `['name' => 'John', 'age' => 30]` → Output: `['name', 'age']`

---

#### Values
Returns the values of an array.

**Template Aliases:** `values`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Values;

// Template expression usage
$template = ['field_values' => '{{ data | values }}'];
```

**Example:**
- Input: `['name' => 'John', 'age' => 30]` → Output: `['John', 30]`

---

#### Reverse
Reverses the order of elements in an array.

**Template Aliases:** `reverse`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Reverse;

// Template expression usage
$template = ['reversed_items' => '{{ items | reverse }}'];
```

**Example:**
- Input: `['a', 'b', 'c']` → Output: `['c', 'b', 'a']`

---

#### Sort
Sorts an array in ascending order.

**Template Aliases:** `sort`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Sort;

// Template expression usage
$template = ['sorted_items' => '{{ items | sort }}'];
```

**Example:**
- Input: `[3, 1, 2]` → Output: `[1, 2, 3]`

---

#### Unique
Removes duplicate values from an array.

**Template Aliases:** `unique`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Unique;

// Template expression usage
$template = ['unique_items' => '{{ items | unique }}'];
```

**Example:**
- Input: `['a', 'b', 'a', 'c']` → Output: `['a', 'b', 'c']`

---

#### Join
Joins array elements into a string with a separator.

**Template Aliases:** `join`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Join;

// Template expression usage
$template = ['tags_string' => '{{ tags | join:", " }}'];
```

**Example:**
- Input: `['php', 'laravel', 'vue']` with separator `", "` → Output: `"php, laravel, vue"`

---

### Encoding Transformers

#### JsonEncode
Encodes a value as JSON.

**Template Aliases:** `json`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\JsonEncode;

// Template expression usage
$template = ['metadata_json' => '{{ metadata | json }}'];
```

**Example:**
- Input: `['name' => 'John', 'age' => 30]` → Output: `'{"name":"John","age":30}'`

---

### Utility Transformers

#### DefaultValue
Returns a default value if the input is null or empty.

**Template Aliases:** `default`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\DefaultValue;

// Template expression usage
$template = ['name' => '{{ user.name | default:"Unknown" }}'];
```

**Example:**
- Input: `null` with default `"Unknown"` → Output: `"Unknown"`
- Input: `"John"` with default `"Unknown"` → Output: `"John"`

---

### Type Casting Transformers

#### CastToInteger
Casts numeric values to integers. Applies to fields containing: `id`, `count`, `quantity`, `age`, `year`, `number`.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\CastToInteger;

protected function pipes(): array
{
    return [CastToInteger::class];
}
```

**Example:**
- Input: `"123"` (in product_id field) → Output: `123` (integer)
- Input: `"hello"` (in product_id field) → Output: `"hello"` (unchanged, not numeric)

---

#### CastToFloat
Casts numeric values to floats. Applies to fields containing: `price`, `amount`, `total`, `rate`, `percentage`, `cost`, `fee`.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\CastToFloat;

protected function pipes(): array
{
    return [CastToFloat::class];
}
```

**Example:**
- Input: `"49.99"` (in price field) → Output: `49.99` (float)
- Input: `"100"` (in price field) → Output: `100.0` (float)

---

#### CastToBoolean
Casts values to booleans. Applies to fields containing: `is_`, `has_`, `can_`, `should_`, `active`, `enabled`, `disabled`.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\CastToBoolean;

protected function pipes(): array
{
    return [CastToBoolean::class];
}
```

**Conversion rules:**
- `true`: `'1'`, `'true'`, `'yes'`, `'on'`, `1`, `true`
- `false`: `'0'`, `'false'`, `'no'`, `'off'`, `''`, `0`, `false`

**Example:**
- Input: `"1"` (in is_active field) → Output: `true` (boolean)
- Input: `"yes"` (in is_active field) → Output: `true` (boolean)
- Input: `"0"` (in is_active field) → Output: `false` (boolean)

---

### Value Filtering Transformers

#### SkipEmptyValues
Prevents empty strings and empty arrays from being written to the target.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\SkipEmptyValues;

protected function pipes(): array
{
    return [SkipEmptyValues::class];
}
```

**Example:**
- Input: `""` → Skipped (not written to target)
- Input: `[]` → Skipped (not written to target)
- Input: `"hello"` → Output: `"hello"`

---

#### RemoveNullValues
Prevents null values from being written to the target.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\RemoveNullValues;

protected function pipes(): array
{
    return [RemoveNullValues::class];
}
```

**Example:**
- Input: `null` → Skipped (not written to target)
- Input: `"hello"` → Output: `"hello"`

---

#### ConvertEmptyToNull
Converts empty strings to null values.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\ConvertEmptyToNull;

protected function pipes(): array
{
    return [ConvertEmptyToNull::class];
}
```

**Example:**
- Input: `""` → Output: `null`
- Input: `"hello"` → Output: `"hello"`

---

#### ConvertToNull
Converts specific values to null. Can be configured with custom values.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\ConvertToNull;

protected function pipes(): array
{
    return [ConvertToNull::class];
}
```

---

## Combining Transformers

Transformers are applied in the order they are defined. This allows for powerful data cleaning pipelines:

```php
protected function pipes(): array
{
    return [
        TrimStrings::class,          // 1. Trim whitespace first
        StripTags::class,            // 2. Remove HTML tags
        ConvertEmptyToNull::class,   // 3. Convert empty strings to null
        LowercaseEmails::class,      // 4. Lowercase email fields
        CastToInteger::class,        // 5. Cast numeric IDs to integers
        CastToFloat::class,          // 6. Cast prices to floats
        CastToBoolean::class,        // 7. Cast boolean fields
        RemoveNullValues::class,     // 8. Remove null values from result
    ];
}
```

## Usage Examples

### Example 1: Clean User Input

```php
class UserRegistrationModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'email' => '{{ request.email }}',
            'name' => '{{ request.name }}',
            'age' => '{{ request.age }}',
        ];
    }

    protected function pipes(): array
    {
        return [
            TrimStrings::class,
            LowercaseEmails::class,
            CastToInteger::class,
        ];
    }
}

$model = new UserRegistrationModel([
    'email' => '  USER@EXAMPLE.COM  ',
    'name' => '  John Doe  ',
    'age' => '25',
]);

// Result:
// [
//     'email' => 'user@example.com',  // trimmed + lowercased
//     'name' => 'John Doe',            // trimmed
//     'age' => 25,                     // cast to integer
// ]
```

### Example 2: Product Data with Type Safety

```php
class ProductModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'product_id' => 'request.id',
            'name' => 'request.name',
            'price' => 'request.price',
            'is_active' => '{{ request.active }}',
        ];
    }

    protected function pipes(): array
    {
        return [
            TrimStrings::class,
            CastToInteger::class,
            CastToFloat::class,
            CastToBoolean::class,
        ];
    }
}
```

### Example 3: Clean Optional Fields

```php
class UserProfileModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'name' => 'request.name',
            'bio' => '{{ request.bio }}',
            'website' => '{{ request.website }}',
        ];
    }

    protected function pipes(): array
    {
        return [
            TrimStrings::class,
            ConvertEmptyToNull::class,
            RemoveNullValues::class,  // Only include fields with values
        ];
    }
}
```

## Creating Custom Transformers

You can create your own transformers by implementing `TransformerInterface`:

```php
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerRegistry;

final class MyCustomTransformer implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        // Your transformation logic here
        return $value;
    }

    public function getHook(): string
    {
        // When to apply: 'preTransform' or 'beforeWrite'
        return 'preTransform';
    }

    public function getFilter(): ?string
    {
        // Optional: filter by field path pattern
        return null;
    }

    /**
     * Define template expression aliases for this transformer.
     * @return array<int, string>
     */
    public function getAliases(): array
    {
        // Return aliases for use in template expressions
        // Example: ['my_filter', 'my_alias']
        return ['my_custom'];
    }
}

// Register the transformer for use in template expressions
TransformerRegistry::register(MyCustomTransformer::class);

// Now you can use it in templates
$template = ['name' => '{{ user.name | my_custom }}'];
```

### Example: AlternatingCase Transformer

Here's a complete example of a custom transformer that alternates character casing:

```php
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

final class AlternatingCase implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $result = '';
        $length = mb_strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($value, $i, 1);
            // Uppercase every 2nd, 4th, 6th character (even positions)
            if (($i + 1) % 2 === 0) {
                $result .= mb_strtoupper($char);
            } else {
                $result .= mb_strtolower($char);
            }
        }

        return $result;
    }

    public function getHook(): string
    {
        return 'preTransform';
    }

    public function getFilter(): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['alternating', 'alt_case', 'zigzag'];
    }
}

// Usage in template expressions
$template = ['name' => '{{ user.name | alternating }}'];
// Input: "hello world" → Output: "hElLo wOrLd"
```

## TransformerRegistry

The `TransformerRegistry` manages transformer aliases for use in template expressions. All built-in transformers are automatically registered.

### Registering Custom Transformers

```php
use event4u\DataHelpers\DataMapper\Pipeline\TransformerRegistry;

// Register a single transformer
TransformerRegistry::register(MyCustomTransformer::class);

// Register multiple transformers
TransformerRegistry::registerMany([
    MyCustomTransformer::class,
    AnotherTransformer::class,
]);

// Check if an alias is registered
if (TransformerRegistry::has('my_custom')) {
    // Alias is available
}

// Get the transformer class for an alias
$transformerClass = TransformerRegistry::get('my_custom');

// Get all registered aliases
$allAliases = TransformerRegistry::all();
// Returns: ['trim' => TrimStrings::class, 'upper' => UppercaseStrings::class, ...]

// Clear all registrations (useful for testing)
TransformerRegistry::clear();
```

### Built-in Transformer Aliases

All built-in transformers are automatically registered with the following aliases:

**String Transformers:**
- `trim` → TrimStrings
- `lower`, `lowercase` → LowercaseStrings
- `upper`, `uppercase` → UppercaseStrings
- `ucfirst` → Ucfirst
- `ucwords` → Ucwords

**Array Transformers:**
- `count` → Count
- `first` → First
- `last` → Last
- `keys` → Keys
- `values` → Values
- `reverse` → Reverse
- `sort` → Sort
- `unique` → Unique
- `join` → Join

**Encoding Transformers:**
- `json` → JsonEncode

**Utility Transformers:**
- `default` → DefaultValue

### Error Handling

If you use an unknown filter alias in a template expression, an `InvalidArgumentException` is thrown:

```php
$template = ['name' => '{{ user.name | unknown_filter }}'];
// Throws: InvalidArgumentException: Unknown transformer alias 'unknown_filter'.
//         
//         create a Transformer class with getAliases() method and register it
//         using TransformerRegistry::register().
```

## Hook Types

- **`preTransform`**: Applied before the value is processed
- **`beforeWrite`**: Applied just before writing to the target

Most transformers use `preTransform` for data cleaning and type conversion.


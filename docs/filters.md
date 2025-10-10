# Data Filters

Data filters are pipeline components that modify values during the mapping process. They can be used with `DataMapper::pipe()`, in
`MappedDataModel::pipes()`, or as filters in template expressions.

## Quick Overview

Filters can be used in two ways:

1. **Pipeline Mode**: Apply filters to all mapped values
2. **Template Expression Mode**: Apply filters to specific fields using aliases

**Pipeline Example:**

```php
DataMapper::pipe([new TrimStrings(), new LowercaseStrings()])
    ->map($source, $target, $mapping);
```

**Template Expression Example:**

```php
$template = [
    'name' => '{{ user.name | trim | upper }}',
    'count' => '{{ items | count }}',
];
```

## Available Filters

### String Filters

#### TrimStrings

Removes characters from the beginning and end of string values.

By default trims whitespace. You can specify custom characters to trim.

**Template Aliases:** `trim`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;

// Pipeline usage - default (whitespace)
protected function pipes(): array
{
    return [new TrimStrings()];
}

// Pipeline usage - custom characters
protected function pipes(): array
{
    return [new TrimStrings(' -')];  // Trim space and dash
}

// Template expression usage
$template = [
    'name' => '{{ user.name | trim }}',                    // Trim whitespace (default)
    'title' => '{{ product.title | trim:"-" }}',           // Trim only dash
    'description' => '{{ item.description | trim:" -" }}', // Trim space and dash
];
```

**Examples:**

- Input: `"  hello  "` → Output: `"hello"` (default whitespace)
- Input: `"- Sample - Swimming Pool -"` with `trim:" -"` → Output: `"Sample - Swimming Pool"`
- Input: `"---Sample---"` with `trim:"-"` → Output: `"Sample"`

**Note:** Uses PHP's `trim()` function. See [PHP trim() documentation](https://www.php.net/manual/en/function.trim.php) for character list syntax.

---

#### DecodeHtmlEntities

Decodes HTML entities in string values, including numeric entities (e.g., `&#32;`, `&#45;`) and named entities (e.g., `&amp;`, `&lt;`, `&gt;`). Handles double-encoded and triple-encoded entities automatically.

**Template Aliases:** `decode_html`, `html_decode`, `decode_entities`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\DecodeHtmlEntities;

// Pipeline usage
protected function pipes(): array
{
    return [new DecodeHtmlEntities()];
}

// Template expression usage
$template = [
    'name' => '{{ customer.name | decode_html }}',
    'description' => '{{ product.description | decode_html | trim }}',
];
```

**Examples:**

- Input: `"Herbert&#32;Meier"` → Output: `"Herbert Meier"`
- Input: `"Sample&amp;#32;&amp;#45;&amp;#32;Pool"` → Output: `"Sample - Pool"` (double-encoded)
- Input: `"&lt;div&gt;"` → Output: `"<div>"`
- Input: `"&quot;Hello&quot;"` → Output: `'"Hello"'`

**Use Cases:**

- Decoding XML/HTML data with encoded special characters
- Processing data from external APIs that encode entities
- Cleaning up text fields with multiple levels of encoding

---

#### LowercaseStrings

Converts all string values to lowercase.

**Template Aliases:** `lower`, `lowercase`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;

// Pipeline usage
protected function pipes(): array
{
    return [new LowercaseStrings()];
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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;

// Pipeline usage
protected function pipes(): array
{
    return [new UppercaseStrings()];
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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Ucfirst;

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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Ucwords;

// Template expression usage
$template = ['name' => '{{ user.name | ucwords }}'];
```

**Example:**

- Input: `"hello world"` → Output: `"Hello World"`

---

#### LowercaseEmails

Converts email addresses to lowercase. Only applies to fields containing "email" in the path.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseEmails;

protected function pipes(): array
{
    return [new LowercaseEmails()];
}
```

**Example:**

- Input: `"USER@EXAMPLE.COM"` (in email field) → Output: `"user@example.com"`
- Input: `"HELLO"` (in name field) → Output: `"HELLO"` (unchanged)

---

#### StripTags

Removes HTML and PHP tags from string values.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\StripTags;

protected function pipes(): array
{
    return [new StripTags()];
}
```

**Example:**

- Input: `"<p>Hello</p>"` → Output: `"Hello"`

---

#### NormalizeLineEndings

Converts Windows (`\r\n`) and Mac (`\r`) line endings to Unix (`\n`).

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\NormalizeLineEndings;

protected function pipes(): array
{
    return [NormalizeLineEndings::class];
}
```

**Example:**

- Input: `"Line1\r\nLine2"` → Output: `"Line1\nLine2"`

---

### Array Filters

#### Count

Returns the count of elements in an array or characters in a string.

**Template Aliases:** `count`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Count;

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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\First;

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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Last;

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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Keys;

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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Values;

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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Reverse;

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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Sort;

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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Unique;

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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Join;

// Template expression usage
$template = ['tags_string' => '{{ tags | join:", " }}'];
```

**Example:**

- Input: `['php', 'laravel', 'vue']` with separator `", "` → Output: `"php, laravel, vue"`

---

### Encoding Filters

#### JsonEncode

Encodes a value as JSON.

**Template Aliases:** `json`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\JsonEncode;

// Template expression usage
$template = ['metadata_json' => '{{ metadata | json }}'];
```

**Example:**

- Input: `['name' => 'John', 'age' => 30]` → Output: `'{"name":"John","age":30}'`

---

### Utility Filters

#### DefaultValue

Returns a default value if the input is null or empty.

**Template Aliases:** `default`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\DefaultValue;

// Template expression usage
$template = ['name' => '{{ user.name | default:"Unknown" }}'];
```

**Example:**

- Input: `null` with default `"Unknown"` → Output: `"Unknown"`
- Input: `"John"` with default `"Unknown"` → Output: `"John"`

---

### Type Casting Filters

#### CastToInteger

Casts numeric values to integers. Applies to fields containing: `id`, `count`, `quantity`, `age`, `year`, `number`.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToInteger;

protected function pipes(): array
{
    return [new CastToInteger()];
}
```

**Example:**

- Input: `"123"` (in product_id field) → Output: `123` (integer)
- Input: `"hello"` (in product_id field) → Output: `"hello"` (unchanged, not numeric)

---

#### CastToFloat

Casts numeric values to floats. Applies to fields containing: `price`, `amount`, `total`, `rate`, `percentage`, `cost`, `fee`.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToFloat;

protected function pipes(): array
{
    return [new CastToFloat()];
}
```

**Example:**

- Input: `"49.99"` (in price field) → Output: `49.99` (float)
- Input: `"100"` (in price field) → Output: `100.0` (float)

---

#### CastToBoolean

Casts values to booleans. Applies to fields containing: `is_`, `has_`, `can_`, `should_`, `active`, `enabled`, `disabled`.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToBoolean;

protected function pipes(): array
{
    return [new CastToBoolean()];
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

### Value Filtering Filters

#### SkipEmptyValues

Prevents empty strings and empty arrays from being written to the target.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\SkipEmptyValues;

protected function pipes(): array
{
    return [new SkipEmptyValues()];
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
use event4u\DataHelpers\DataMapper\Pipeline\Filters\RemoveNullValues;

protected function pipes(): array
{
    return [new RemoveNullValues()];
}
```

**Example:**

- Input: `null` → Skipped (not written to target)
- Input: `"hello"` → Output: `"hello"`

---

#### ConvertEmptyToNull

Converts empty strings to null values. Useful for database operations where empty strings should be stored as NULL, or when you want to skip empty fields in the result.

**Template Aliases:** `empty_to_null`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\ConvertEmptyToNull;

// Pipeline usage
protected function pipes(): array
{
    return [new ConvertEmptyToNull()];
}

// Template expression usage
$template = [
    'name3' => '{{ customer.name3 | decode_html | empty_to_null }}',
];
```

**Examples:**

- Input: `""` → Output: `null` (field will be skipped in mapFromTemplate)
- Input: `"hello"` → Output: `"hello"`

**Note:** When used in `mapFromTemplate()`, null values are skipped by default, so empty strings will not appear in the result.

---

#### ConvertToNull

Converts specific values to null. Can be configured with custom values.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\ConvertToNull;

protected function pipes(): array
{
    return [new ConvertToNull()];
}
```

---

## Combining Filters

Filters are applied in the order they are defined. This allows for powerful data cleaning pipelines:

```php
protected function pipes(): array
{
    return [
        new TrimStrings(),          // 1. Trim whitespace first
        new StripTags(),            // 2. Remove HTML tags
        new ConvertEmptyToNull(),   // 3. Convert empty strings to null
        new LowercaseEmails(),      // 4. Lowercase email fields
        new CastToInteger(),        // 5. Cast numeric IDs to integers
        new CastToFloat(),          // 6. Cast prices to floats
        new CastToBoolean(),        // 7. Cast boolean fields
        new RemoveNullValues(),     // 8. Remove null values from result
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
            new TrimStrings(),
            new LowercaseEmails(),
            new CastToInteger(),
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
            new TrimStrings(),
            new CastToInteger(),
            new CastToFloat(),
            new CastToBoolean(),
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
            new TrimStrings(),
            new ConvertEmptyToNull(),
            new RemoveNullValues(),  // Only include fields with values
        ];
    }
}
```

## Creating Custom Filters

You can create your own filters by implementing `FilterInterface`:

```php
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\DataMapper\Pipeline\FilterRegistry;

final class MyCustomFilter implements FilterInterface
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
     * Define template expression aliases for this filter.
     * @return array<int, string>
     */
    public function getAliases(): array
    {
        // Return aliases for use in template expressions
        // Example: ['my_filter', 'my_alias']
        return ['my_custom'];
    }
}

// Register the filter for use in template expressions
FilterRegistry::register(MyCustomFilter::class);

// Now you can use it in templates
$template = ['name' => '{{ user.name | my_custom }}'];
```

### Example: AlternatingCase Filter

Here's a complete example of a custom filter that alternates character casing:

```php
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

final class AlternatingCase implements FilterInterface
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

## FilterRegistry

The `FilterRegistry` manages filter aliases for use in template expressions. All built-in filters are automatically
registered.

### Registering Custom Filters

```php
use event4u\DataHelpers\DataMapper\Pipeline\FilterRegistry;

// Register a single filter
FilterRegistry::register(MyCustomFilter::class);

// Register multiple filters
FilterRegistry::registerMany([
    MyCustomFilter::class,
    AnotherFilter::class,
]);

// Check if an alias is registered
if (FilterRegistry::has('my_custom')) {
    // Alias is available
}

// Get the filter class for an alias
$filterClass = FilterRegistry::get('my_custom');

// Get all registered aliases
$allAliases = FilterRegistry::all();
// Returns: ['trim' => new TrimStrings(), 'upper' => new UppercaseStrings(), ...]

// Clear all registrations (useful for testing)
FilterRegistry::clear();
```

### Built-in Filter Aliases

All built-in filters are automatically registered with the following aliases:

**String Filters:**

- `trim` → TrimStrings
- `lower`, `lowercase` → LowercaseStrings
- `upper`, `uppercase` → UppercaseStrings
- `ucfirst` → Ucfirst
- `ucwords` → Ucwords
- `decode_html` → DecodeHtmlEntities

**Array Filters:**

- `count` → Count
- `first` → First
- `last` → Last
- `keys` → Keys
- `values` → Values
- `reverse` → Reverse
- `sort` → Sort
- `unique` → Unique
- `join` → Join

**Encoding Filters:**

- `json` → JsonEncode

**Utility Filters:**

- `default` → DefaultValue
- `between` → Between
- `clamp` → Clamp
- `empty_to_null` → ConvertEmptyToNull

---

#### Between

Checks if a numeric value is within a range (inclusive by default).

**Template Aliases:** `between`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Between;

// Template expression usage
$template = [
    'is_valid_age' => '{{ user.age | between:18:65 }}',
    'is_in_range' => '{{ value | between:0:100 }}',
];
```

**Modes:**

- **Inclusive (default)**: Uses `>=` and `<=` operators (like Laravel, MySQL, etc.)
- **Strict mode**: Uses `>` and `<` operators (exclusive boundaries)

**Examples:**

```php
// Inclusive mode (default)
{{ value | between:3:5 }}
// 3 → true, 4 → true, 5 → true
// 2 → false, 6 → false

// Strict mode (exclusive boundaries)
{{ value | between:3:5:strict }}
// 3 → false, 4 → true, 5 → false
// Only values > 3 AND < 5 return true

// Negative ranges
{{ value | between:-10:10 }}
// -10 → true, 0 → true, 10 → true

// Decimal values
{{ value | between:0.5:1.5 }}
// 0.5 → true, 1.0 → true, 1.5 → true
```

**Non-numeric values:**

- Input: `"abc"` → Output: `false`
- Input: `null` → Output: `false`

---

#### Clamp

Limits a numeric value to a specified range.

**Template Aliases:** `clamp`

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Clamp;

// Template expression usage
$template = [
    'age' => '{{ user.age | clamp:18:65 }}',
    'percentage' => '{{ value | clamp:0:100 }}',
];
```

**Examples:**

```php
// Clamp to range
{{ value | clamp:3:5 }}
// 2 → 3 (below min, returns min)
// 4 → 4 (within range, unchanged)
// 6 → 5 (above max, returns max)

// Negative ranges
{{ value | clamp:-10:10 }}
// -15 → -10, 0 → 0, 15 → 10

// Decimal values
{{ value | clamp:0:1 }}
// -0.5 → 0.0, 0.75 → 0.75, 1.5 → 1.0
```

**Non-numeric values:**

- Input: `"abc"` → Output: `"abc"` (unchanged)
- Input: `null` → Output: `null` (unchanged)

**Difference between Between and Clamp:**

- `between` returns a **boolean** (true/false) indicating if the value is in range
- `clamp` returns a **modified value** limited to the range

```php
// Between (boolean check)
{{ 150 | between:0:100 }}  // → false

// Clamp (value limiting)
{{ 150 | clamp:0:100 }}    // → 100.0
```

---

### Error Handling

If you use an unknown filter alias in a template expression, an `InvalidArgumentException` is thrown:

```php
$template = ['name' => '{{ user.name | unknown_filter }}'];
// Throws: InvalidArgumentException: Unknown filter alias 'unknown_filter'.
//
//         create a Filter class with getAliases() method and register it
//         using FilterRegistry::register().
```

## Hook Types

- **`preTransform`**: Applied before the value is processed
- **`beforeWrite`**: Applied just before writing to the target

Most filters use `preTransform` for data cleaning and type conversion.


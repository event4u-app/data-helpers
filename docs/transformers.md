# Data Transformers

Data transformers are pipeline components that modify values during the mapping process. They can be used with `DataMapper::pipe()` or in `MappedDataModel::pipes()`.

## Available Transformers

### String Transformers

#### TrimStrings
Removes whitespace from the beginning and end of all string values.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;

protected function pipes(): array
{
    return [TrimStrings::class];
}
```

**Example:**
- Input: `"  hello  "` → Output: `"hello"`

---

#### LowercaseStrings
Converts all string values to lowercase.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseStrings;

protected function pipes(): array
{
    return [LowercaseStrings::class];
}
```

**Example:**
- Input: `"HELLO"` → Output: `"hello"`

---

#### UppercaseStrings
Converts all string values to uppercase.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\UppercaseStrings;

protected function pipes(): array
{
    return [UppercaseStrings::class];
}
```

**Example:**
- Input: `"hello"` → Output: `"HELLO"`

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
            'email' => 'request.email',
            'name' => 'request.name',
            'age' => 'request.age',
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
            'is_active' => 'request.active',
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
            'bio' => 'request.bio',
            'website' => 'request.website',
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
}
```

## Hook Types

- **`preTransform`**: Applied before the value is processed
- **`beforeWrite`**: Applied just before writing to the target

Most transformers use `preTransform` for data cleaning and type conversion.


# Enum Support

The DataMapper supports automatic conversion of values to PHP 8.1+ Enums (both BackedEnum and UnitEnum).

## Features

- ✅ Automatic conversion from string/int values to BackedEnum instances
- ✅ Support for custom `tryFromAny()` method for flexible enum conversion
- ✅ Works with Laravel Eloquent models using `$casts` property
- ✅ Works with Doctrine entities using typed setter methods
- ✅ Works with plain PHP DTOs using typed properties
- ✅ Preserves enum instances when already the correct type
- ✅ Handles null values correctly

## Usage

### 1. Define a BackedEnum

```php
enum Salutation: string
{
    case MR = 'Mr';
    case MRS = 'Mrs';
    case MISS = 'Miss';
    case DIVERSE = 'Diverse';
}
```

### 2. Use in Laravel Eloquent Model

```php
use Illuminate\Database\Eloquent\Model;

class ContactPerson extends Model
{
    protected $casts = [
        'salutation' => Salutation::class,
    ];

    public function getSalutation(): ?Salutation
    {
        return $this->salutation;
    }

    public function setSalutation(Salutation|string|null $salutation): self
    {
        $this->salutation = $salutation;
        return $this;
    }
}
```

### 3. Map Data to Model

```php
use event4u\DataHelpers\DataMapper;

$data = [
    'salutation' => 'Mr',
    'surname' => 'Doe, John',
    'email' => 'john@example.com',
];

$mapping = [
    'salutation' => '{{ salutation }}',
    'surname' => '{{ surname }}',
    'email' => '{{ email }}',
];

$contactPerson = new ContactPerson();
$result = DataMapper::map($data, $contactPerson, $mapping);

// $result->getSalutation() returns Salutation::MR (enum instance)
// $result->getSalutation()->value returns 'Mr' (string value)
```

## Advanced: Custom tryFromAny() Method

For more flexible enum conversion (e.g., case-insensitive matching, translations), add a `tryFromAny()` method to your enum:

```php
enum Salutation: string
{
    case MR = 'Mr';
    case MRS = 'Mrs';
    case MISS = 'Miss';
    case DIVERSE = 'Diverse';

    public static function tryFromAny(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (!is_string($value)) {
            return null;
        }

        // Case-insensitive matching
        $value = strtolower(trim($value, '. '));

        return match ($value) {
            'mr', 'herr' => self::MR,
            'mrs', 'frau' => self::MRS,
            'miss', 'fräulein' => self::MISS,
            'diverse', 'divers' => self::DIVERSE,
            default => self::tryFrom(ucfirst($value)),
        };
    }
}
```

Now the DataMapper will use `tryFromAny()` for conversion:

```php
$data = [
    'salutation' => 'herr',  // German for "Mr"
];

$mapping = [
    'salutation' => '{{ salutation }}',
];

$contactPerson = new ContactPerson();
$result = DataMapper::map($data, $contactPerson, $mapping);

// $result->getSalutation() returns Salutation::MR
```

## Doctrine Entities

For Doctrine entities, use typed setter methods:

```php
class ContactPerson
{
    private ?Salutation $salutation = null;

    public function setSalutation(?Salutation $salutation): self
    {
        $this->salutation = $salutation;
        return $this;
    }

    public function getSalutation(): ?Salutation
    {
        return $this->salutation;
    }
}
```

The DataMapper will automatically convert string values to enum instances when calling the setter.

## Plain PHP DTOs

For plain PHP DTOs, use typed properties:

```php
class ContactPersonDto
{
    public ?Salutation $salutation = null;
    public ?string $surname = null;
    public ?string $email = null;
}
```

The DataMapper will automatically convert string values to enum instances when setting the property.

## Conversion Priority

The DataMapper tries to convert values to enums in the following order:

1. **Check if value is already the correct enum type** → Return as-is
2. **Try `tryFrom()` for BackedEnum** → Standard PHP enum conversion
3. **Try `tryFromAny()` if available** → Custom conversion logic
4. **Return original value** → Let the framework handle it (may throw error)

## Error Handling

If the value cannot be converted to the enum:

- **Laravel Eloquent**: Throws `ValueError` with message like `"invalid" is not a valid backing value for enum Salutation`
- **Doctrine**: Throws `TypeError` if setter expects enum type
- **Plain PHP**: Throws `TypeError` if property is typed

To avoid errors, use nullable enum types (`?Salutation`) or implement `tryFromAny()` with fallback logic.

## Testing

Example test to verify enum mapping:

```php
it('maps string to BackedEnum', function(): void {
    $data = ['salutation' => 'Mr'];
    $mapping = ['salutation' => '{{ salutation }}'];

    $contactPerson = new ContactPerson();
    $result = DataMapper::map($data, $contactPerson, $mapping);

    expect($result->getSalutation())->toBeInstanceOf(Salutation::class);
    expect($result->getSalutation())->toBe(Salutation::MR);
    expect($result->getSalutation()?->value)->toBe('Mr');
});
```

## Framework Support

| Framework | Support | Notes |
|-----------|---------|-------|
| Laravel Eloquent | ✅ Full | Uses `$casts` property |
| Doctrine | ✅ Full | Uses typed setter methods |
| Plain PHP | ✅ Full | Uses typed properties |
| Symfony | ✅ Full | Works like Doctrine |

## See Also

- [DataMapper Documentation](datamapper.md)
- [Template Expressions](template-expressions.md)
- [Type Casting](type-casting.md)


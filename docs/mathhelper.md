# MathHelper

The `MathHelper` provides **high-precision mathematical operations** using BCMath with a clean, type-safe API.

## Features

- ✅ **High-precision arithmetic** - Uses BCMath for accurate decimal calculations
- ✅ **Type-safe** - PHPStan Level 9 compliant with strict type checking
- ✅ **Flexible input** - Accepts int, float, string, or null values
- ✅ **Array operations** - min, max, sum, average, product
- ✅ **Time conversions** - Convert between minutes, hours, and HH:MM format
- ✅ **Configurable error handling** - Choose between exceptions or zero conversion for malformed input

## Basic Arithmetic

### Addition

```php
use event4u\DataHelpers\Helpers\MathHelper;

$result = MathHelper::add(10.5, 5.3); // 15.8
$result = MathHelper::add('10.5', '5.3'); // 15.8 (accepts strings)
$result = MathHelper::add(null, 5); // 5.0 (null treated as 0)
```

### Subtraction

```php
$result = MathHelper::subtract(10, 5); // 5.0
$result = MathHelper::subtract(10.5, 5.3); // 5.2
```

### Multiplication

```php
$result = MathHelper::multiply(10, 5); // 50.0
$result = MathHelper::multiply(2.5, 4); // 10.0
```

### Division

```php
$result = MathHelper::divide(10, 5); // 2.0
$result = MathHelper::divide(5, 2); // 2.5

// Division by zero throws exception by default
try {
    MathHelper::divide(10, 0);
} catch (MathException $e) {
    // Handle error
}

// Or return 0 instead
$result = MathHelper::divide(10, 0, MathHelper::DEFAULT_SCALE, false); // 0.0
```

### Modulo

```php
$result = MathHelper::modulo(10, 3); // 1.0
$result = MathHelper::modulo(10, 5); // 0.0
```

### Power

```php
$result = MathHelper::powerOf(2, 3); // 8.0
$result = MathHelper::powerOf(5, 2); // 25.0
$result = MathHelper::powerOf(0, 0); // 1.0
```

### Square Root

```php
$result = MathHelper::squareRoot(16); // 4.0
$result = MathHelper::squareRoot(2.25); // 1.5

// Negative numbers throw exception
try {
    MathHelper::squareRoot(-1);
} catch (MathException $e) {
    // Handle error
}
```

## Comparison

```php
$result = MathHelper::compare(10, 10); // 0 (equal)
$result = MathHelper::compare(10, 5);  // 1 (first is greater)
$result = MathHelper::compare(5, 10);  // -1 (first is smaller)
```

## Array Operations

### Minimum

```php
$min = MathHelper::min([10, 5, 20, 3]); // 3
$min = MathHelper::min([5.5, 2.3, 8.1]); // 2.3
$min = MathHelper::min([]); // 0 (empty array)
```

### Maximum

```php
$max = MathHelper::max([10, 5, 20, 3]); // 20
$max = MathHelper::max([5.5, 2.3, 8.1]); // 8.1
$max = MathHelper::max([]); // 0 (empty array)
```

### Sum

```php
$sum = MathHelper::sum([10, 5, 20, 3]); // 38.0
$sum = MathHelper::sum([1.5, 2.5, 3.5]); // 7.5
$sum = MathHelper::sum([]); // 0.0 (empty array)
```

### Subtraction Sum

```php
$result = MathHelper::subSum([10, 5, 3]); // -18.0 (0 - 10 - 5 - 3)
```

### Average

```php
$avg = MathHelper::average([10, 20, 30]); // 20.0
$avg = MathHelper::average([1, 2, 3, 4, 5]); // 3.0
$avg = MathHelper::average([]); // 0.0 (empty array)
```

### Product

```php
$product = MathHelper::product([2, 3, 4]); // 24.0
$product = MathHelper::product([1.5, 2, 3]); // 9.0
$product = MathHelper::product([]); // 1.0 (empty array)
```

## Time Conversions

### Minutes to Decimal Hours

```php
// As string
$hours = MathHelper::convertMinutesToDecimalHours(90); // '1.5'
$hours = MathHelper::convertMinutesToDecimalHours(90, 2); // '1.50'

// As float
$hours = MathHelper::convertMinutesToDecimalHoursAsFloat(90); // 1.5
$hours = MathHelper::convertMinutesToDecimalHoursAsFloat(120); // 2.0

// Rounded
$hours = MathHelper::convertMinutesToDecimalHoursRounded(125); // 2.0
$hours = MathHelper::convertMinutesToDecimalHoursRounded(125, 1); // 2.1
```

### Minutes to HH:MM Format

```php
$time = MathHelper::convertMinutesToHourMinutes(125); // '02:05'
$time = MathHelper::convertMinutesToHourMinutes(60);  // '01:00'
$time = MathHelper::convertMinutesToHourMinutes(90);  // '01:30'
$time = MathHelper::convertMinutesToHourMinutes(0);   // '00:00'
```

### HH:MM to Minutes

```php
$minutes = MathHelper::convertHoursMinutesToMinutes('02:05'); // 125
$minutes = MathHelper::convertHoursMinutesToMinutes('01:00'); // 60
$minutes = MathHelper::convertHoursMinutesToMinutes('01:30'); // 90
```

### Decimal Hours to Seconds

```php
$seconds = MathHelper::convertDecimalHoursToSeconds(1);   // 3600.0
$seconds = MathHelper::convertDecimalHoursToSeconds(1.5); // 5400.0

// Rounded
$seconds = MathHelper::convertDecimalHoursToSecondsRounded(1.5); // 5400.0
```

## Precision Control

All methods accept an optional `$scale` parameter to control decimal precision:

```php
$result = MathHelper::add(1.123456789, 2.987654321, 8); // 4.11111111
$result = MathHelper::divide(10, 3, 4); // 3.3333
```

**Default scale:** 16 decimal places
**Maximum scale:** 53 decimal places

## Error Handling

### Malformed Input

By default, malformed input throws `MathException`:

```php
try {
    MathHelper::add('not_a_number', 5);
} catch (MathException $e) {
    // Handle error
}
```

You can configure it to convert malformed input to zero instead:

```php
MathHelper::setConvertMalformedInputToZero(true);
$result = MathHelper::add('not_a_number', 5); // 5.0
MathHelper::setConvertMalformedInputToZero(false); // Reset to default
```

### Division by Zero

```php
// Throws exception by default
try {
    MathHelper::divide(10, 0);
} catch (MathException $e) {
    // Handle error
}

// Or return 0
$result = MathHelper::divide(10, 0, MathHelper::DEFAULT_SCALE, false); // 0.0
```

### Exception Data

`MathException` contains detailed error information:

```php
try {
    MathHelper::divide(10, 0);
} catch (MathException $e) {
    $data = $e->getData();
    // ['method' => 'divide', 'num1' => '10', 'num2' => '0', 'scale' => 16]
}
```

## Scientific Notation

Automatically handles scientific notation:

```php
$result = MathHelper::add('3.8773213097356E-12', 1); // 1.0000000000038773
```

## Best Practices

### Use Appropriate Scale

```php
// ✅ Use higher scale for financial calculations
$total = MathHelper::multiply($price, $quantity, 4);

// ✅ Use lower scale for display
$display = MathHelper::divide($total, $count, 2);
```

### Handle Null Values

```php
// ✅ Null is automatically treated as 0
$result = MathHelper::add($value ?? null, 10); // Works even if $value is null
```

### Array Operations with Null

```php
// ✅ Null values are automatically filtered
$sum = MathHelper::sum([10, null, 5, null, 3]); // 18.0
```

### Time Conversions

```php
// ✅ Use appropriate method for your use case
$hoursString = MathHelper::convertMinutesToDecimalHours(90, 2); // '1.50' for display
$hoursFloat = MathHelper::convertMinutesToDecimalHoursAsFloat(90); // 1.5 for calculations
$hoursRounded = MathHelper::convertMinutesToDecimalHoursRounded(125); // 2.0 for rounding
```

## Constants

```php
MathHelper::DEFAULT_SCALE; // 16
MathHelper::MAXIMUM_PHP_SCALE; // 53
MathHelper::THROW_EXCEPTION_AT_DIVISION_BY_ZERO; // true
```

## See Also

- [Configuration](configuration.md) - Package configuration
- [Examples](examples.md) - More usage examples

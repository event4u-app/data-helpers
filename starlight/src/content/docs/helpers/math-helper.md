---
title: MathHelper
description: High-precision mathematical operations using BCMath with type-safe API
---

MathHelper provides high-precision mathematical operations using BCMath with a clean, type-safe API.

## Quick Example

```php
use event4u\DataHelpers\Helpers\MathHelper;

// Basic arithmetic
$result = MathHelper::add(10.5, 5.3); // 15.8
$result = MathHelper::subtract(10, 5); // 5.0
$result = MathHelper::multiply(2.5, 4); // 10.0
$result = MathHelper::divide(10, 3, 4); // 3.3333

// Array operations
$sum = MathHelper::sum([10, 5, 20, 3]); // 38.0
$avg = MathHelper::average([10, 20, 30]); // 20.0
$min = MathHelper::min([10, 5, 20, 3]); // 3
$max = MathHelper::max([10, 5, 20, 3]); // 20

// Time conversions
$hours = MathHelper::convertMinutesToDecimalHours(90); // '1.5'
$time = MathHelper::convertMinutesToHourMinutes(125); // '02:05'
$minutes = MathHelper::convertHoursMinutesToMinutes('02:05'); // 125
```

## Introduction

MathHelper provides high-precision arithmetic operations using BCMath.

### Key Features

- **High-precision arithmetic** - Uses BCMath for accurate decimal calculations
- **Type-safe** - PHPStan Level 9 compliant with strict type checking
- **Flexible input** - Accepts int, float, string, or null values
- **Array operations** - min, max, sum, average, product
- **Time conversions** - Convert between minutes, hours, and HH:MM format
- **Configurable error handling** - Choose between exceptions or zero conversion for malformed input

### Precision Control

All methods accept an optional `$scale` parameter to control decimal precision:

- **Default scale:** 16 decimal places
- **Maximum scale:** 53 decimal places

```php
$result = MathHelper::add(1.123456789, 2.987654321, 8); // 4.11111111
$result = MathHelper::divide(10, 3, 4); // 3.3333
```

## Basic Arithmetic

### Addition

```php
use event4u\DataHelpers\Helpers\MathHelper;

$result = MathHelper::add(10.5, 5.3); // 15.8
$result = MathHelper::add('10.5', '5.3'); // 15.8 (accepts strings)
$result = MathHelper::add(null, 5); // 5.0 (null treated as 0)

// With custom precision
$result = MathHelper::add(1.123456789, 2.987654321, 8); // 4.11111111
```

### Subtraction

```php
$result = MathHelper::subtract(10, 5); // 5.0
$result = MathHelper::subtract(10.5, 5.3); // 5.2
$result = MathHelper::subtract(100, 25.5); // 74.5
```

### Multiplication

```php
$result = MathHelper::multiply(10, 5); // 50.0
$result = MathHelper::multiply(2.5, 4); // 10.0
$result = MathHelper::multiply(3.14159, 2, 4); // 6.2832
```

### Division

```php
$result = MathHelper::divide(10, 5);
// Result: 2.0

$result = MathHelper::divide(5, 2);
// Result: 2.5

$result = MathHelper::divide(10, 3, 4);
// Result: 3.3333

// Or return 0 instead of throwing exception
$result = MathHelper::divide(10, 0, MathHelper::DEFAULT_SCALE, false);
// Result: 0.0
```

### Modulo

```php
$result = MathHelper::modulo(10, 3); // 1.0
$result = MathHelper::modulo(10, 5); // 0.0
$result = MathHelper::modulo(17, 4); // 1.0
```

### Power

```php
$result = MathHelper::powerOf(2, 3); // 8.0
$result = MathHelper::powerOf(5, 2); // 25.0
$result = MathHelper::powerOf(10, 0); // 1.0
$result = MathHelper::powerOf(0, 0); // 1.0
```

### Square Root

```php
$result = MathHelper::squareRoot(16);
// Result: 4.0

$result = MathHelper::squareRoot(2.25);
// Result: 1.5

$result = MathHelper::squareRoot(100);
// Result: 10.0
```

### Comparison

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
$min = MathHelper::min([10, null, 5, null, 3]); // 3 (null values filtered)
```

### Maximum

```php
$max = MathHelper::max([10, 5, 20, 3]); // 20
$max = MathHelper::max([5.5, 2.3, 8.1]); // 8.1
$max = MathHelper::max([]); // 0 (empty array)
$max = MathHelper::max([10, null, 5, null, 20]); // 20 (null values filtered)
```

### Sum

```php
$sum = MathHelper::sum([10, 5, 20, 3]); // 38.0
$sum = MathHelper::sum([1.5, 2.5, 3.5]); // 7.5
$sum = MathHelper::sum([]); // 0.0 (empty array)
$sum = MathHelper::sum([10, null, 5, null, 3]); // 18.0 (null values filtered)
```

### Subtraction Sum

```php
$result = MathHelper::subSum([10, 5, 3]); // -18.0 (0 - 10 - 5 - 3)
$result = MathHelper::subSum([100, 25, 10]); // -135.0
```

### Average

```php
$avg = MathHelper::average([10, 20, 30]); // 20.0
$avg = MathHelper::average([1, 2, 3, 4, 5]); // 3.0
$avg = MathHelper::average([]); // 0.0 (empty array)
$avg = MathHelper::average([10, null, 20, null, 30]); // 20.0 (null values filtered)
```

### Product

```php
$product = MathHelper::product([2, 3, 4]); // 24.0
$product = MathHelper::product([1.5, 2, 3]); // 9.0
$product = MathHelper::product([]); // 1.0 (empty array)
$product = MathHelper::product([2, null, 3, null, 4]); // 24.0 (null values filtered)
```

## Time Conversions

### Minutes to Decimal Hours

```php
// As string
$hours = MathHelper::convertMinutesToDecimalHours(90); // '1.5'
$hours = MathHelper::convertMinutesToDecimalHours(90, 2); // '1.50'
$hours = MathHelper::convertMinutesToDecimalHours(125); // '2.083333333333333'

// As float
$hours = MathHelper::convertMinutesToDecimalHoursAsFloat(90); // 1.5
$hours = MathHelper::convertMinutesToDecimalHoursAsFloat(120); // 2.0
$hours = MathHelper::convertMinutesToDecimalHoursAsFloat(45); // 0.75

// Rounded
$hours = MathHelper::convertMinutesToDecimalHoursRounded(125); // 2.0
$hours = MathHelper::convertMinutesToDecimalHoursRounded(125, 1); // 2.1
$hours = MathHelper::convertMinutesToDecimalHoursRounded(125, 2); // 2.08
```

### Minutes to HH:MM Format

```php
$time = MathHelper::convertMinutesToHourMinutes(125); // '02:05'
$time = MathHelper::convertMinutesToHourMinutes(60);  // '01:00'
$time = MathHelper::convertMinutesToHourMinutes(90);  // '01:30'
$time = MathHelper::convertMinutesToHourMinutes(0);   // '00:00'
$time = MathHelper::convertMinutesToHourMinutes(1439); // '23:59'
```

### HH:MM to Minutes

```php
$minutes = MathHelper::convertHoursMinutesToMinutes('02:05'); // 125
$minutes = MathHelper::convertHoursMinutesToMinutes('01:00'); // 60
$minutes = MathHelper::convertHoursMinutesToMinutes('01:30'); // 90
$minutes = MathHelper::convertHoursMinutesToMinutes('00:00'); // 0
$minutes = MathHelper::convertHoursMinutesToMinutes('23:59'); // 1439
```

### Decimal Hours to Seconds

```php
$seconds = MathHelper::convertDecimalHoursToSeconds(1);   // 3600.0
$seconds = MathHelper::convertDecimalHoursToSeconds(1.5); // 5400.0
$seconds = MathHelper::convertDecimalHoursToSeconds(0.5); // 1800.0

// Rounded
$seconds = MathHelper::convertDecimalHoursToSecondsRounded(1.5); // 5400.0
$seconds = MathHelper::convertDecimalHoursToSecondsRounded(1.5, 0); // 5400.0
```

## Error Handling

### Malformed Input

By default, malformed input throws `MathException`:

<!-- skip-test: exception example -->
```php
try {
    MathHelper::add('not_a_number', 5);
} catch (MathException $e) {
    echo $e->getMessage(); // "Malformed input"
}
```

You can configure it to convert malformed input to zero instead:

```php
MathHelper::setConvertMalformedInputToZero(true);
$result = MathHelper::add('not_a_number', 5);
// $result = 5.0
MathHelper::setConvertMalformedInputToZero(false); // Reset to default
```

### Division by Zero

<!-- skip-test: exception example -->
```php
// Throws exception by default
try {
    MathHelper::divide(10, 0);
} catch (MathException $e) {
    echo $e->getMessage(); // "Division by zero"
}

// Or return 0
$result = MathHelper::divide(10, 0, MathHelper::DEFAULT_SCALE, false); // 0.0
```

### Exception Data

`MathException` contains detailed error information:

<!-- skip-test: exception example -->
```php
try {
    MathHelper::divide(10, 0);
} catch (MathException $e) {
    $data = $e->getData();
    // ['method' => 'divide', 'num1' => '10', 'num2' => '0', 'scale' => 16]

    echo $e->getMessage(); // "Division by zero"
    echo $e->getMethod(); // "divide"
}
```

## Scientific Notation

Automatically handles scientific notation:

```php
$result = MathHelper::add('3.8773213097356E-12', 1); // 1.0000000000038773
$result = MathHelper::multiply('1.5E+2', 2); // 300.0
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
$avg = MathHelper::average([10, null, 20, null, 30]); // 20.0
```

### Time Conversions

```php
// ✅ Use appropriate method for your use case
$hoursString = MathHelper::convertMinutesToDecimalHours(90, 2); // '1.50' for display
$hoursFloat = MathHelper::convertMinutesToDecimalHoursAsFloat(90); // 1.5 for calculations
$hoursRounded = MathHelper::convertMinutesToDecimalHoursRounded(125); // 2.0 for rounding
```

### Financial Calculations

```php
// ✅ Use high precision for money
$price = 19.99;
$quantity = 3;
$tax = 0.19;

$subtotal = MathHelper::multiply($price, $quantity, 4); // 59.9700
$taxAmount = MathHelper::multiply($subtotal, $tax, 4); // 11.3943
$total = MathHelper::add($subtotal, $taxAmount, 2); // 71.36
```

## Constants

```php
MathHelper::DEFAULT_SCALE; // 16
MathHelper::MAXIMUM_PHP_SCALE; // 53
MathHelper::THROW_EXCEPTION_AT_DIVISION_BY_ZERO; // true
```

## Real-World Examples

### Calculate Order Total

```php
$items = [
    ['price' => 19.99, 'quantity' => 2],
    ['price' => 29.99, 'quantity' => 1],
    ['price' => 9.99, 'quantity' => 3],
];

$subtotal = 0;
foreach ($items as $item) {
    $lineTotal = MathHelper::multiply($item['price'], $item['quantity'], 2);
    $subtotal = MathHelper::add($subtotal, $lineTotal, 2);
}

$tax = MathHelper::multiply($subtotal, 0.19, 2); // 19% tax
$total = MathHelper::add($subtotal, $tax, 2);

echo "Subtotal: $" . $subtotal . "\n"; // Subtotal: $99.95
echo "Tax: $" . $tax . "\n";           // Tax: $18.99
echo "Total: $" . $total . "\n";       // Total: $118.94
```

### Calculate Average Response Time

```php
$responseTimes = [125, 230, 180, 95, 310, 145]; // in milliseconds

$avg = MathHelper::average($responseTimes); // 180.833...
$min = MathHelper::min($responseTimes);     // 95
$max = MathHelper::max($responseTimes);     // 310

echo "Average: " . round($avg, 2) . "ms\n"; // Average: 180.83ms
echo "Min: {$min}ms\n";                     // Min: 95ms
echo "Max: {$max}ms\n";                     // Max: 310ms
```

### Time Tracking

```php
// Convert work hours to different formats
$minutesWorked = 485; // 8 hours and 5 minutes

$decimalHours = MathHelper::convertMinutesToDecimalHours($minutesWorked, 2); // '8.08'
$timeFormat = MathHelper::convertMinutesToHourMinutes($minutesWorked);       // '08:05'

echo "Worked: {$timeFormat} ({$decimalHours} hours)\n";
// Worked: 08:05 (8.08 hours)

// Convert back
$minutes = MathHelper::convertHoursMinutesToMinutes('08:05'); // 485
```

### Calculate Percentage

```php
$total = 1000;
$part = 250;

// Calculate percentage
$percentage = MathHelper::divide($part, $total, 4);
$percentage = MathHelper::multiply($percentage, 100, 2);

echo "{$part} is {$percentage}% of {$total}\n";
// 250 is 25.00% of 1000
```

### Calculate Compound Interest

```php
$principal = 10000;
$rate = 0.05; // 5%
$years = 10;

$amount = $principal;
for ($i = 0; $i < $years; $i++) {
    $interest = MathHelper::multiply($amount, $rate, 2);
    $amount = MathHelper::add($amount, $interest, 2);
}

echo "After {$years} years: $" . $amount . "\n";
// After 10 years: $16288.95
```

## See Also

- [ConfigHelper](/helpers/config-helper/) - Configuration helper
- [EnvHelper](/helpers/env-helper/) - Environment variable helper
- [Core Concepts: Type System](/core-concepts/type-system/) - Type casting and conversion

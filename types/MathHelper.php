<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\Helpers\MathHelper;
use function PHPStan\Testing\assertType;

// Test add
$result = MathHelper::add(10, 5);
assertType('float', $result);

$result = MathHelper::add(10.5, 5.3, 2);
assertType('float', $result);

// Test subtract
$result = MathHelper::subtract(10, 5);
assertType('float', $result);

// Test multiply
$result = MathHelper::multiply(10, 5);
assertType('float', $result);

// Test divide
$result = MathHelper::divide(10, 5);
assertType('float', $result);

// Test sum
$result = MathHelper::sum([10, 20, 30]);
assertType('float', $result);

// Test subSum
$result = MathHelper::subSum([10, 20, 30]);
assertType('float', $result);

// Test average
$result = MathHelper::average([10, 20, 30]);
assertType('float', $result);

// Test product
$result = MathHelper::product([2, 3, 4]);
assertType('float', $result);

// Test min
$result = MathHelper::min([10, 20, 30]);
assertType('float|int', $result);

// Test max
$result = MathHelper::max([10, 20, 30]);
assertType('float|int', $result);

// Test convertMinutesToDecimalHours
$result = MathHelper::convertMinutesToDecimalHours(90);
assertType('string', $result);

// Test convertMinutesToDecimalHoursAsFloat
$result = MathHelper::convertMinutesToDecimalHoursAsFloat(90);
assertType('float', $result);

// Test convertMinutesToDecimalHoursRounded
$result = MathHelper::convertMinutesToDecimalHoursRounded(90);
assertType('float', $result);

<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\Helpers\DotPathHelper;
use function PHPStan\Testing\assertType;

// Test segments
$result = DotPathHelper::segments('users.*.name');
assertType('array<int, string>', $result);

// Test buildPrefix
$result = DotPathHelper::buildPrefix('users', '0');
assertType('string', $result);

$result = DotPathHelper::buildPrefix('users', 0);
assertType('string', $result);

// Test isWildcard
$result = DotPathHelper::isWildcard('*');
assertType('bool', $result);

$result = DotPathHelper::isWildcard('name');
assertType('bool', $result);


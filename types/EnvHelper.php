<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\Helpers\EnvHelper;
use function PHPStan\Testing\assertType;

// Test get
$result = EnvHelper::get('APP_NAME', 'default');
assertType('mixed', $result);

// Test string
$result = EnvHelper::string('APP_NAME', 'default');
assertType('string|null', $result);

// Test integer
$result = EnvHelper::integer('APP_PORT', 8080);
assertType('int|null', $result);

// Test float
$result = EnvHelper::float('APP_TIMEOUT', 30.5);
assertType('float|null', $result);

// Test boolean
$result = EnvHelper::boolean('APP_DEBUG', false);
assertType('bool|null', $result);

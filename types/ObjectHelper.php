<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\Helpers\ObjectHelper;
use function PHPStan\Testing\assertType;

// Test class for copying
class TestObject
{
    public function __construct(
        public string $name = 'test',
        public int $value = 42,
    ) {}
}

// Test copy
$original = new TestObject('Alice', 100);
$copy = ObjectHelper::copy($original);
assertType('object', $copy);

// Test copy with recursive flag
$copy = ObjectHelper::copy($original, true);
assertType('object', $copy);

// Test copy with maxLevel
$copy = ObjectHelper::copy($original, true, 5);
assertType('object', $copy);

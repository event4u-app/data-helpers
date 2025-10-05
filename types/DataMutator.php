<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\DataMutator;
use function PHPStan\Testing\assertType;

// Test set with array
$data = [];
$result = DataMutator::set($data, 'user.name', 'Alice');
assertType('array<int|string, mixed>|object', $result);

// Test set with multiple values
$data = [];
$result = DataMutator::set($data, ['user.name' => 'Alice', 'user.age' => 30]);
assertType('array<int|string, mixed>|object', $result);

// Test merge
$data = ['user' => ['name' => 'Alice']];
$result = DataMutator::merge($data, 'user', ['age' => 30]);
assertType('array<int|string, mixed>|object', $result);

// Test unset
$data = ['user' => ['name' => 'Alice', 'age' => 30]];
$result = DataMutator::unset($data, 'user.age');
assertType('array<int|string, mixed>|object', $result);

// Test unset with multiple paths
$data = ['user' => ['name' => 'Alice', 'age' => 30, 'email' => 'alice@example.com']];
$result = DataMutator::unset($data, ['user.age', 'user.email']);
assertType('array<int|string, mixed>|object', $result);

// Test with object
$object = new stdClass();
$object->user = new stdClass();
$result = DataMutator::set($object, 'user.name', 'Alice');
assertType('array<int|string, mixed>|object', $result);

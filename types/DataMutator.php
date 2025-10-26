<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\DataMutator;
use function PHPStan\Testing\assertType;

// Test set with array
$data = [];
$mutator = DataMutator::make($data);
assertType('event4u\DataHelpers\DataMutator', $mutator);
$mutator->set('user.name', 'Alice');
assertType('array<int|string, mixed>|object', $data);

// Test set with multiple values
$data = [];
$mutator = DataMutator::make($data);
$mutator->set(['user.name' => 'Alice', 'user.age' => 30]);
assertType('array<int|string, mixed>|object', $data);

// Test merge
$data = ['user' => ['name' => 'Alice']];
$mutator = DataMutator::make($data);
$mutator->merge('user', ['age' => 30]);
assertType('array<int|string, mixed>|object', $data);

// Test unset
$data = ['user' => ['name' => 'Alice', 'age' => 30]];
$mutator = DataMutator::make($data);
$mutator->unset('user.age');
assertType('array<int|string, mixed>|object', $data);

// Test unset with multiple paths
$data = ['user' => ['name' => 'Alice', 'age' => 30, 'email' => 'alice@example.com']];
$mutator = DataMutator::make($data);
$mutator->unset(['user.age', 'user.email']);
assertType('array<int|string, mixed>|object', $data);

// Test with object
$object = new stdClass();
$object->user = new stdClass();
$mutator = DataMutator::make($object);
$mutator->set('user.name', 'Alice');
assertType('array<int|string, mixed>|object', $object);

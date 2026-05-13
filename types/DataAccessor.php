<?php

declare(strict_types=1);

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\DataAccessor;
use function PHPStan\Testing\assertType;

// Test basic array access
$data = ['user' => ['name' => 'Alice', 'age' => 30]];
$accessor = new DataAccessor($data);

assertType('mixed', $accessor->get('user.name'));
assertType('int|null', $accessor->getInt('user.name'));
assertType('string|null', $accessor->getString('user.name'));
assertType('bool|null', $accessor->getBool('user.active'));
assertType('float|null', $accessor->getFloat('user.score'));
assertType('array<int|string, mixed>|null', $accessor->getArray('user.profile'));

// Test with default values - can still return null if value exists but is null
assertType('string|null', $accessor->getString('user.name', 'default'));
assertType('int|null', $accessor->getInt('user.age', 0));
assertType('bool|null', $accessor->getBool('user.active', false));
assertType('float|null', $accessor->getFloat('user.score', 0.0));
assertType('array<int|string, mixed>|null', $accessor->getArray('user.profile', []));

// Test wildcard access - returns mixed because it can be anything
assertType('mixed', $accessor->get('users.*.name'));
assertType('mixed', $accessor->get('users.*.emails.*.value'));

// Test collection getters for wildcards
assertType('event4u\DataHelpers\DataCollection<int>', $accessor->getIntCollection('users.*.age'));
assertType('event4u\DataHelpers\DataCollection<string>', $accessor->getStringCollection('users.*.name'));
assertType('event4u\DataHelpers\DataCollection<bool>', $accessor->getBoolCollection('users.*.active'));
assertType('event4u\DataHelpers\DataCollection<float>', $accessor->getFloatCollection('users.*.score'));
assertType(
    'event4u\DataHelpers\DataCollection<array<int|string, mixed>>',
    $accessor->getArrayCollection('users.*.profile')
);

<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\DataMapperProperty;
use event4u\DataHelpers\DataMapper\DataMapperResult;
use event4u\DataHelpers\DataMapper\FluentDataMapper;
use event4u\DataHelpers\DataMapper\MapperQuery;
use function PHPStan\Testing\assertType;

// Test source data
$source = [
    'users' => [
        ['id' => 1, 'name' => 'Alice', 'age' => 30],
        ['id' => 2, 'name' => 'Bob', 'age' => 25],
    ],
];

// Test template
$template = [
    'items' => [
        '*' => [
            'id' => '{{ users.*.id }}',
            'name' => '{{ users.*.name }}',
        ],
    ],
];

// Test DataMapper::source() factory
$mapper = DataMapper::source($source);
assertType(FluentDataMapper::class, $mapper);

// Test template() method
$mapper = $mapper->template($template);
assertType(FluentDataMapper::class, $mapper);

// Test target() method
$mapper = $mapper->target([]);
assertType(FluentDataMapper::class, $mapper);

// Test map() method
$result = $mapper->map();
assertType(DataMapperResult::class, $result);

// Test query() method
$query = $mapper->query('users.*');
assertType(MapperQuery::class, $query);

// Test property() method
$property = $mapper->property('items.*.name');
assertType(DataMapperProperty::class, $property);

// Test skipNull() method
$mapper = $mapper->skipNull(true);
assertType(FluentDataMapper::class, $mapper);

// Test reindexWildcard() method
$mapper = $mapper->reindexWildcard(true);
assertType(FluentDataMapper::class, $mapper);

// Test copy() method
$copy = $mapper->copy();
assertType(FluentDataMapper::class, $copy);

// Test DataMapperResult methods
$target = $result->getTarget();
assertType('mixed', $target);

$array = $result->toArray();
assertType('array<int|string, mixed>', $array);

$json = $result->toJson();
assertType('string', $json);


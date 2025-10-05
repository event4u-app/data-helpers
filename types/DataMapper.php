<?php

/** @noinspection PhpExpressionResultUnusedInspection */

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\DataMapperPipeline;
use function PHPStan\Testing\assertType;

// Test simple mapping
$source = ['firstName' => 'Alice', 'lastName' => 'Smith'];
$target = [];
$mapping = ['name' => 'firstName', 'surname' => 'lastName'];
$result = DataMapper::map($source, $target, $mapping);
assertType('mixed', $result);

// Test structured mapping
$source = ['user' => ['name' => 'Alice']];
$target = [];
$mapping = [
    ['target' => 'profile.name', 'source' => 'user.name'],
];
$result = DataMapper::map($source, $target, $mapping);
assertType('mixed', $result);

// Test autoMap
$source = ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'];
$target = ['id' => null, 'name' => null, 'email' => null];
$result = DataMapper::autoMap($source, $target);
assertType('mixed', $result);

// Test pipe
$pipeline = DataMapper::pipe([]);
assertType(DataMapperPipeline::class, $pipeline);

// Test mapFromTemplate
$template = ['user' => ['name' => '{{ user.firstName }}']];
$sources = ['user' => ['firstName' => 'Alice']];
$result = DataMapper::mapFromTemplate($template, $sources);
assertType('array<string, mixed>', $result);

// Test mapToTargetsFromTemplate
$data = ['user' => ['name' => 'Alice']];
$template = ['userTarget' => ['profile.name' => 'user.name']];
$targets = ['userTarget' => []];
$result = DataMapper::mapToTargetsFromTemplate($data, $template, $targets);
assertType('array<string, mixed>', $result);

<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MappingOptions;

test('MappingOptions → it uses default options', function(): void {
    $source = ['name' => 'John', 'age' => null];
    $target = [];
    $mapping = ['fullName' => '{{ name }}', 'years' => '{{ age }}'];

    $result = DataMapper::source($source)
        ->target($target)
        ->template($mapping)
        ->options(MappingOptions::default())
        ->map()
        ->getTarget();

    expect($result)->toBe(['fullName' => 'John']);
});

test('MappingOptions → it includes null values with includeNull()', function(): void {
    $source = ['name' => 'John', 'age' => null];
    $target = [];
    $mapping = ['fullName' => '{{ name }}', 'years' => '{{ age }}'];

    $result = DataMapper::source($source)
        ->target($target)
        ->template($mapping)
        ->options(MappingOptions::includeNull())
        ->map()
        ->getTarget();

    expect($result)->toBe(['fullName' => 'John', 'years' => null]);
});

test('MappingOptions → it reindexes wildcard results with reindexed()', function(): void {
    $source = ['items' => [5 => 'a', 10 => 'b', 15 => 'c']];
    $target = [];
    $mapping = ['values.*' => '{{ items.* }}'];

    $result = DataMapper::source($source)
        ->target($target)
        ->template($mapping)
        ->options(MappingOptions::reindexed())
        ->map()
        ->getTarget();

    expect($result)->toBe(['values' => [0 => 'a', 1 => 'b', 2 => 'c']]);
});

test('MappingOptions → it chains multiple with methods', function(): void {
    $source = ['name' => '  John  ', 'age' => null];
    $target = [];
    $mapping = ['fullName' => '{{ name }}', 'years' => '{{ age }}'];

    $options = MappingOptions::default()
        ->withSkipNull(false)
        ->withTrimValues(false);

    $result = DataMapper::source($source)
        ->target($target)
        ->template($mapping)
        ->options($options)
        ->map()
        ->getTarget();

    expect($result)->toBe(['fullName' => '  John  ', 'years' => null]);
});

test('MappingOptions → it works with mapFromFile()', function(): void {
    $filePath = __DIR__ . '/../../Utils/json/data_mapper_from_file_test.json';
    $target = [];
    $mapping = ['companyName' => '{{ company.name }}'];

    $result = DataMapper::sourceFile($filePath)->target($target)->template($mapping)->options(
        MappingOptions::default()
    )->map()->getTarget();

    expect($result)->toHaveKey('companyName')
        ->and($result['companyName'])->toBe('TechCorp Solutions');
});

test('MappingOptions → backward compatibility with old API', function(): void {
    $source = ['name' => 'John', 'age' => null];
    $target = [];
    $mapping = ['fullName' => '{{ name }}', 'years' => '{{ age }}'];

    // Old API still works
    $result = DataMapper::source($source)
        ->target($target)
        ->template($mapping)
        ->map()
        ->getTarget();

    expect($result)->toBe(['fullName' => 'John']);
});

test('MappingOptions → it converts to array', function(): void {
    $options = MappingOptions::default()
        ->withSkipNull(false)
        ->withReindexWildcard(true);

    $array = $options->toArray();

    expect($array)->toBe([
        'skipNull' => false,
        'reindexWildcard' => true,
        'hooks' => [],
        'trimValues' => true,
        'caseInsensitiveReplace' => false,
    ]);
});

<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\DataMapperResult;
use event4u\DataHelpers\DataMapper\FluentDataMapper;

it('can create FluentDataMapper from source', function () {
    $source = ['name' => 'John', 'age' => 30];
    $mapper = DataMapper::source($source);

    expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
});

it('can create FluentDataMapper using from()', function () {
    $source = ['name' => 'John', 'age' => 30];
    $mapper = DataMapper::source($source);

    expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
});

it('can set target and template', function () {
    $source = ['firstName' => 'John', 'lastName' => 'Doe'];
    $target = [];
    $template = [
        'first' => '{{ firstName }}',
        'last' => '{{ lastName }}',
    ];

    $mapper = DataMapper::source($source)
        ->target($target)
        ->template($template);

    expect($mapper)->toBeInstanceOf(FluentDataMapper::class);
});

it('can execute mapping and return DataMapperResult', function () {
    $source = ['firstName' => 'John', 'lastName' => 'Doe'];
    $target = [];
    $template = [
        'first' => '{{ firstName }}',
        'last' => '{{ lastName }}',
    ];

    $result = DataMapper::source($source)
        ->target($target)
        ->template($template)
        ->map();

    expect($result)->toBeInstanceOf(DataMapperResult::class);
    expect($result->getTarget())->toBeArray();
    expect($result->getTarget()['first'])->toBe('John');
    expect($result->getTarget()['last'])->toBe('Doe');
});

it('can convert result to JSON', function () {
    $source = ['firstName' => 'John', 'lastName' => 'Doe'];
    $target = [];
    $template = [
        'first' => '{{ firstName }}',
        'last' => '{{ lastName }}',
    ];

    $result = DataMapper::source($source)
        ->target($target)
        ->template($template)
        ->map();

    $json = $result->toJson();

    expect($json)->toBeString();
    $decoded = json_decode($json, true);
    expect($decoded)->toHaveKey('first');
    expect($decoded)->toHaveKey('last');
});

it('can copy mapper', function () {
    $source = ['name' => 'John'];
    $mapper = DataMapper::source($source)
        ->template(['name' => '{{ name }}']);

    $copy = $mapper->copy();

    expect($copy)->toBeInstanceOf(FluentDataMapper::class);
    expect($copy)->not->toBe($mapper);
});

it('can chain multiple configurations', function () {
    $source = ['firstName' => 'John', 'lastName' => 'Doe', 'age' => 30];
    $target = [];
    $template = [
        'first' => '{{ firstName }}',
        'last' => '{{ lastName }}',
        'age' => '{{ age }}',
    ];

    $result = DataMapper::source($source)
        ->target($target)
        ->template($template)
        ->skipNull(true)
        ->trimValues(true)
        ->map();

    expect($result->getTarget())->toHaveKeys(['first', 'last', 'age']);
});

it('supports nested mappings', function () {
    $source = [
        'user' => [
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ],
    ];
    $target = [];
    $template = [
        'profile.fullname' => '{{ user.name }}',
        'profile.contact.email' => '{{ user.email }}',
    ];

    $result = DataMapper::source($source)
        ->target($target)
        ->template($template)
        ->map();

    expect($result->getTarget())->toBe([
        'profile' => [
            'fullname' => 'Alice',
            'contact' => [
                'email' => 'alice@example.com',
            ],
        ],
    ]);
});

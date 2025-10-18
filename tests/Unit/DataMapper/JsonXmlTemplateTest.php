<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('JSON Template Mapping', function(): void {
    it('maps from JSON template string', function(): void {
        $jsonTemplate = json_encode([
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
        ]);

        assert(is_string($jsonTemplate));

        $sources = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ];

        $result = DataMapper::source($sources)->template($jsonTemplate)->map()->getTarget();

        expect($result)->toBe([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    it('supports wildcard operators in JSON templates', function(): void {
        $jsonTemplate = json_encode([
            'filtered_items' => [
                'WHERE' => [
                    '{{ items.*.type }}' => 'active',
                ],
                'ORDER BY' => [
                    '{{ items.*.name }}' => 'asc',
                ],
                'LIMIT' => 2,
                '*' => [
                    'id' => '{{ items.*.id }}',
                    'name' => '{{ items.*.name }}',
                ],
            ],
        ]);

        assert(is_string($jsonTemplate));

        $sources = [
            'items' => [
                ['id' => 1, 'name' => 'Zebra', 'type' => 'active'],
                ['id' => 2, 'name' => 'Apple', 'type' => 'inactive'],
                ['id' => 3, 'name' => 'Banana', 'type' => 'active'],
                ['id' => 4, 'name' => 'Cherry', 'type' => 'active'],
            ],
        ];

        $result = DataMapper::source($sources)->template($jsonTemplate)->reindexWildcard(true)->map()->getTarget();

        expect($result['filtered_items'])->toBe([
            ['id' => 3, 'name' => 'Banana'],
            ['id' => 4, 'name' => 'Cherry'],
        ]);
    });

    it('supports all wildcard operators in JSON templates', function(): void {
        $jsonTemplate = json_encode([
            'products' => [
                'WHERE' => [
                    '{{ items.*.category }}' => 'electronics',
                ],
                'ORDER BY' => [
                    '{{ items.*.price }}' => 'desc',
                ],
                'OFFSET' => 1,
                'LIMIT' => 2,
                '*' => [
                    'name' => '{{ items.*.name }}',
                    'price' => '{{ items.*.price }}',
                ],
            ],
        ]);

        assert(is_string($jsonTemplate));

        $sources = [
            'items' => [
                ['name' => 'Laptop', 'price' => 1200, 'category' => 'electronics'],
                ['name' => 'Mouse', 'price' => 25, 'category' => 'electronics'],
                ['name' => 'Keyboard', 'price' => 75, 'category' => 'electronics'],
                ['name' => 'Monitor', 'price' => 300, 'category' => 'electronics'],
                ['name' => 'Desk', 'price' => 500, 'category' => 'furniture'],
            ],
        ];

        $result = DataMapper::source($sources)->template($jsonTemplate)->reindexWildcard(true)->map()->getTarget();

        expect($result['products'])->toBe([
            ['name' => 'Monitor', 'price' => 300],
            ['name' => 'Keyboard', 'price' => 75],
        ]);
    });
});

describe('XML Template Mapping', function(): void {
    it('maps from XML template string', function(): void {
        $xmlTemplate = <<<XML
<?xml version="1.0"?>
<template>
    <name>{{ user.name }}</name>
    <email>{{ user.email }}</email>
</template>
XML;

        $sources = [
            'user' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ],
        ];

        $result = DataMapper::source($sources)->template($xmlTemplate)->map()->getTarget();

        expect($result)->toBe([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    });

    it('supports wildcard operators in XML templates', function(): void {
        $xmlTemplate = <<<XML
<?xml version="1.0"?>
<template>
    <filtered_items>
        <WHERE>
            <item_type>{{ items.*.type }}</item_type>
            <expected>active</expected>
        </WHERE>
        <ORDER_BY>
            <field>{{ items.*.name }}</field>
            <direction>asc</direction>
        </ORDER_BY>
        <LIMIT>2</LIMIT>
        <wildcard>
            <id>{{ items.*.id }}</id>
            <name>{{ items.*.name }}</name>
        </wildcard>
    </filtered_items>
</template>
XML;

        $sources = [
            'items' => [
                ['id' => 1, 'name' => 'Zebra', 'type' => 'active'],
                ['id' => 2, 'name' => 'Apple', 'type' => 'inactive'],
                ['id' => 3, 'name' => 'Banana', 'type' => 'active'],
                ['id' => 4, 'name' => 'Cherry', 'type' => 'active'],
            ],
        ];

        $result = DataMapper::source($sources)->template($xmlTemplate)->reindexWildcard(true)->map()->getTarget();

        // XML converts to nested structure
        expect($result['filtered_items'])->toBeArray();
        expect($result['filtered_items']['wildcard'])->toBeArray();
    });

    it('throws exception for invalid template string', function(): void {
        $invalidTemplate = 'not json or xml';

        $sources = ['user' => ['name' => 'Test']];

        expect(fn(): array => DataMapper::source($sources)->template($invalidTemplate)->map()->getTarget())
            ->toThrow(InvalidArgumentException::class, 'Template must be a valid JSON or XML string, or an array');
    });
});

describe('Template Format Detection', function(): void {
    it('accepts array templates', function(): void {
        $template = [
            'name' => '{{ user.name }}',
        ];

        $sources = ['user' => ['name' => 'Test User']];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->toBe(['name' => 'Test User']);
    });

    it('detects and parses JSON templates', function(): void {
        $jsonTemplate = '{"name":"{{ user.name }}"}';

        $sources = ['user' => ['name' => 'JSON User']];

        $result = DataMapper::source($sources)->template($jsonTemplate)->map()->getTarget();

        expect($result)->toBe(['name' => 'JSON User']);
    });

    it('detects and parses XML templates', function(): void {
        $xmlTemplate = '<template><name>{{ user.name }}</name></template>';

        $sources = ['user' => ['name' => 'XML User']];

        $result = DataMapper::source($sources)->template($xmlTemplate)->map()->getTarget();

        expect($result)->toBe(['name' => 'XML User']);
    });
});


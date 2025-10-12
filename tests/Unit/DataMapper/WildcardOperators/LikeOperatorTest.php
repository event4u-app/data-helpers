<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Support\WildcardOperators\LikeOperator;

describe('LIKE Operator', function(): void {
    it('filters items using % wildcard for any characters', function(): void {
        $items = [
            0 => ['name' => 'Laptop'],
            1 => ['name' => 'Desktop'],
            2 => ['name' => 'Tablet'],
            3 => ['name' => 'Smartphone'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.name }}' => '%top%',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('Laptop');
        expect($result[1]['name'])->toBe('Desktop');
    });

    it('filters items using _ wildcard for single character', function(): void {
        $items = [
            0 => ['code' => 'A1'],
            1 => ['code' => 'A2'],
            2 => ['code' => 'B1'],
            3 => ['code' => 'A12'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.code }}' => 'A_',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(2);
        expect($result[0]['code'])->toBe('A1');
        expect($result[1]['code'])->toBe('A2');
    });

    it('is case-insensitive by default', function(): void {
        $items = [
            0 => ['name' => 'LAPTOP'],
            1 => ['name' => 'laptop'],
            2 => ['name' => 'LaPtOp'],
            3 => ['name' => 'Desktop'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.name }}' => 'laptop',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(3);
    });

    it('supports case-sensitive matching', function(): void {
        $items = [
            0 => ['name' => 'LAPTOP'],
            1 => ['name' => 'laptop'],
            2 => ['name' => 'Desktop'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.name }}' => [
                'pattern' => 'laptop',
                'case_sensitive' => true,
            ],
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(1);
        expect($result[1]['name'])->toBe('laptop');
    });

    it('handles multiple LIKE conditions (AND logic)', function(): void {
        $items = [
            0 => ['name' => 'Laptop Pro', 'category' => 'electronics'],
            1 => ['name' => 'Desktop Pro', 'category' => 'electronics'],
            2 => ['name' => 'Laptop Basic', 'category' => 'electronics'],
            3 => ['name' => 'Desk Pro', 'category' => 'furniture'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.name }}' => '%Pro',
            '{{ items.*.category }}' => 'electronics',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('Laptop Pro');
        expect($result[1]['name'])->toBe('Desktop Pro');
    });

    it('handles pattern at start of string', function(): void {
        $items = [
            0 => ['email' => 'john@example.com'],
            1 => ['email' => 'jane@example.com'],
            2 => ['email' => 'admin@test.com'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.email }}' => 'j%',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(2);
    });

    it('handles pattern at end of string', function(): void {
        $items = [
            0 => ['email' => 'john@example.com'],
            1 => ['email' => 'jane@example.com'],
            2 => ['email' => 'admin@test.com'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.email }}' => '%example.com',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(2);
    });

    it('handles exact match without wildcards', function(): void {
        $items = [
            0 => ['status' => 'active'],
            1 => ['status' => 'inactive'],
            2 => ['status' => 'active'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.status }}' => 'active',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(2);
    });

    it('returns empty array when no matches', function(): void {
        $items = [
            0 => ['name' => 'Laptop'],
            1 => ['name' => 'Desktop'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.name }}' => 'Tablet%',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toBe([]);
    });

    it('handles empty items array', function(): void {
        $result = LikeOperator::filter([], ['{{ items.*.name }}' => '%test%'], [], []);

        expect($result)->toBe([]);
    });

    it('handles empty config array', function(): void {
        $items = [
            0 => ['name' => 'Test'],
        ];

        $result = LikeOperator::filter($items, [], [], []);

        expect($result)->toBe($items);
    });

    it('works with numeric values', function(): void {
        $items = [
            0 => ['code' => 12345],
            1 => ['code' => 54321],
            2 => ['code' => 12999],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.code }}' => '12%',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(2);
    });

    it('handles special regex characters in pattern', function(): void {
        $items = [
            0 => ['name' => 'Test (Pro)'],
            1 => ['name' => 'Test [Basic]'],
            2 => ['name' => 'Test {Advanced}'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.name }}' => 'Test (Pro)',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(1);
        expect($result[0]['name'])->toBe('Test (Pro)');
    });

    it('combines % and _ wildcards', function(): void {
        $items = [
            0 => ['code' => 'ABC123'],
            1 => ['code' => 'ABD123'],
            2 => ['code' => 'ABC456'],
            3 => ['code' => 'XYZ123'],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.code }}' => 'AB_123',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(2);
        expect($result[0]['code'])->toBe('ABC123');
        expect($result[1]['code'])->toBe('ABD123');
    });

    it('filters non-string values gracefully', function(): void {
        $items = [
            0 => ['value' => 'text'],
            1 => ['value' => null],
            2 => ['value' => ['array']],
        ];

        $sources = ['items' => $items];
        $config = [
            '{{ items.*.value }}' => 'text',
        ];

        $result = LikeOperator::filter($items, $config, $sources, []);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe('text');
    });
});


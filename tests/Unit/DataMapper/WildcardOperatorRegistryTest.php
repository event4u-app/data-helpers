<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Support\WildcardOperatorRegistry;

describe('Wildcard Operator Registry', function(): void {
    beforeEach(function(): void {
        // Note: We don't clear the registry here because it would remove built-in operators
        // Tests that need a clean slate should handle it themselves

        $this->sources = [
            'items' => [
                ['id' => 1, 'category' => 'A', 'value' => 100],
                ['id' => 2, 'category' => 'B', 'value' => 200],
                ['id' => 3, 'category' => 'A', 'value' => 150],
                ['id' => 4, 'category' => 'B', 'value' => 250],
                ['id' => 5, 'category' => 'A', 'value' => 120],
            ],
        ];
    });

    it('has built-in WHERE operator', function(): void {
        expect(WildcardOperatorRegistry::has('WHERE'))->toBeTrue();
        expect(WildcardOperatorRegistry::has('where'))->toBeTrue();
    });

    it('has built-in ORDER BY operator', function(): void {
        expect(WildcardOperatorRegistry::has('ORDER BY'))->toBeTrue();
        expect(WildcardOperatorRegistry::has('ORDER_BY'))->toBeTrue();
        expect(WildcardOperatorRegistry::has('order by'))->toBeTrue();
        expect(WildcardOperatorRegistry::has('ORDER'))->toBeTrue();
    });

    it('can register custom operator', function(): void {
        WildcardOperatorRegistry::register('LIMIT', function(array $items, mixed $config): array {
            if (!is_int($config)) {
                return $items;
            }
            $result = [];
            $count = 0;
            foreach ($items as $index => $item) {
                if ($count >= $config) {
                    break;
                }
                $result[$index] = $item;
                $count++;
            }
            return $result;
        });

        expect(WildcardOperatorRegistry::has('LIMIT'))->toBeTrue();
    });

    it('can use custom LIMIT operator', function(): void {
        WildcardOperatorRegistry::register('LIMIT', function(array $items, mixed $config): array {
            if (!is_int($config)) {
                return $items;
            }
            $result = [];
            $count = 0;
            foreach ($items as $index => $item) {
                if ($count >= $config) {
                    break;
                }
                $result[$index] = $item;
                $count++;
            }
            return $result;
        });

        $template = [
            'limited_items' => [
                'LIMIT' => 2,
                '*' => [
                    'id' => '{{ items.*.id }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['limited_items'])->toHaveCount(2);
        expect($result['limited_items'][0]['id'])->toBe(1);
        expect($result['limited_items'][1]['id'])->toBe(2);
    });

    it('can use custom GROUP BY operator', function(): void {
        WildcardOperatorRegistry::register('GROUP BY', function(array $items, mixed $config, mixed $sources): array {
            if (!is_string($config)) {
                return $items;
            }

            // Simple grouping by field
            $grouped = [];
            foreach ($items as $index => $item) {
                // Extract field value from config (e.g., '{{ items.*.category }}')
                $fieldPath = str_replace('*', (string)$index, $config);

                if (str_starts_with($fieldPath, '{{') && str_ends_with($fieldPath, '}}')) {
                    $path = trim(substr($fieldPath, 2, -2));
                    $accessor = new DataAccessor($sources);
                    $groupKey = $accessor->get($path);
                } else {
                    $groupKey = $item[$config] ?? 'default';
                }

                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [];
                }
                $grouped[$groupKey][] = $item;
            }

            // Flatten back to single array with first item of each group
            $result = [];
            foreach ($grouped as $group) {
                $result[] = $group[0];
            }

            return $result;
        });

        $template = [
            'grouped_items' => [
                'GROUP BY' => '{{ items.*.category }}',
                '*' => [
                    'id' => '{{ items.*.id }}',
                    'category' => '{{ items.*.category }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['grouped_items'])->toHaveCount(2);
        expect($result['grouped_items'][0]['category'])->toBe('A');
        expect($result['grouped_items'][1]['category'])->toBe('B');
    });

    it('can combine multiple operators', function(): void {
        WildcardOperatorRegistry::register('LIMIT', function(array $items, mixed $config): array {
            if (!is_int($config)) {
                return $items;
            }
            $result = [];
            $count = 0;
            foreach ($items as $index => $item) {
                if ($count >= $config) {
                    break;
                }
                $result[$index] = $item;
                $count++;
            }
            return $result;
        });

        $template = [
            'filtered_limited_items' => [
                'WHERE' => [
                    '{{ items.*.category }}' => 'A',
                ],
                'ORDER BY' => [
                    '{{ items.*.value }}' => 'DESC',
                ],
                'LIMIT' => 2,
                '*' => [
                    'id' => '{{ items.*.id }}',
                    'value' => '{{ items.*.value }}',
                ],
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, $this->sources, true, true);

        expect($result['filtered_limited_items'])->toHaveCount(2);
        expect($result['filtered_limited_items'][0]['value'])->toBe(150);
        expect($result['filtered_limited_items'][1]['value'])->toBe(120);
    });

    it('can unregister operator', function(): void {
        WildcardOperatorRegistry::register('CUSTOM', fn(array $items): array => $items);

        expect(WildcardOperatorRegistry::has('CUSTOM'))->toBeTrue();

        WildcardOperatorRegistry::unregister('CUSTOM');

        expect(WildcardOperatorRegistry::has('CUSTOM'))->toBeFalse();
    });

    it('lists all registered operators', function(): void {
        $operators = WildcardOperatorRegistry::all();

        expect($operators)->toContain('WHERE');
        expect($operators)->toContain('ORDERBY');
        expect($operators)->toContain('ORDER');
    });

    it('normalizes operator names', function(): void {
        WildcardOperatorRegistry::register('my operator', fn(array $items): array => $items);

        expect(WildcardOperatorRegistry::has('MY OPERATOR'))->toBeTrue();
        expect(WildcardOperatorRegistry::has('my_operator'))->toBeTrue();
        expect(WildcardOperatorRegistry::has('MY_OPERATOR'))->toBeTrue();
    });
});


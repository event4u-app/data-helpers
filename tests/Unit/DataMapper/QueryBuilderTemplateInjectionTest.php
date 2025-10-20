<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\FluentDataMapper;

describe('Query Builder Template Injection', function(): void {
    describe('Single Wildcard with WHERE', function(): void {
        it('injects WHERE operator into template for simple wildcard', function(): void {
            $template = [
                'user' => [
                    'forename' => 'Klaus',
                    'surname' => 'Meyer',
                    'roles' => [
                        '*' => [
                            'name' => '{{ roles.*.name }}',
                        ],
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $result = $dataMapper->query('roles.*')->where('name', '=', 'admin')->end();
            expect($result)->toBeInstanceOf(FluentDataMapper::class);

            // Get the modified template by calling map() with withQuery=true
            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            // Expected: WHERE operator should be injected
            expect($modifiedTemplate['user']['roles'])->toHaveKey('WHERE');
            expect($modifiedTemplate['user']['roles']['WHERE'])->toBe([
                '{{ roles.*.name }}' => 'admin',  // '=' operator uses simple value format
            ]);
        });

        it('injects WHERE operator with multiple conditions', function(): void {
            $template = [
                'users' => [
                    '*' => [
                        'name' => '{{ users.*.name }}',
                        'age' => '{{ users.*.age }}',
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $dataMapper->query('users.*')
                ->where('age', '>', 18)
                ->where('name', '!=', 'John');

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            expect($modifiedTemplate['users'])->toHaveKey('WHERE');
            expect($modifiedTemplate['users']['WHERE'])->toBe([
                '{{ users.*.age }}' => ['>', 18],  // Non-'=' operators use array format
                '{{ users.*.name }}' => ['!=', 'John'],
            ]);
        });
    });

    describe('Single Wildcard with ORDER BY', function(): void {
        it('injects ORDER BY operator into template', function(): void {
            $template = [
                'products' => [
                    '*' => [
                        'name' => '{{ products.*.name }}',
                        'price' => '{{ products.*.price }}',
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $result = $dataMapper->query('products.*')->orderBy('price', 'DESC')->end();
            expect($result)->toBeInstanceOf(FluentDataMapper::class);

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            expect($modifiedTemplate['products'])->toHaveKey('ORDER BY');
            expect($modifiedTemplate['products']['ORDER BY'])->toBe([
                '{{ products.*.price }}' => 'DESC',
            ]);
        });

        it('injects ORDER BY with multiple fields', function(): void {
            $template = [
                'products' => [
                    '*' => [
                        'name' => '{{ products.*.name }}',
                        'price' => '{{ products.*.price }}',
                        'rating' => '{{ products.*.rating }}',
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $dataMapper->query('products.*')
                ->orderBy('rating', 'DESC')
                ->orderBy('price', 'ASC');

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            expect($modifiedTemplate['products'])->toHaveKey('ORDER BY');
            expect($modifiedTemplate['products']['ORDER BY'])->toBe([
                '{{ products.*.rating }}' => 'DESC',
                '{{ products.*.price }}' => 'ASC',
            ]);
        });
    });

    describe('Single Wildcard with LIMIT and OFFSET', function(): void {
        it('injects LIMIT operator into template', function(): void {
            $template = [
                'items' => [
                    '*' => [
                        'id' => '{{ items.*.id }}',
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $result = $dataMapper->query('items.*')->limit(10)->end();
            expect($result)->toBeInstanceOf(FluentDataMapper::class);

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            expect($modifiedTemplate['items'])->toHaveKey('LIMIT');
            expect($modifiedTemplate['items']['LIMIT'])->toBe(10);
        });

        it('injects LIMIT and OFFSET operators into template', function(): void {
            $template = [
                'items' => [
                    '*' => [
                        'id' => '{{ items.*.id }}',
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $result = $dataMapper->query('items.*')->limit(10)->offset(5)->end();
            expect($result)->toBeInstanceOf(FluentDataMapper::class);

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            expect($modifiedTemplate['items'])->toHaveKey('LIMIT');
            expect($modifiedTemplate['items']['LIMIT'])->toBe(10);
            expect($modifiedTemplate['items'])->toHaveKey('OFFSET');
            expect($modifiedTemplate['items']['OFFSET'])->toBe(5);
        });
    });

    describe('Single Wildcard with GROUP BY', function(): void {
        it('injects GROUP BY operator with single field', function(): void {
            $template = [
                'sales' => [
                    '*' => [
                        'category' => '{{ sales.*.category }}',
                        'amount' => '{{ sales.*.amount }}',
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $result = $dataMapper->query('sales.*')->groupBy('category')->end();
            expect($result)->toBeInstanceOf(FluentDataMapper::class);

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            expect($modifiedTemplate['sales'])->toHaveKey('GROUP BY');
            expect($modifiedTemplate['sales']['GROUP BY'])->toBe([
                'field' => '{{ sales.*.category }}',
            ]);
        });

        it('injects GROUP BY operator with multiple fields', function(): void {
            $template = [
                'sales' => [
                    '*' => [
                        'category' => '{{ sales.*.category }}',
                        'region' => '{{ sales.*.region }}',
                        'amount' => '{{ sales.*.amount }}',
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $dataMapper->query('sales.*')
                ->groupBy('category')
                ->groupBy('region');

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            expect($modifiedTemplate['sales'])->toHaveKey('GROUP BY');
            expect($modifiedTemplate['sales']['GROUP BY'])->toBe([
                'fields' => [
                    '{{ sales.*.category }}',
                    '{{ sales.*.region }}',
                ],
            ]);
        });
    });

    describe('Combined Operators', function(): void {
        it('injects multiple operators (WHERE + ORDER BY + LIMIT)', function(): void {
            $template = [
                'products' => [
                    '*' => [
                        'name' => '{{ products.*.name }}',
                        'price' => '{{ products.*.price }}',
                        'category' => '{{ products.*.category }}',
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $dataMapper->query('products.*')
                ->where('category', '=', 'Electronics')
                ->orderBy('price', 'DESC')
                ->limit(5);

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            expect($modifiedTemplate['products'])->toHaveKey('WHERE');
            expect($modifiedTemplate['products'])->toHaveKey('ORDER BY');
            expect($modifiedTemplate['products'])->toHaveKey('LIMIT');
        });
    });

    describe('Nested Wildcards', function(): void {
        it('injects operators into nested wildcard structure', function(): void {
            $template = [
                'departments' => [
                    '*' => [
                        'name' => '{{ departments.*.name }}',
                        'employees' => [
                            '*' => [
                                'name' => '{{ departments.*.employees.*.name }}',
                                'salary' => '{{ departments.*.employees.*.salary }}',
                            ],
                        ],
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $dataMapper->query('departments.*.employees.*')
                ->where('salary', '>', 50000)
                ->orderBy('salary', 'DESC');

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            // Check that operators are injected
            // Note: Currently operators are injected at the first wildcard level, not the nested level
            expect($modifiedTemplate['departments'])->toHaveKey('WHERE');
            expect($modifiedTemplate['departments'])->toHaveKey('ORDER BY');
            expect($modifiedTemplate['departments']['WHERE'])->toBe([
                '{{ departments.*.employees.*.salary }}' => ['>', 50000],
            ]);
            expect($modifiedTemplate['departments']['ORDER BY'])->toBe([
                '{{ departments.*.employees.*.salary }}' => 'DESC',
            ]);
        });

        it('injects operators into deeply nested wildcard (3 levels)', function(): void {
            $template = [
                'companies' => [
                    '*' => [
                        'name' => '{{ companies.*.name }}',
                        'departments' => [
                            '*' => [
                                'name' => '{{ companies.*.departments.*.name }}',
                                'teams' => [
                                    '*' => [
                                        'name' => '{{ companies.*.departments.*.teams.*.name }}',
                                        'size' => '{{ companies.*.departments.*.teams.*.size }}',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $dataMapper->query('companies.*.departments.*.teams.*')
                ->where('size', '>=', 5)
                ->orderBy('size', 'ASC');

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            // Check that operators are injected
            // Note: Currently operators are injected at the first wildcard level
            expect($modifiedTemplate['companies'])->toHaveKey('WHERE');
            expect($modifiedTemplate['companies'])->toHaveKey('ORDER BY');
            expect($modifiedTemplate['companies']['WHERE'])->toBe([
                '{{ companies.*.departments.*.teams.*.size }}' => ['>=', 5],
            ]);
            expect($modifiedTemplate['companies']['ORDER BY'])->toBe([
                '{{ companies.*.departments.*.teams.*.size }}' => 'ASC',
            ]);
        });
    });

    describe('Multiple Independent Wildcards', function(): void {
        it('injects operators into multiple independent wildcards', function(): void {
            $template = [
                'users' => [
                    '*' => [
                        'name' => '{{ users.*.name }}',
                        'age' => '{{ users.*.age }}',
                    ],
                ],
                'products' => [
                    '*' => [
                        'name' => '{{ products.*.name }}',
                        'price' => '{{ products.*.price }}',
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $result1 = $dataMapper->query('users.*')->where('age', '>', 18)->end();
            expect($result1)->toBeInstanceOf(FluentDataMapper::class);
            $result2 = $dataMapper->query('products.*')->where('price', '<', 100)->end();
            expect($result2)->toBeInstanceOf(FluentDataMapper::class);

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            // Both wildcards should have their own WHERE operators
            expect($modifiedTemplate['users'])->toHaveKey('WHERE');
            expect($modifiedTemplate['products'])->toHaveKey('WHERE');
            expect($modifiedTemplate['users']['WHERE'])->toBe([
                '{{ users.*.age }}' => ['>', 18],
            ]);
            expect($modifiedTemplate['products']['WHERE'])->toBe([
                '{{ products.*.price }}' => ['<', 100],
            ]);
        });
    });

    describe('Complex Real-World Scenarios', function(): void {
        it('handles user with roles scenario from example', function(): void {
            $template = [
                'user' => [
                    'forename' => 'Klaus',
                    'surname' => 'Meyer',
                    'roles' => [
                        '*' => [
                            'name' => '{{ roles.*.name }}',
                        ],
                    ],
                ],
            ];

            $dataMapper = DataMapper::template($template);
            $result = $dataMapper->query('roles.*')->where('name', '=', 'admin')->end();
            expect($result)->toBeInstanceOf(FluentDataMapper::class);

            $reflection = new ReflectionClass($dataMapper);
            $method = $reflection->getMethod('applyQueriesToTemplate');

            $modifiedTemplate = $method->invoke($dataMapper, $template);

            // Verify the exact structure matches expected
            $expectedTemplate = [
                'user' => [
                    'forename' => 'Klaus',
                    'surname' => 'Meyer',
                    'roles' => [
                        '*' => [
                            'name' => '{{ roles.*.name }}',
                        ],
                        'WHERE' => [
                            '{{ roles.*.name }}' => 'admin',  // '=' operator uses simple value format
                        ],
                    ],
                ],
            ];

            expect($modifiedTemplate)->toBe($expectedTemplate);
        });
    });
});


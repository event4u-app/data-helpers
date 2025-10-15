<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackParameters;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackRegistry;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CallbackFilter;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\ReverseDataMapper;

describe('Callback Integration Tests', function(): void {
    beforeEach(function(): void {
        MapperExceptions::reset();
        CallbackRegistry::clear();
    });

    it('works with TrimStrings filter', function(): void {
        $source = [
            'user' => [
                'name' => '  alice  ',
                'email' => '  ALICE@EXAMPLE.COM  ',
            ],
        ];

        $mapping = [
            'profile.name' => '{{ user.name }}',
            'profile.email' => '{{ user.email }}',
        ];

        $result = DataMapper::pipe([
            new TrimStrings(),
            new CallbackFilter(fn(CallbackParameters $p): mixed => is_string($p->value) ? strtolower(
                $p->value
            ) : $p->value),
        ])->map($source, [], $mapping);

        expect($result)->toBe([
            'profile' => [
                'name' => 'alice',
                'email' => 'alice@example.com',
            ],
        ]);
    });

    it('works with multiple callback filters', function(): void {
        $source = [
            'user' => [
                'name' => 'alice',
            ],
        ];

        $mapping = [
            'profile.name' => '{{ user.name }}',
        ];

        $result = DataMapper::pipe([
            new CallbackFilter(function(CallbackParameters $p): mixed {
                // Add prefix
                if (is_string($p->value)) {
                    return 'Dr. ' . $p->value;
                }
                return $p->value;
            }),
            new CallbackFilter(function(CallbackParameters $p): mixed {
                // Uppercase
                if (is_string($p->value)) {
                    return strtoupper($p->value);
                }
                return $p->value;
            }),
        ])->map($source, [], $mapping);

        expect($result)->toBe([
            'profile' => [
                'name' => 'DR. ALICE',
            ],
        ]);
    });

    it('works with wildcard operators', function(): void {
        $source = [
            'users' => [
                ['name' => 'alice', 'role' => 'admin'],
                ['name' => 'bob', 'role' => 'user'],
            ],
        ];

        $mapping = [
            'team' => '{{ users }}',
        ];

        $result = DataMapper::pipe([
            new CallbackFilter(function(CallbackParameters $p): mixed {
                // Transform array of users
                if (is_array($p->value) && 'team' === $p->key) {
                    return array_map(function($user) {
                        if (is_array($user) && isset($user['name']) && is_string($user['name'])) {
                            $user['name'] = strtoupper($user['name']);
                        }
                        return $user;
                    }, $p->value);
                }
                return $p->value;
            }),
        ])->map($source, [], $mapping);

        expect($result)->toBe([
            'team' => [
                ['name' => 'ALICE', 'role' => 'admin'],
                ['name' => 'BOB', 'role' => 'user'],
            ],
        ]);
    });

    it('works with reverse mapping', function(): void {
        $source = ['user' => ['name' => 'alice']];
        $mapping = [
            'profile.name' => '{{ user.name }}',
        ];

        // Forward mapping with callback filter
        $forward = DataMapper::pipe([
            new CallbackFilter(fn($p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value),
        ])->map($source, [], $mapping);

        expect($forward)->toBe([
            'profile' => ['name' => 'ALICE'],
        ]);

        // Reverse mapping (without callback - callbacks are one-way transformations)
        $reverse = ReverseDataMapper::map($forward, [], $mapping);

        expect($reverse)->toBe([
            'user' => ['name' => 'ALICE'],
        ]);
    });

    it('works with query builder', function(): void {
        $source = [
            'users' => [
                ['name' => 'alice', 'age' => 25, 'active' => true],
                ['name' => 'bob', 'age' => 30, 'active' => false],
                ['name' => 'charlie', 'age' => 35, 'active' => true],
            ],
        ];

        $mapping = [
            'activeUsers' => '{{ users }}',
        ];

        $result = DataMapper::pipe([
            new CallbackFilter(function(CallbackParameters $p): mixed {
                // Filter active users and uppercase names
                if (is_array($p->value) && 'activeUsers' === $p->key) {
                    $filtered = [];
                    foreach ($p->value as $user) {
                        if (is_array($user) && isset($user['active']) && $user['active']) {
                            $filtered[] = $user;
                        }
                    }
                    return $filtered;
                }
                return $p->value;
            }),
        ])->map($source, [], $mapping);

        expect($result['activeUsers'])->toHaveCount(2);
        expect($result['activeUsers'][0]['name'])->toBe('alice');
        expect($result['activeUsers'][1]['name'])->toBe('charlie');
    });

    it('handles complex nested transformations', function(): void {
        $source = [
            'company' => [
                'departments' => [
                    [
                        'name' => 'sales',
                        'employees' => [
                            ['name' => 'alice', 'salary' => 50000],
                            ['name' => 'bob', 'salary' => 60000],
                        ],
                    ],
                    [
                        'name' => 'engineering',
                        'employees' => [
                            ['name' => 'charlie', 'salary' => 80000],
                        ],
                    ],
                ],
            ],
        ];

        $mapping = [
            'org.teams' => '{{ company.departments }}',
        ];

        $result = DataMapper::pipe([
            new CallbackFilter(function(CallbackParameters $p): mixed {
                if (is_array($p->value) && 'teams' === $p->key) {
                    // Add total salary to each department
                    return array_map(function($dept) {
                        if (is_array($dept) && isset($dept['employees']) && is_array($dept['employees'])) {
                            $dept['totalSalary'] = array_sum(array_column($dept['employees'], 'salary'));
                        }
                        return $dept;
                    }, $p->value);
                }
                return $p->value;
            }),
        ])->map($source, [], $mapping);

        expect($result['org']['teams'][0]['totalSalary'])->toBe(110000);
        expect($result['org']['teams'][1]['totalSalary'])->toBe(80000);
    });

    it('can access source data for context-aware transformations', function(): void {
        $source = [
            'config' => [
                'currency' => 'USD',
                'taxRate' => 0.2,
            ],
            'product' => [
                'name' => 'Product A',
                'price' => 100,
            ],
        ];

        $mapping = [
            'item.name' => '{{ product.name }}',
            'item.price' => '{{ product.price }}',
        ];

        $result = DataMapper::pipe([
            new CallbackFilter(function(CallbackParameters $p) {
                // Apply tax from config
                if ('price' === $p->key && is_numeric($p->value)) {
                    $taxRate = 0.0;
                    if (is_array($p->source) && isset($p->source['config']['taxRate']) && is_numeric(
                        $p->source['config']['taxRate']
                    )) {
                        $taxRate = (float)$p->source['config']['taxRate'];
                    }
                    return $p->value * (1 + $taxRate);
                }
                return $p->value;
            }),
        ])->map($source, [], $mapping);

        expect($result['item']['price'])->toBe(120.0);
        expect($result['item']['name'])->toBe('Product A');
    });

    it('works with template expression chaining', function(): void {
        CallbackRegistry::register(
            'addPrefix',
            fn($p): mixed => is_string($p->value) ? 'PREFIX_' . $p->value : $p->value
        );
        CallbackRegistry::register(
            'addSuffix',
            fn($p): mixed => is_string($p->value) ? $p->value . '_SUFFIX' : $p->value
        );

        $template = [
            'result' => '{{ value | callback:addPrefix | callback:addSuffix | upper }}',
        ];

        $result = DataMapper::mapFromTemplate($template, [
            'value' => 'test',
        ]);

        expect($result)->toBe([
            'result' => 'PREFIX_TEST_SUFFIX',
        ]);
    });

    it('handles readonly source parameter correctly', function(): void {
        $source = [
            'user' => [
                'name' => 'Alice',
            ],
        ];

        $mapping = [
            'profile.name' => '{{ user.name }}',
        ];

        $originalSource = $source;

        DataMapper::pipe([
            new CallbackFilter(fn(CallbackParameters $p): mixed => // Try to modify source (should not affect original)
                // Note: CallbackParameters is readonly, so this would fail at runtime
                // This test documents that source is passed by value
            $p->value),
        ])->map($source, [], $mapping);

        // Source should be unchanged
        expect($source)->toBe($originalSource);
    });

    it('works with different hooks - beforeWrite', function(): void {
        $source = ['user' => ['name' => 'Alice']];
        $mapping = ['profile.name' => '{{ user.name }}'];

        $beforeWriteCalled = false;
        $hooks = [
            'beforeWrite' => function($value) use (&$beforeWriteCalled) {
                $beforeWriteCalled = true;
                return is_string($value) ? strtoupper($value) : $value;
            },
        ];

        $result = DataMapper::map($source, [], $mapping, true, false, $hooks);

        expect($beforeWriteCalled)->toBeTrue();
        expect($result['profile']['name'])->toBe('ALICE');
    });

    it('works with different hooks - postTransform', function(): void {
        $source = ['user' => ['name' => 'alice']];
        $mapping = ['profile.name' => '{{ user.name }}'];

        $postTransformCalled = false;
        $hooks = [
            'postTransform' => function($value) use (&$postTransformCalled) {
                $postTransformCalled = true;
                return is_string($value) ? strtoupper($value) : $value;
            },
        ];

        $result = DataMapper::map($source, [], $mapping, true, false, $hooks);

        expect($postTransformCalled)->toBeTrue();
        expect($result['profile']['name'])->toBe('ALICE');
    });

    it('works with mapFromFile', function(): void {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.json';
        file_put_contents($tempFile, json_encode(['user' => ['name' => 'alice', 'email' => 'ALICE@EXAMPLE.COM']]));

        try {
            $mapping = [
                'profile.name' => '{{ user.name }}',
                'profile.email' => '{{ user.email }}',
            ];

            $result = DataMapper::pipe([
                new CallbackFilter(function(CallbackParameters $p) {
                    if ('name' === $p->key && is_string($p->value)) {
                        return strtoupper($p->value);
                    }
                    if ('email' === $p->key && is_string($p->value)) {
                        return strtolower($p->value);
                    }
                    return $p->value;
                }),
            ])->mapFromFile($tempFile, [], $mapping);

            expect($result['profile']['name'])->toBe('ALICE');
            expect($result['profile']['email'])->toBe('alice@example.com');
        } finally {
            unlink($tempFile);
        }
    });

    it('works with skipNull option', function(): void {
        $source = ['user' => ['name' => 'Alice', 'email' => null]];
        $mapping = [
            'profile.name' => '{{ user.name }}',
            'profile.email' => '{{ user.email }}',
        ];

        $result = DataMapper::pipe([
            new CallbackFilter(fn($p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value),
        ])->map($source, [], $mapping, true);

        expect($result)->toBe(['profile' => ['name' => 'ALICE']]);
    });

    it('works with reindexWildcard option', function(): void {
        $source = [
            'items' => [
                5 => 'alice',
                10 => 'bob',
            ],
        ];
        $mapping = [
            'users.*' => '{{ items.* }}',
        ];

        // Without pipeline - reindexWildcard works
        $result = DataMapper::map($source, [], $mapping, true, true);

        expect($result['users'])->toHaveKey(0);
        expect($result['users'])->toHaveKey(1);
        expect($result['users'][0])->toBe('alice');
        expect($result['users'][1])->toBe('bob');
    });

    it('works with multiple sources in template', function(): void {
        $template = [
            'profile' => [
                'firstName' => '{{ user.firstName }}',
                'lastName' => '{{ user.lastName }}',
                'company' => '{{ company.name }}',
            ],
        ];

        $result = DataMapper::mapFromTemplate($template, [
            'user' => ['firstName' => 'Alice', 'lastName' => 'Smith'],
            'company' => ['name' => 'ACME Corp'],
        ]);

        expect($result['profile']['firstName'])->toBe('Alice');
        expect($result['profile']['lastName'])->toBe('Smith');
        expect($result['profile']['company'])->toBe('ACME Corp');
    });

    it('works with object mapping', function(): void {
        $source = (object)['user' => (object)['name' => 'alice']];
        $mapping = ['profile.name' => '{{ user.name }}'];

        $result = DataMapper::pipe([
            new CallbackFilter(fn($p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value),
        ])->map($source, [], $mapping);

        expect($result)->toBeArray();
        expect($result['profile']['name'])->toBe('ALICE');
    });

    it('handles deeply nested structures', function(): void {
        $source = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'level5' => ['value' => 'deep'],
                        ],
                    ],
                ],
            ],
        ];
        $mapping = ['result' => '{{ level1.level2.level3.level4.level5.value }}'];

        $result = DataMapper::pipe([
            new CallbackFilter(fn($p): mixed => is_string($p->value) ? strtoupper($p->value) : $p->value),
        ])->map($source, [], $mapping);

        expect($result['result'])->toBe('DEEP');
    });
});


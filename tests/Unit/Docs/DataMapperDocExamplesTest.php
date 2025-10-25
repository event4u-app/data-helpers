<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('DataMapper Documentation Examples', function (): void {
    it('validates template-based query example from documentation', function (): void {
        $source = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'orders' => [
                ['id' => 1, 'total' => 100, 'status' => 'shipped'],
                ['id' => 2, 'total' => 200, 'status' => 'pending'],
                ['id' => 3, 'total' => 150, 'status' => 'shipped'],
            ],
        ];

        // Template-based approach from documentation
        $result = DataMapper::from($source)
            ->template([
                'customer_name' => '{{ user.name }}',
                'customer_email' => '{{ user.email }}',
                'shipped_orders' => [
                    'WHERE' => [
                        '{{ orders.*.status }}' => 'shipped',
                    ],
                    'ORDER BY' => [
                        '{{ orders.*.total }}' => 'DESC',
                    ],
                    '*' => [
                        'id' => '{{ orders.*.id }}',
                        'total' => '{{ orders.*.total }}',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($result)->toBeArray();
        expect($result['customer_name'])->toBe('John Doe');
        expect($result['customer_email'])->toBe('john@example.com');
        expect($result['shipped_orders'])->toBeArray();
        expect($result['shipped_orders'])->toHaveCount(2);
        expect($result['shipped_orders'][0]['total'])->toBe(150); // DESC order
        expect($result['shipped_orders'][1]['total'])->toBe(100);
    })->group('docs', 'data-mapper');

    it('validates README DataMapper example', function (): void {
        $source = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'orders' => [
                ['id' => 1, 'total' => 100, 'status' => 'shipped'],
                ['id' => 2, 'total' => 200, 'status' => 'pending'],
                ['id' => 3, 'total' => 150, 'status' => 'shipped'],
            ],
        ];

        $result = DataMapper::from($source)
            ->template([
                'customer_name' => '{{ user.name }}',
                'customer_email' => '{{ user.email }}',
                'shipped_orders' => [
                    'WHERE' => [
                        '{{ orders.*.status }}' => 'shipped',
                    ],
                    'ORDER BY' => [
                        '{{ orders.*.total }}' => 'DESC',
                    ],
                    '*' => [
                        'id' => '{{ orders.*.id }}',
                        'total' => '{{ orders.*.total }}',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($result)->toMatchArray([
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
        ]);
        expect($result['shipped_orders'])->toHaveCount(2);
    })->group('docs', 'readme', 'data-mapper');

    it('validates no-code mapping example', function (): void {
        // Mock Mappings class for the example
        $template = [
            'customer_name' => '{{ user.name }}',
            'customer_email' => '{{ user.email }}',
        ];

        $source = [
            'user' => [
                'name' => 'Alice Smith',
                'email' => 'alice@example.com',
            ],
        ];

        $result = DataMapper::from($source)
            ->template($template)
            ->map()
            ->getTarget();

        expect($result)->toMatchArray([
            'customer_name' => 'Alice Smith',
            'customer_email' => 'alice@example.com',
        ]);
    })->group('docs', 'data-mapper', 'no-code');

    it('validates complex nested mapping example', function (): void {
        // Mock Company class
        $company = new class {
            public string $name = '';

            public array $departments = [];
        };

        $jsonData = [
            'company' => [
                'name' => 'Acme Corp',
                'departments' => [
                    ['name' => 'Engineering', 'budget' => 100000],
                    ['name' => 'Sales', 'budget' => 50000],
                ],
            ],
        ];

        $result = DataMapper::from($jsonData)
            ->target($company)
            ->template([
                'name' => '{{ company.name }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ company.departments.*.name }}',
                        'budget' => '{{ company.departments.*.budget }}',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($result)->toBeObject();
        expect($result->name)->toBe('Acme Corp');
        expect($result->departments)->toBeArray();
        expect($result->departments)->toHaveCount(2);
        expect($result->departments[0]['name'])->toBe('Engineering');
    })->group('docs', 'data-mapper', 'nested');

    it('validates quick start example', function (): void {
        $data = [
            'user' => [
                'orders' => [
                    ['id' => 1, 'total' => 50],
                    ['id' => 2, 'total' => 150],
                    ['id' => 3, 'total' => 200],
                ],
            ],
        ];

        $result = DataMapper::from($data)
            ->template([
                'items' => [
                    'WHERE' => [
                        '{{ user.orders.*.total }}' => ['>', 100],
                    ],
                    'ORDER BY' => [
                        '{{ user.orders.*.total }}' => 'DESC',
                    ],
                    'LIMIT' => 5,
                    '*' => [
                        'id' => '{{ user.orders.*.id }}',
                        'total' => '{{ user.orders.*.total }}',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($result)->toBeArray();
        expect($result['items'])->toBeArray();
        expect($result['items'])->toHaveCount(2);
        expect($result['items'][0]['total'])->toBe(200); // DESC order
        expect($result['items'][1]['total'])->toBe(150);
    })->group('docs', 'quick-start', 'data-mapper');

    it('validates WHERE with comparison operators', function (): void {
        $source = [
            'products' => [
                ['id' => 1, 'price' => 50],
                ['id' => 2, 'price' => 150],
                ['id' => 3, 'price' => 200],
            ],
        ];

        $result = DataMapper::from($source)
            ->template([
                'expensive_products' => [
                    'WHERE' => [
                        '{{ products.*.price }}' => ['>', 100],
                    ],
                    '*' => [
                        'id' => '{{ products.*.id }}',
                        'price' => '{{ products.*.price }}',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($result['expensive_products'])->toHaveCount(2);
        expect($result['expensive_products'][0]['price'])->toBe(150);
        expect($result['expensive_products'][1]['price'])->toBe(200);
    })->group('docs', 'data-mapper', 'where');

    it('validates LIMIT and OFFSET', function (): void {
        $source = [
            'items' => [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
                ['id' => 5],
            ],
        ];

        $result = DataMapper::from($source)
            ->template([
                'paginated' => [
                    'OFFSET' => 1,
                    'LIMIT' => 2,
                    '*' => [
                        'id' => '{{ items.*.id }}',
                    ],
                ],
            ])
            ->map()
            ->getTarget();

        expect($result['paginated'])->toBeArray();
        // OFFSET 1 means skip first item, LIMIT 2 means take 2 items
        // So we should get items with id 2 and 3
        $values = array_values($result['paginated']);
        expect($values)->toHaveCount(2);
        expect($values[0]['id'])->toBe(2);
        expect($values[1]['id'])->toBe(3);
    })->group('docs', 'data-mapper', 'pagination');
});


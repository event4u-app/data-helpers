<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\DataAccessor;

describe('DataAccessor Cache Safety', function(): void {
    it('handles different data structures with same path', function(): void {
        // First dataset
        $data1 = [
            'user' => [
                'name' => 'Alice',
                'age' => 30,
            ],
        ];

        // Second dataset with same structure but different values
        $data2 = [
            'user' => [
                'name' => 'Bob',
                'age' => 25,
            ],
        ];

        // Third dataset with different structure
        $data3 = [
            'user' => [
                'name' => 'Charlie',
                'email' => 'charlie@example.com', // Different field
            ],
        ];

        // Access same path on different datasets
        expect(DataAccessor::getValue($data1, 'user.name'))->toBe('Alice');
        expect(DataAccessor::getValue($data2, 'user.name'))->toBe('Bob');
        expect(DataAccessor::getValue($data3, 'user.name'))->toBe('Charlie');

        // Access different fields
        expect(DataAccessor::getValue($data1, 'user.age'))->toBe(30);
        expect(DataAccessor::getValue($data2, 'user.age'))->toBe(25);
        expect(DataAccessor::getValue($data3, 'user.age'))->toBeNull();
        expect(DataAccessor::getValue($data3, 'user.email'))->toBe('charlie@example.com');
    });

    it('handles instance-based and static access interleaved', function(): void {
        $data1 = ['value' => 'first'];
        $data2 = ['value' => 'second'];
        $data3 = ['value' => 'third'];

        // Mix instance-based and static access
        $accessor1 = new DataAccessor($data1);
        expect($accessor1->get('value'))->toBe('first');

        expect(DataAccessor::getValue($data2, 'value'))->toBe('second');

        $accessor3 = new DataAccessor($data3);
        expect($accessor3->get('value'))->toBe('third');

        // Verify first accessor still works correctly
        expect($accessor1->get('value'))->toBe('first');
    });

    it('handles different data types with same path', function(): void {
        // Array
        $array = ['user' => ['name' => 'Alice']];
        expect(DataAccessor::getValue($array, 'user.name'))->toBe('Alice');

        // Object
        $object = (object)[
            'user' => (object)[
                'name' => 'Bob',
            ],
        ];
        expect(DataAccessor::getValue($object, 'user.name'))->toBe('Bob');

        // JSON string
        $json = '{"user":{"name":"Charlie"}}';
        expect(DataAccessor::getValue($json, 'user.name'))->toBe('Charlie');

        // Back to array with different value
        $array2 = ['user' => ['name' => 'David']];
        expect(DataAccessor::getValue($array2, 'user.name'))->toBe('David');
    });

    it('handles wildcard paths on different array sizes', function(): void {
        // Small array
        $data1 = [
            'users' => [
                ['name' => 'Alice'],
                ['name' => 'Bob'],
            ],
        ];

        $result1 = DataAccessor::getValue($data1, 'users.*.name');
        expect($result1)->toBe([
            'users.0.name' => 'Alice',
            'users.1.name' => 'Bob',
        ]);

        // Larger array
        $data2 = [
            'users' => [
                ['name' => 'Charlie'],
                ['name' => 'David'],
                ['name' => 'Eve'],
                ['name' => 'Frank'],
            ],
        ];

        $result2 = DataAccessor::getValue($data2, 'users.*.name');
        expect($result2)->toBe([
            'users.0.name' => 'Charlie',
            'users.1.name' => 'David',
            'users.2.name' => 'Eve',
            'users.3.name' => 'Frank',
        ]);

        // Empty array
        $data3 = ['users' => []];
        $result3 = DataAccessor::getValue($data3, 'users.*.name');
        expect($result3)->toBe([]);

        // Single item
        $data4 = [
            'users' => [
                ['name' => 'George'],
            ],
        ];

        $result4 = DataAccessor::getValue($data4, 'users.*.name');
        expect($result4)->toBe([
            'users.0.name' => 'George',
        ]);
    });

    it('handles nested structures with varying depths', function(): void {
        // Shallow structure
        $data1 = ['name' => 'Alice'];
        expect(DataAccessor::getValue($data1, 'name'))->toBe('Alice');

        // Medium depth
        $data2 = [
            'user' => [
                'profile' => [
                    'name' => 'Bob',
                ],
            ],
        ];
        expect(DataAccessor::getValue($data2, 'user.profile.name'))->toBe('Bob');

        // Deep structure
        $data3 = [
            'company' => [
                'departments' => [
                    'engineering' => [
                        'teams' => [
                            'backend' => [
                                'lead' => 'Charlie',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        expect(DataAccessor::getValue($data3, 'company.departments.engineering.teams.backend.lead'))->toBe('Charlie');

        // Back to shallow
        $data4 = ['name' => 'David'];
        expect(DataAccessor::getValue($data4, 'name'))->toBe('David');
    });

    it('handles missing paths correctly across different datasets', function(): void {
        $data1 = ['user' => ['name' => 'Alice', 'email' => 'alice@example.com']];
        $data2 = ['user' => ['name' => 'Bob']]; // Missing email
        $data3 = ['user' => ['email' => 'charlie@example.com']]; // Missing name

        expect(DataAccessor::getValue($data1, 'user.name'))->toBe('Alice');
        expect(DataAccessor::getValue($data1, 'user.email'))->toBe('alice@example.com');

        expect(DataAccessor::getValue($data2, 'user.name'))->toBe('Bob');
        expect(DataAccessor::getValue($data2, 'user.email'))->toBeNull();

        expect(DataAccessor::getValue($data3, 'user.name'))->toBeNull();
        expect(DataAccessor::getValue($data3, 'user.email'))->toBe('charlie@example.com');
    });

    it('handles typed getters with different data structures', function(): void {
        $data1 = ['value' => 42];
        $data2 = ['value' => '100'];
        $data3 = ['value' => 3.14];
        $data4 = ['value' => 'not a number'];

        expect(DataAccessor::getIntValue($data1, 'value'))->toBe(42);
        expect(DataAccessor::getIntValue($data2, 'value'))->toBe(100);
        expect(DataAccessor::getIntValue($data3, 'value'))->toBe(3);
        expect(DataAccessor::getIntValue($data4, 'value', 0))->toBe(0);
    });

    it('handles multiple accessors with same paths simultaneously', function(): void {
        $data1 = ['user' => ['name' => 'Alice', 'age' => 30]];
        $data2 = ['user' => ['name' => 'Bob', 'age' => 25]];
        $data3 = ['user' => ['name' => 'Charlie', 'age' => 35]];

        $accessor1 = new DataAccessor($data1);
        $accessor2 = new DataAccessor($data2);
        $accessor3 = new DataAccessor($data3);

        // All accessors should return their own data
        expect($accessor1->get('user.name'))->toBe('Alice');
        expect($accessor2->get('user.name'))->toBe('Bob');
        expect($accessor3->get('user.name'))->toBe('Charlie');

        expect($accessor1->get('user.age'))->toBe(30);
        expect($accessor2->get('user.age'))->toBe(25);
        expect($accessor3->get('user.age'))->toBe(35);

        // Verify again to ensure no cross-contamination
        expect($accessor1->get('user.name'))->toBe('Alice');
        expect($accessor2->get('user.name'))->toBe('Bob');
        expect($accessor3->get('user.name'))->toBe('Charlie');
    });

    it('handles sequential processing of different structures', function(): void {
        // Simulate processing multiple API responses
        $responses = [
            ['status' => 'success', 'data' => ['id' => 1, 'name' => 'Alice']],
            ['status' => 'success', 'data' => ['id' => 2, 'name' => 'Bob']],
            ['status' => 'error', 'message' => 'Not found'],
            ['status' => 'success', 'data' => ['id' => 3, 'name' => 'Charlie']],
        ];

        $results = [];
        foreach ($responses as $response) {
            $status = DataAccessor::getValue($response, 'status');
            if ('success' === $status) {
                $results[] = DataAccessor::getValue($response, 'data.name');
            }
        }

        expect($results)->toBe(['Alice', 'Bob', 'Charlie']);
    });

    it('handles Collections with same path', function(): void {
        if (!class_exists('Illuminate\Support\Collection')) {
            expect(true)->toBeTrue(); // Skip if Laravel not available
            return;
        }

        $collection1 = collect(['user' => ['name' => 'Alice']]);
        $collection2 = collect(['user' => ['name' => 'Bob']]);
        $array = ['user' => ['name' => 'Charlie']];

        expect(DataAccessor::getValue($collection1, 'user.name'))->toBe('Alice');
        expect(DataAccessor::getValue($collection2, 'user.name'))->toBe('Bob');
        expect(DataAccessor::getValue($array, 'user.name'))->toBe('Charlie');
    });

    it('stress test: many datasets with same paths', function(): void {
        $datasets = [];
        for ($i = 0; 100 > $i; $i++) {
            $datasets[] = [
                'user' => [
                    'id' => $i,
                    'name' => 'User ' . $i,
                    'email' => sprintf('user%d@example.com', $i),
                ],
            ];
        }

        // Process all datasets with same paths
        foreach ($datasets as $i => $data) {
            expect(DataAccessor::getIntValue($data, 'user.id'))->toBe($i);
            expect(DataAccessor::getStringValue($data, 'user.name'))->toBe('User ' . $i);
            expect(DataAccessor::getStringValue($data, 'user.email'))->toBe(sprintf('user%d@example.com', $i));
        }
    });

    it('handles structure changes during processing', function(): void {
        // Start with one structure
        $data1 = [
            'type' => 'user',
            'user' => ['name' => 'Alice', 'age' => 30],
        ];

        expect(DataAccessor::getValue($data1, 'type'))->toBe('user');
        expect(DataAccessor::getValue($data1, 'user.name'))->toBe('Alice');

        // Switch to different structure
        $data2 = [
            'type' => 'product',
            'product' => ['name' => 'Widget', 'price' => 19.99],
        ];

        expect(DataAccessor::getValue($data2, 'type'))->toBe('product');
        expect(DataAccessor::getValue($data2, 'product.name'))->toBe('Widget');
        expect(DataAccessor::getFloatValue($data2, 'product.price'))->toBe(19.99);

        // Back to user structure with different data
        $data3 = [
            'type' => 'user',
            'user' => ['name' => 'Bob', 'age' => 25],
        ];

        expect(DataAccessor::getValue($data3, 'type'))->toBe('user');
        expect(DataAccessor::getValue($data3, 'user.name'))->toBe('Bob');
        expect(DataAccessor::getIntValue($data3, 'user.age'))->toBe(25);
    });
});

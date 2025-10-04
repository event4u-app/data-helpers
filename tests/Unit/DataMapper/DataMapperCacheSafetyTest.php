<?php

declare(strict_types=1);

namespace Tests\Unit\DataMapper;

use event4u\DataHelpers\DataMapper;

describe('DataMapper Cache Safety', function(): void {
    it('maps different source structures with same mapping', function(): void {
        $mapping = [
            'user.name' => 'name',
            'user.email' => 'email',
        ];

        // First source
        $source1 = [
            'user' => [
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ],
        ];

        $result1 = DataMapper::map($source1, [], $mapping);
        expect($result1)->toBe([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        // Second source with different values
        $source2 = [
            'user' => [
                'name' => 'Bob',
                'email' => 'bob@example.com',
            ],
        ];

        $result2 = DataMapper::map($source2, [], $mapping);
        expect($result2)->toBe([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);

        // Third source with partial data
        $source3 = [
            'user' => [
                'name' => 'Charlie',
            ],
        ];

        $result3 = DataMapper::map($source3, [], $mapping);
        expect($result3)->toBe([
            'name' => 'Charlie',
        ]);
    });

    it('handles wildcard mappings on different array sizes', function(): void {
        $mapping = [
            'users.*.name' => 'names.*',
        ];

        // Small array
        $source1 = [
            'users' => [
                ['name' => 'Alice'],
                ['name' => 'Bob'],
            ],
        ];

        $result1 = DataMapper::map($source1, [], $mapping);
        expect($result1['names'])->toBe(['Alice', 'Bob']);

        // Larger array
        $source2 = [
            'users' => [
                ['name' => 'Charlie'],
                ['name' => 'David'],
                ['name' => 'Eve'],
                ['name' => 'Frank'],
            ],
        ];

        $result2 = DataMapper::map($source2, [], $mapping);
        expect($result2['names'])->toBe(['Charlie', 'David', 'Eve', 'Frank']);

        // Empty array - no items to map
        $source3 = ['users' => []];
        $result3 = DataMapper::map($source3, [], $mapping);
        expect($result3)->toBe([]); // Empty result when no items

        // Single item
        $source4 = [
            'users' => [
                ['name' => 'George'],
            ],
        ];

        $result4 = DataMapper::map($source4, [], $mapping);
        expect($result4['names'])->toBe(['George']);
    });

    it('handles sequential batch processing', function(): void {
        $mapping = [
            'user.id' => 'id',
            'user.name' => 'name',
            'user.email' => 'email',
        ];

        $sources = [];
        for ($i = 0; 50 > $i; $i++) {
            $sources[] = [
                'user' => [
                    'id' => $i,
                    'name' => 'User ' . $i,
                    'email' => sprintf('user%d@example.com', $i),
                ],
            ];
        }

        // Process all sources
        $results = [];
        foreach ($sources as $source) {
            $results[] = DataMapper::map($source, [], $mapping);
        }

        // Verify each result
        foreach ($results as $i => $result) {
            expect($result['id'])->toBe($i);
            expect($result['name'])->toBe('User ' . $i);
            expect($result['email'])->toBe(sprintf('user%d@example.com', $i));
        }
    });

    it('handles different source types with same mapping', function(): void {
        $mapping = [
            'user.name' => 'name',
        ];

        // Array
        $array = ['user' => ['name' => 'Alice']];
        $result1 = DataMapper::map($array, [], $mapping);
        expect($result1['name'])->toBe('Alice');

        // Object
        $object = (object)[
            'user' => (object)[
                'name' => 'Bob',
            ],
        ];
        $result2 = DataMapper::map($object, [], $mapping);
        expect($result2['name'])->toBe('Bob');

        // JSON string
        $json = '{"user":{"name":"Charlie"}}';
        $result3 = DataMapper::map($json, [], $mapping);
        expect($result3['name'])->toBe('Charlie');
    });

    it('handles wildcard mappings with different nesting levels', function(): void {
        $mapping = [
            'items.*.name' => 'names.*',
            'items.*.value' => 'values.*',
        ];

        // Small structure
        $source1 = [
            'items' => [
                ['name' => 'Item 1', 'value' => 10],
                ['name' => 'Item 2', 'value' => 20],
            ],
        ];

        $result1 = DataMapper::map($source1, [], $mapping);
        expect($result1['names'])->toBe(['Item 1', 'Item 2']);
        expect($result1['values'])->toBe([10, 20]);

        // Larger structure
        $source2 = [
            'items' => [
                ['name' => 'Item A', 'value' => 100],
                ['name' => 'Item B', 'value' => 200],
                ['name' => 'Item C', 'value' => 300],
                ['name' => 'Item D', 'value' => 400],
            ],
        ];

        $result2 = DataMapper::map($source2, [], $mapping);
        expect($result2['names'])->toBe(['Item A', 'Item B', 'Item C', 'Item D']);
        expect($result2['values'])->toBe([100, 200, 300, 400]);
    });

    it('handles mapping on different datasets', function(): void {
        $mapping = [
            'user.name' => 'name',
            'user.email' => 'email',
        ];

        // First dataset
        $source1 = [
            'user' => [
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ],
        ];

        $result1 = DataMapper::map($source1, [], $mapping);
        expect($result1['name'])->toBe('Alice');
        expect($result1['email'])->toBe('alice@example.com');

        // Second dataset
        $source2 = [
            'user' => [
                'name' => 'Bob',
                'email' => 'bob@example.com',
            ],
        ];

        $result2 = DataMapper::map($source2, [], $mapping);
        expect($result2['name'])->toBe('Bob');
        expect($result2['email'])->toBe('bob@example.com');

        // Third dataset with different structure
        $source3 = [
            'user' => [
                'name' => 'Charlie',
                'phone' => '+49123456789',
            ],
        ];

        $result3 = DataMapper::map($source3, [], $mapping);
        expect($result3['name'])->toBe('Charlie');
    });

    it('handles structure changes during processing', function(): void {
        // First mapping for user structure
        $userMapping = [
            'type' => 'type',
            'user.name' => 'name',
            'user.age' => 'age',
        ];

        $userSource = [
            'type' => 'user',
            'user' => ['name' => 'Alice', 'age' => 30],
        ];

        $userResult = DataMapper::map($userSource, [], $userMapping);
        expect($userResult['type'])->toBe('user');
        expect($userResult['name'])->toBe('Alice');
        expect($userResult['age'])->toBe(30);

        // Second mapping for product structure
        $productMapping = [
            'type' => 'type',
            'product.name' => 'name',
            'product.price' => 'price',
        ];

        $productSource = [
            'type' => 'product',
            'product' => ['name' => 'Widget', 'price' => 19.99],
        ];

        $productResult = DataMapper::map($productSource, [], $productMapping);
        expect($productResult['type'])->toBe('product');
        expect($productResult['name'])->toBe('Widget');
        expect($productResult['price'])->toBe(19.99);

        // Back to user with different data
        $userSource2 = [
            'type' => 'user',
            'user' => ['name' => 'Bob', 'age' => 25],
        ];

        $userResult2 = DataMapper::map($userSource2, [], $userMapping);
        expect($userResult2['type'])->toBe('user');
        expect($userResult2['name'])->toBe('Bob');
        expect($userResult2['age'])->toBe(25);
    });

    it('handles concurrent mappings with different structures', function(): void {
        $mapping1 = ['user.name' => 'name'];
        $mapping2 = ['product.title' => 'title'];
        $mapping3 = ['address.city' => 'city'];

        $source1 = ['user' => ['name' => 'Alice']];
        $source2 = ['product' => ['title' => 'Widget']];
        $source3 = ['address' => ['city' => 'Berlin']];

        // Interleaved mappings
        $result1 = DataMapper::map($source1, [], $mapping1);
        $result2 = DataMapper::map($source2, [], $mapping2);
        $result3 = DataMapper::map($source3, [], $mapping3);

        expect($result1['name'])->toBe('Alice');
        expect($result2['title'])->toBe('Widget');
        expect($result3['city'])->toBe('Berlin');

        // Verify again with different data
        $source1b = ['user' => ['name' => 'Bob']];
        $source2b = ['product' => ['title' => 'Gadget']];
        $source3b = ['address' => ['city' => 'Hamburg']];

        $result1b = DataMapper::map($source1b, [], $mapping1);
        $result2b = DataMapper::map($source2b, [], $mapping2);
        $result3b = DataMapper::map($source3b, [], $mapping3);

        expect($result1b['name'])->toBe('Bob');
        expect($result2b['title'])->toBe('Gadget');
        expect($result3b['city'])->toBe('Hamburg');
    });

    it('handles API response processing with varying structures', function(): void {
        $mapping = [
            'status' => 'status',
            'data.user.id' => 'userId',
            'data.user.name' => 'userName',
            'error.message' => 'error',
        ];

        // Success response
        $response1 = [
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => 1,
                    'name' => 'Alice',
                ],
            ],
        ];

        $result1 = DataMapper::map($response1, [], $mapping);
        expect($result1['status'])->toBe('success');
        expect($result1['userId'])->toBe(1);
        expect($result1['userName'])->toBe('Alice');

        // Error response
        $response2 = [
            'status' => 'error',
            'error' => [
                'message' => 'User not found',
            ],
        ];

        $result2 = DataMapper::map($response2, [], $mapping);
        expect($result2['status'])->toBe('error');
        expect($result2['error'])->toBe('User not found');

        // Another success response
        $response3 = [
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => 2,
                    'name' => 'Bob',
                ],
            ],
        ];

        $result3 = DataMapper::map($response3, [], $mapping);
        expect($result3['status'])->toBe('success');
        expect($result3['userId'])->toBe(2);
        expect($result3['userName'])->toBe('Bob');
    });
});

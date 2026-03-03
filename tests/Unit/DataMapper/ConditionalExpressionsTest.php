<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('DataMapper → Conditional Expressions', function(): void {
    test('it transforms status to 0 or 1 based on condition', function(): void {
        $source = [
            'user' => [
                'status' => 'active',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'active' => '{{ user.status == "active" ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['active' => 1]);
    });

    test('it returns 0 when status is not active', function(): void {
        $source = [
            'user' => [
                'status' => 'inactive',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'active' => '{{ user.status == "active" ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['active' => 0]);
    });

    test('it supports boolean values', function(): void {
        $source = [
            'user' => [
                'status' => 'active',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'is_active' => '{{ user.status == "active" ? true : false }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['is_active' => true]);
    });

    test('it supports string values', function(): void {
        $source = [
            'user' => [
                'age' => 25,
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'category' => '{{ user.age > 18 ? "adult" : "minor" }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['category' => 'adult']);
    });

    test('it supports greater than operator', function(): void {
        $source = [
            'product' => [
                'price' => 150,
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'expensive' => '{{ product.price > 100 ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['expensive' => 1]);
    });

    test('it supports less than operator', function(): void {
        $source = [
            'product' => [
                'price' => 50,
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'cheap' => '{{ product.price < 100 ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['cheap' => 1]);
    });

    test('it supports greater than or equal operator', function(): void {
        $source = [
            'user' => [
                'age' => 18,
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'adult' => '{{ user.age >= 18 ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['adult' => 1]);
    });

    test('it supports less than or equal operator', function(): void {
        $source = [
            'user' => [
                'age' => 17,
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'minor' => '{{ user.age <= 17 ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['minor' => 1]);
    });

    test('it supports not equal operator', function(): void {
        $source = [
            'user' => [
                'status' => 'inactive',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'not_active' => '{{ user.status != "active" ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['not_active' => 1]);
    });

    test('it works with wildcards', function(): void {
        $source = [
            'users' => [
                ['name' => 'Alice', 'status' => 'active'],
                ['name' => 'Bob', 'status' => 'inactive'],
                ['name' => 'Charlie', 'status' => 'active'],
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'users.*' => [
                    'name' => '{{ users.*.name }}',
                    'active' => '{{ users.*.status == "active" ? 1 : 0 }}',
                ],
            ])
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'users' => [
                ['name' => 'Alice', 'active' => 1],
                ['name' => 'Bob', 'active' => 0],
                ['name' => 'Charlie', 'active' => 1],
            ],
        ]);
    });

    test('it handles null values in conditions', function(): void {
        $source = [
            'user' => [
                'email' => null,
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'has_email' => '{{ user.email != null ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['has_email' => 0]);
    });

    test('it handles numeric comparisons with floats', function(): void {
        $source = [
            'product' => [
                'price' => 99.99,
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'expensive' => '{{ product.price >= 100.00 ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['expensive' => 0]);
    });

    test('it handles nested property access in conditions', function(): void {
        $source = [
            'user' => [
                'profile' => [
                    'age' => 25,
                ],
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'adult' => '{{ user.profile.age >= 18 ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['adult' => 1]);
    });

    test('it handles single quotes in string literals', function(): void {
        $source = [
            'user' => [
                'status' => 'active',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'status_text' => "{{ user.status == 'active' ? 'Yes' : 'No' }}",
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['status_text' => 'Yes']);
    });

    test('it handles double quotes in string literals', function(): void {
        $source = [
            'user' => [
                'status' => 'active',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'status_text' => '{{ user.status == "active" ? "Yes" : "No" }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['status_text' => 'Yes']);
    });

    test('it handles numeric literals in conditions', function(): void {
        $source = [
            'product' => [
                'quantity' => 5,
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'low_stock' => '{{ product.quantity < 10 ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['low_stock' => 1]);
    });

    test('it handles zero values correctly', function(): void {
        $source = [
            'product' => [
                'quantity' => 0,
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'out_of_stock' => '{{ product.quantity == 0 ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['out_of_stock' => 1]);
    });

    test('it handles empty string values', function(): void {
        $source = [
            'user' => [
                'name' => '',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'has_name' => '{{ user.name != "" ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['has_name' => 0]);
    });

    test('it handles multiple conditions in same template', function(): void {
        $source = [
            'user' => [
                'age' => 25,
                'status' => 'active',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'adult' => '{{ user.age >= 18 ? 1 : 0 }}',
                'active' => '{{ user.status == "active" ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'adult' => 1,
            'active' => 1,
        ]);
    });

    test('it supports pipe filters in condition with parentheses', function(): void {
        $source = [
            'equipment' => [
                ['name' => 'Mixer', 'status' => 'Active'],
                ['name' => 'Oven', 'status' => 'Inactive'],
                ['name' => 'Grill', 'status' => 'ACTIVE'],
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'equipment.*' => [
                    'name' => '{{ equipment.*.name }}',
                    'item_inactive' => '{{ (equipment.*.status | lower) == "active" ? 0 : 1 }}',
                ],
            ])
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'equipment' => [
                ['name' => 'Mixer', 'item_inactive' => 0],
                ['name' => 'Oven', 'item_inactive' => 1],
                ['name' => 'Grill', 'item_inactive' => 0],
            ],
        ]);
    });

    test('it supports upper filter in condition with parentheses', function(): void {
        $source = [
            'user' => [
                'role' => 'admin',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'is_admin' => '{{ (user.role | upper) == "ADMIN" ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['is_admin' => 1]);
    });

    test('it supports filtered condition with not equal operator', function(): void {
        $source = [
            'user' => [
                'status' => 'INACTIVE',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'not_active' => '{{ (user.status | lower) != "active" ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['not_active' => 1]);
    });

    test('it supports filtered condition with string result values', function(): void {
        $source = [
            'user' => [
                'status' => 'Active',
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'label' => '{{ (user.status | lower) == "active" ? "enabled" : "disabled" }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['label' => 'enabled']);
    });

    test('it handles null value in filtered ternary condition', function(): void {
        $source = ['user' => ['status' => null]];

        $result = DataMapper::source($source)
            ->template([
                'active' => '{{ (user.status | lower) == "active" ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['active' => 0]);
    });

    test('it handles null value in filtered ternary with not equal', function(): void {
        $source = ['user' => ['status' => null]];

        $result = DataMapper::source($source)
            ->template([
                'not_active' => '{{ (user.status | lower) != "active" ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['not_active' => 1]);
    });

    test('it handles mixed null and non-null values in wildcard filtered ternary', function(): void {
        $source = [
            'equipment' => [
                ['name' => 'Mixer', 'status' => 'Active'],
                ['name' => 'Oven', 'status' => null],
                ['name' => 'Grill', 'status' => 'Defekt'],
                ['name' => 'Blender', 'status' => 'Ok'],
            ],
        ];

        $result = DataMapper::source($source)
            ->template([
                'equipment.*' => [
                    'name' => '{{ equipment.*.name }}',
                    'item_inactive' => '{{ (equipment.*.status | lower) == "active" ? 0 : 1 }}',
                ],
            ])
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'equipment' => [
                ['name' => 'Mixer', 'item_inactive' => 0],
                ['name' => 'Oven', 'item_inactive' => 1],
                ['name' => 'Grill', 'item_inactive' => 1],
                ['name' => 'Blender', 'item_inactive' => 1],
            ],
        ]);
    });

    test('it handles null compared to null in filtered ternary', function(): void {
        $source = ['user' => ['status' => null]];

        $result = DataMapper::source($source)
            ->template([
                'is_null' => '{{ (user.status | lower) == null ? 1 : 0 }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe(['is_null' => 1]);
    });
});

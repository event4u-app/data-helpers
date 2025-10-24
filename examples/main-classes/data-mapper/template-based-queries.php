<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\DataMapper;

echo "Template-Based Queries Example\n";
echo str_repeat('=', 80) . "\n\n";

// Sample data
$source = [
    'user' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
    'orders' => [
        ['id' => 1, 'total' => 100, 'status' => 'shipped', 'date' => '2024-01-15'],
        ['id' => 2, 'total' => 200, 'status' => 'pending', 'date' => '2024-01-16'],
        ['id' => 3, 'total' => 150, 'status' => 'shipped', 'date' => '2024-01-17'],
        ['id' => 4, 'total' => 300, 'status' => 'shipped', 'date' => '2024-01-18'],
        ['id' => 5, 'total' => 50, 'status' => 'cancelled', 'date' => '2024-01-19'],
    ],
];

// ============================================================================
// Example 1: Basic WHERE and ORDER BY
// ============================================================================
echo "1. Basic WHERE and ORDER BY\n";
echo str_repeat('-', 80) . "\n";

$template1 = [
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
            'date' => '{{ orders.*.date }}',
        ],
    ],
];

$result1 = DataMapper::from($source)
    ->template($template1)
    ->map()
    ->getTarget();

echo "Shipped orders (sorted by total DESC):\n";
echo json_encode($result1, JSON_PRETTY_PRINT) . "\n\n";

// ============================================================================
// Example 2: WHERE with comparison operators
// ============================================================================
echo "2. WHERE with Comparison Operators\n";
echo str_repeat('-', 80) . "\n";

$template2 = [
    'customer_name' => '{{ user.name }}',
    'high_value_orders' => [
        'WHERE' => [
            '{{ orders.*.total }}' => ['>', 100],
        ],
        'ORDER BY' => [
            '{{ orders.*.total }}' => 'DESC',
        ],
        '*' => [
            'id' => '{{ orders.*.id }}',
            'total' => '{{ orders.*.total }}',
            'status' => '{{ orders.*.status }}',
        ],
    ],
];

$result2 = DataMapper::from($source)
    ->template($template2)
    ->map()
    ->getTarget();

echo "Orders with total > 100:\n";
echo json_encode($result2, JSON_PRETTY_PRINT) . "\n\n";

// ============================================================================
// Example 3: LIMIT and OFFSET (Pagination)
// ============================================================================
echo "3. LIMIT and OFFSET (Pagination)\n";
echo str_repeat('-', 80) . "\n";

$template3 = [
    'customer_name' => '{{ user.name }}',
    'paginated_orders' => [
        'WHERE' => [
            '{{ orders.*.status }}' => 'shipped',
        ],
        'ORDER BY' => [
            '{{ orders.*.total }}' => 'DESC',
        ],
        'OFFSET' => 1,  // Skip first item
        'LIMIT' => 2,   // Get 2 items
        '*' => [
            'id' => '{{ orders.*.id }}',
            'total' => '{{ orders.*.total }}',
        ],
    ],
];

$result3 = DataMapper::from($source)
    ->template($template3)
    ->map()
    ->getTarget();

echo "Shipped orders (page 2, 2 items per page):\n";
echo json_encode($result3, JSON_PRETTY_PRINT) . "\n\n";

// ============================================================================
// Example 4: Complex WHERE with AND/OR
// ============================================================================
echo "4. Complex WHERE with AND/OR\n";
echo str_repeat('-', 80) . "\n";

$template4 = [
    'customer_name' => '{{ user.name }}',
    'filtered_orders' => [
        'WHERE' => [
            'AND' => [
                '{{ orders.*.total }}' => ['>=', 100],
                'OR' => [
                    '{{ orders.*.status }}' => 'pending',
                ],
            ],
        ],
        'ORDER BY' => [
            '{{ orders.*.total }}' => 'DESC',
        ],
        '*' => [
            'id' => '{{ orders.*.id }}',
            'total' => '{{ orders.*.total }}',
            'status' => '{{ orders.*.status }}',
        ],
    ],
];

$result4 = DataMapper::from($source)
    ->template($template4)
    ->map()
    ->getTarget();

echo "Orders with total >= 100 AND (status = shipped OR pending):\n";
echo json_encode($result4, JSON_PRETTY_PRINT) . "\n\n";

// ============================================================================
// Example 5: Database-Stored Template (Simulated)
// ============================================================================
echo "5. Database-Stored Template (Simulated)\n";
echo str_repeat('-', 80) . "\n";

// Simulate loading template from database
// In real application: $template = Mappings::find(3)->template;
$storedTemplate = [
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
];

// Use template from database
$result5 = DataMapper::from($source)
    ->template($storedTemplate)
    ->map()
    ->getTarget();

echo "Result using database-stored template:\n";
echo json_encode($result5, JSON_PRETTY_PRINT) . "\n\n";

// ============================================================================
// Example 6: Multiple Wildcard Arrays with Different Queries
// ============================================================================
echo "6. Multiple Wildcard Arrays with Different Queries\n";
echo str_repeat('-', 80) . "\n";

$template6 = [
    'customer_name' => '{{ user.name }}',
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
    'pending_orders' => [
        'WHERE' => [
            '{{ orders.*.status }}' => 'pending',
        ],
        '*' => [
            'id' => '{{ orders.*.id }}',
            'total' => '{{ orders.*.total }}',
        ],
    ],
];

$result6 = DataMapper::from($source)
    ->template($template6)
    ->map()
    ->getTarget();

echo "Multiple filtered arrays in one mapping:\n";
echo json_encode($result6, JSON_PRETTY_PRINT) . "\n\n";

// ============================================================================
// Summary
// ============================================================================
echo str_repeat('=', 80) . "\n";
echo "Summary:\n";
echo "- Template-based queries can be stored in database\n";
echo "- Perfect for drag-and-drop mapping editors\n";
echo "- Enables no-code data transformation\n";
echo "- Use cases: Import wizards, API integrations, ETL pipelines\n";
echo str_repeat('=', 80) . "\n";

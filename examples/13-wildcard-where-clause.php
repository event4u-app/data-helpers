<?php

declare(strict_types=1);

/**
 * Example 13: Wildcard WHERE and ORDER BY Clauses
 *
 * This example demonstrates how to filter and sort wildcard arrays using WHERE
 * and ORDER BY clauses similar to Laravel's Query Builder. You can use AND/OR
 * logic to filter items and sort them by multiple fields before mapping.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

echo "=== Wildcard WHERE and ORDER BY Examples ===\n\n";

// Example data: Construction site with positions
$constructionSite = [
    'ConstructionSite' => [
        'nr_lv' => 'CS-123',
        'name' => 'Main Building Project',
        'Positions' => [
            'Position' => [
                ['project_number' => 'CS-123', 'pos_number' => '1.3', 'parent_id' => null, 'type' => 'gravel', 'quantity' => 100, 'priority' => 2],
                ['project_number' => 'CS-123', 'pos_number' => '1.1', 'parent_id' => null, 'type' => 'sand', 'quantity' => 50, 'priority' => 1],
                ['project_number' => 'CS-999', 'pos_number' => '2.1', 'parent_id' => null, 'type' => 'gravel', 'quantity' => 75, 'priority' => 3],
                ['project_number' => 'CS-123', 'pos_number' => '1.2', 'parent_id' => null, 'type' => 'gravel', 'quantity' => 120, 'priority' => 3],
                ['project_number' => 'CS-123', 'pos_number' => '1.4', 'parent_id' => null, 'type' => 'sand', 'quantity' => 80, 'priority' => 1],
            ],
        ],
    ],
];

// ============================================================================
// Example 1: Simple WHERE Clause
// ============================================================================
echo "1. Simple WHERE Clause - Filter by project number\n";
echo str_repeat('-', 60) . "\n";

$template1 = [
    'project' => [
        'number' => '{{ ConstructionSite.nr_lv }}',
        'name' => '{{ ConstructionSite.name }}',
    ],
    'positions' => [
        'WHERE' => [
            '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'type' => '{{ ConstructionSite.Positions.Position.*.type }}',
            'quantity' => '{{ ConstructionSite.Positions.Position.*.quantity }}',
        ],
    ],
];

$result1 = DataMapper::mapFromTemplate($template1, $constructionSite, true, true);

echo sprintf('Project: %s - %s%s', $result1['project']['number'], $result1['project']['name'], PHP_EOL);
echo "Filtered Positions (only CS-123):\n";
foreach ($result1['positions'] as $pos) {
    echo "  - {$pos['number']}: {$pos['type']} ({$pos['quantity']} units)\n";
}
echo "\n";

// ============================================================================
// Example 2: AND Condition (Implicit)
// ============================================================================
echo "2. AND Condition (Implicit) - Filter by project AND type\n";
echo str_repeat('-', 60) . "\n";

$template2 = [
    'project' => [
        'number' => '{{ ConstructionSite.nr_lv }}',
    ],
    'gravel_positions' => [
        'WHERE' => [
            '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
            '{{ ConstructionSite.Positions.Position.*.type }}' => 'gravel',
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'quantity' => '{{ ConstructionSite.Positions.Position.*.quantity }}',
        ],
    ],
];

$result2 = DataMapper::mapFromTemplate($template2, $constructionSite, true, true);

echo "Gravel Positions for CS-123:\n";
foreach ($result2['gravel_positions'] as $pos) {
    echo "  - {$pos['number']}: {$pos['quantity']} units\n";
}
echo "\n";

// ============================================================================
// Example 3: Explicit AND Condition
// ============================================================================
echo "3. Explicit AND Condition - Same as above but explicit\n";
echo str_repeat('-', 60) . "\n";

$template3 = [
    'project' => [
        'number' => '{{ ConstructionSite.nr_lv }}',
    ],
    'gravel_positions' => [
        'WHERE' => [
            'AND' => [
                '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
                '{{ ConstructionSite.Positions.Position.*.type }}' => 'gravel',
            ],
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'quantity' => '{{ ConstructionSite.Positions.Position.*.quantity }}',
        ],
    ],
];

$result3 = DataMapper::mapFromTemplate($template3, $constructionSite, true, true);

echo "Gravel Positions for CS-123 (explicit AND):\n";
foreach ($result3['gravel_positions'] as $pos) {
    echo "  - {$pos['number']}: {$pos['quantity']} units\n";
}
echo "\n";

// ============================================================================
// Example 4: OR Condition
// ============================================================================
echo "4. OR Condition - Filter by type gravel OR sand\n";
echo str_repeat('-', 60) . "\n";

$template4 = [
    'project' => [
        'number' => '{{ ConstructionSite.nr_lv }}',
    ],
    'material_positions' => [
        'WHERE' => [
            'AND' => [
                '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
                'OR' => [
                    '{{ ConstructionSite.Positions.Position.*.type }}' => 'sand',
                ],
            ],
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'type' => '{{ ConstructionSite.Positions.Position.*.type }}',
            'quantity' => '{{ ConstructionSite.Positions.Position.*.quantity }}',
        ],
    ],
];

$result4 = DataMapper::mapFromTemplate($template4, $constructionSite, true, true);

echo "Material Positions (gravel OR sand) for CS-123:\n";
foreach ($result4['material_positions'] as $pos) {
    echo "  - {$pos['number']}: {$pos['type']} ({$pos['quantity']} units)\n";
}
echo "\n";

// ============================================================================
// Example 5: Nested AND/OR Conditions
// ============================================================================
echo "5. Nested AND/OR Conditions - Complex filtering\n";
echo str_repeat('-', 60) . "\n";

$template5 = [
    'high_quantity_positions' => [
        'WHERE' => [
            'OR' => [
                [
                    'AND' => [
                        '{{ ConstructionSite.Positions.Position.*.type }}' => 'gravel',
                        '{{ ConstructionSite.Positions.Position.*.quantity }}' => 100,
                    ],
                ],
                [
                    'AND' => [
                        '{{ ConstructionSite.Positions.Position.*.type }}' => 'sand',
                        '{{ ConstructionSite.Positions.Position.*.quantity }}' => 80,
                    ],
                ],
            ],
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'type' => '{{ ConstructionSite.Positions.Position.*.type }}',
            'quantity' => '{{ ConstructionSite.Positions.Position.*.quantity }}',
        ],
    ],
];

$result5 = DataMapper::mapFromTemplate($template5, $constructionSite, true, true);

echo "High Quantity Positions (gravel=100 OR sand=80):\n";
foreach ($result5['high_quantity_positions'] as $pos) {
    echo "  - {$pos['number']}: {$pos['type']} ({$pos['quantity']} units)\n";
}
echo "\n";

// ============================================================================
// Example 6: Case-Insensitive Keywords
// ============================================================================
echo "6. Case-Insensitive Keywords - 'and' and 'or' work too\n";
echo str_repeat('-', 60) . "\n";

$template6 = [
    'project' => [
        'number' => '{{ ConstructionSite.nr_lv }}',
    ],
    'positions' => [
        'WHERE' => [
            'and' => [  // lowercase 'and'
                '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
                'or' => [  // lowercase 'or'
                    '{{ ConstructionSite.Positions.Position.*.type }}' => 'gravel',
                    '{{ ConstructionSite.Positions.Position.*.pos_number }}' => '1.1.1',
                ],
            ],
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'type' => '{{ ConstructionSite.Positions.Position.*.type }}',
        ],
    ],
];

$result6 = DataMapper::mapFromTemplate($template6, $constructionSite, true, true);

echo "Positions (case-insensitive keywords):\n";
foreach ($result6['positions'] as $pos) {
    echo sprintf('  - %s: %s%s', $pos['number'], $pos['type'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 7: Empty Result
// ============================================================================
echo "7. Empty Result - No matches\n";
echo str_repeat('-', 60) . "\n";

$template7 = [
    'positions' => [
        'WHERE' => [
            '{{ ConstructionSite.Positions.Position.*.project_number }}' => 'CS-999',
            '{{ ConstructionSite.Positions.Position.*.type }}' => 'concrete',  // No concrete positions
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
        ],
    ],
];

$result7 = DataMapper::mapFromTemplate($template7, $constructionSite, true, true);

echo "Concrete Positions for CS-999: " . (empty($result7['positions']) ? 'None found' : count(
    $result7['positions']
)) . "\n";
echo "\n";

// ============================================================================
// Example 8: ORDER BY - Single Field Ascending
// ============================================================================
echo "8. ORDER BY - Sort by position number (ASC)\n";
echo str_repeat('-', 60) . "\n";

$template8 = [
    'project' => [
        'number' => '{{ ConstructionSite.nr_lv }}',
    ],
    'sorted_positions' => [
        'WHERE' => [
            '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
        ],
        'ORDER BY' => [
            '{{ ConstructionSite.Positions.Position.*.pos_number }}' => 'ASC',
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'type' => '{{ ConstructionSite.Positions.Position.*.type }}',
        ],
    ],
];

$result8 = DataMapper::mapFromTemplate($template8, $constructionSite, true, true);

echo "Sorted Positions (by number ASC):\n";
foreach ($result8['sorted_positions'] as $pos) {
    echo sprintf('  - %s: %s%s', $pos['number'], $pos['type'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 9: ORDER BY - Multiple Fields
// ============================================================================
echo "9. ORDER BY - Sort by priority, then by quantity (DESC)\n";
echo str_repeat('-', 60) . "\n";

$template9 = [
    'project' => [
        'number' => '{{ ConstructionSite.nr_lv }}',
    ],
    'sorted_positions' => [
        'WHERE' => [
            '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
        ],
        'ORDER BY' => [
            '{{ ConstructionSite.Positions.Position.*.priority }}' => 'ASC',
            '{{ ConstructionSite.Positions.Position.*.quantity }}' => 'DESC',
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'priority' => '{{ ConstructionSite.Positions.Position.*.priority }}',
            'quantity' => '{{ ConstructionSite.Positions.Position.*.quantity }}',
        ],
    ],
];

$result9 = DataMapper::mapFromTemplate($template9, $constructionSite, true, true);

echo "Sorted Positions (by priority ASC, quantity DESC):\n";
foreach ($result9['sorted_positions'] as $pos) {
    echo sprintf('  - %s: Priority %s, Quantity %s%s', $pos['number'], $pos['priority'], $pos['quantity'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 10: ORDER BY - Descending Order
// ============================================================================
echo "10. ORDER BY - Sort by quantity (DESC)\n";
echo str_repeat('-', 60) . "\n";

$template10 = [
    'project' => [
        'number' => '{{ ConstructionSite.nr_lv }}',
    ],
    'sorted_positions' => [
        'WHERE' => [
            '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
        ],
        'ORDER BY' => [
            '{{ ConstructionSite.Positions.Position.*.quantity }}' => 'DESC',
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'quantity' => '{{ ConstructionSite.Positions.Position.*.quantity }}',
        ],
    ],
];

$result10 = DataMapper::mapFromTemplate($template10, $constructionSite, true, true);

echo "Sorted Positions (by quantity DESC):\n";
foreach ($result10['sorted_positions'] as $pos) {
    echo "  - {$pos['number']}: {$pos['quantity']} units\n";
}
echo "\n";

// ============================================================================
// Example 11: Combined WHERE and ORDER BY
// ============================================================================
echo "11. Combined WHERE and ORDER BY - Filter gravel, sort by quantity\n";
echo str_repeat('-', 60) . "\n";

$template11 = [
    'project' => [
        'number' => '{{ ConstructionSite.nr_lv }}',
    ],
    'gravel_positions' => [
        'WHERE' => [
            'AND' => [
                '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
                '{{ ConstructionSite.Positions.Position.*.type }}' => 'gravel',
            ],
        ],
        'ORDER BY' => [
            '{{ ConstructionSite.Positions.Position.*.quantity }}' => 'DESC',
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'quantity' => '{{ ConstructionSite.Positions.Position.*.quantity }}',
        ],
    ],
];

$result11 = DataMapper::mapFromTemplate($template11, $constructionSite, true, true);

echo "Gravel Positions (sorted by quantity DESC):\n";
foreach ($result11['gravel_positions'] as $pos) {
    echo "  - {$pos['number']}: {$pos['quantity']} units\n";
}
echo "\n";

echo "=== All Examples Completed ===\n";


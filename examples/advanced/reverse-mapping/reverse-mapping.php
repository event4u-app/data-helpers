<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\DataMapper;

echo "=== Reverse Mapping Examples ===\n\n";

// ============================================================================
// Example 1: Simple Reverse Mapping
// ============================================================================
echo "1. Simple Reverse Mapping\n";
echo str_repeat('-', 50) . "\n";

// Define a mapping
$mapping = [
    'profile.name' => '{{ user.name }}',
    'profile.email' => '{{ user.email }}',
];

// Forward mapping: user -> profile
$user = ['user' => ['name' => 'John Doe', 'email' => 'john@example.com']];
$forwardResult = DataMapper::source($user)
    ->target([])
    ->template($mapping)
    ->map()
    ->getTarget();
echo "Forward (user -> profile):\n";
echo json_encode($forwardResult, JSON_PRETTY_PRINT) . PHP_EOL;

// Reverse mapping: profile -> user
$profile = ['profile' => ['name' => 'Jane Doe', 'email' => 'jane@example.com']];
$reverseResult = DataMapper::source($profile)
    ->target([])
    ->template($mapping)
    ->reverse()
    ->map()
    ->getTarget();
echo "\nReverse (profile -> user):\n";
echo json_encode($reverseResult, JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n";

// ============================================================================
// Example 2: Nested Reverse Mapping
// ============================================================================
echo "2. Nested Reverse Mapping\n";
echo str_repeat('-', 50) . "\n";

// Define a nested mapping
$nestedMapping = [
    'dto' => [
        'fullName' => '{{ person.name }}',
        'contact' => [
            'email' => '{{ person.email }}',
            'phone' => '{{ person.phone }}',
        ],
    ],
];

// Forward mapping: person -> dto
$person = [
    'person' => [
        'name' => 'Alice Smith',
        'email' => 'alice@example.com',
        'phone' => '+1234567890',
    ],
];
$forwardDto = DataMapper::source($person)
    ->target([])
    ->template($nestedMapping)
    ->map()
    ->getTarget();
echo "Forward (person -> dto):\n";
echo json_encode($forwardDto, JSON_PRETTY_PRINT) . PHP_EOL;

// Reverse mapping: dto -> person
$dto = [
    'dto' => [
        'fullName' => 'Bob Johnson',
        'contact' => [
            'email' => 'bob@example.com',
            'phone' => '+0987654321',
        ],
    ],
];
$reversePerson = DataMapper::source($dto)
    ->target([])
    ->template($nestedMapping)
    ->reverse()
    ->map()
    ->getTarget();
echo "\nReverse (dto -> person):\n";
echo json_encode($reversePerson, JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n";

// ============================================================================
// Example 3: Template-Based Reverse Mapping
// ============================================================================
echo "3. Template-Based Reverse Mapping\n";
echo str_repeat('-', 50) . "\n";

// Define a template
$template = [
    'profile' => [
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ],
    'address' => [
        'street' => '{{ location.street }}',
        'city' => '{{ location.city }}',
    ],
];

// Forward mapping: sources -> template structure
$sources = [
    'user' => ['name' => 'Charlie Brown', 'email' => 'charlie@example.com'],
    'location' => ['street' => '123 Main St', 'city' => 'Springfield'],
];
$forwardTemplate = DataMapper::source($sources)
    ->template($template)
    ->map()
    ->toArray();
echo "Forward (sources -> template structure):\n";
echo json_encode($forwardTemplate, JSON_PRETTY_PRINT) . PHP_EOL;

// Reverse mapping: template structure -> sources
$templateData = [
    'profile' => [
        'name' => 'David Wilson',
        'email' => 'david@example.com',
    ],
    'address' => [
        'street' => '456 Oak Ave',
        'city' => 'Shelbyville',
    ],
];
$reverseSources = DataMapper::source($templateData)
    ->template($template)
    ->reverse()
    ->map()
    ->toArray();
echo "\nReverse (template structure -> sources):\n";
echo json_encode($reverseSources, JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n";

// ============================================================================
// Example 4: Bidirectional Mapping with Target
// ============================================================================
echo "4. Bidirectional Mapping with Target\n";
echo str_repeat('-', 50) . "\n";

// Define a template
$bidirectionalTemplate = [
    'profile' => [
        'name' => 'user.name',
        'email' => 'user.email',
    ],
];

// Forward: sources -> template structure
$userSources = ['user' => ['name' => 'Eve Adams', 'email' => 'eve@example.com']];
$profileData = DataMapper::source($userSources)
    ->template($bidirectionalTemplate)
    ->map()
    ->toArray();
echo "Forward (sources -> template structure):\n";
echo json_encode($profileData, JSON_PRETTY_PRINT) . PHP_EOL;

// Reverse: template structure -> targets
$updatedProfileData = [
    'profile' => [
        'name' => 'Eve Adams-Smith',
        'email' => 'eve.smith@example.com',
    ],
];
$userTargets = ['user' => ['name' => null, 'email' => null]];
$updatedTargets = DataMapper::source($updatedProfileData)
    ->target($userTargets)
    ->template($bidirectionalTemplate)
    ->reverse()
    ->map()
    ->getTarget();
echo "\nReverse (template structure -> targets):\n";
echo json_encode($updatedTargets, JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n";

// ============================================================================
// Example 5: Wildcard Reverse Mapping
// ============================================================================
echo "5. Wildcard Reverse Mapping\n";
echo str_repeat('-', 50) . "\n";

// Define a mapping with wildcards
$wildcardMapping = [
    'names.*' => '{{ users.*.name }}',
];

// Forward mapping: users -> names
$users = [
    ['name' => 'Frank'],
    ['name' => 'Grace'],
    ['name' => 'Henry'],
];
$forwardNames = DataMapper::source(['users' => $users])
    ->target([])
    ->template($wildcardMapping)
    ->map()
    ->getTarget();
echo "Forward (users -> names):\n";
echo json_encode($forwardNames, JSON_PRETTY_PRINT) . PHP_EOL;

// Reverse mapping: names -> users
$names = ['Alice', 'Bob', 'Charlie'];
$reverseUsers = DataMapper::source(['names' => $names])
    ->target([])
    ->template($wildcardMapping)
    ->reverse()
    ->map()
    ->getTarget();
echo "\nReverse (names -> users):\n";
echo json_encode($reverseUsers, JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n";

// ============================================================================
// Example 6: Auto-Map (Symmetric)
// ============================================================================
echo "6. Auto-Map (Symmetric)\n";
echo str_repeat('-', 50) . "\n";

// Auto-map is symmetric, so we can use autoMap in both directions
$source = ['name' => 'Ivy Lee', 'email' => 'ivy@example.com'];
$target = ['name' => null, 'email' => null];

$autoMapped = DataMapper::source($source)
    ->target($target)
    ->autoMap()
    ->getTarget();
echo "Auto-mapped:\n";
echo json_encode($autoMapped, JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n";

// ============================================================================
// Example 7: Pipeline with Filters
// ============================================================================
echo "7. Pipeline with Filters\n";
echo str_repeat('-', 50) . "\n";

use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;

// Define a mapping
$pipelineMapping = [
    'output.name' => '{{ input.name }}',
    'output.email' => '{{ input.email }}',
];

// Reverse mapping with filters
$input = ['input' => ['name' => '  jack brown  ', 'email' => '  jack@example.com  ']];
$pipelineResult = DataMapper::source($input)
    ->target([])
    ->template($pipelineMapping)
    ->pipeline([new TrimStrings(), new UppercaseStrings()])
    ->reverse()
    ->map()
    ->getTarget();
echo "Reverse mapping with filters (trim + uppercase):\n";
echo json_encode($pipelineResult, JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n=== End of Examples ===\n";

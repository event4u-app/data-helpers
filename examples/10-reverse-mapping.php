<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\ReverseDataMapper;

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
$forwardResult = DataMapper::map($user, [], $mapping);
echo "Forward (user -> profile):\n";
print_r($forwardResult);

// Reverse mapping: profile -> user
$profile = ['profile' => ['name' => 'Jane Doe', 'email' => 'jane@example.com']];
$reverseResult = ReverseDataMapper::map($profile, [], $mapping);
echo "\nReverse (profile -> user):\n";
print_r($reverseResult);

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
$forwardDto = DataMapper::map($person, [], $nestedMapping);
echo "Forward (person -> dto):\n";
print_r($forwardDto);

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
$reversePerson = ReverseDataMapper::map($dto, [], $nestedMapping);
echo "\nReverse (dto -> person):\n";
print_r($reversePerson);

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
$forwardTemplate = DataMapper::mapFromTemplate($template, $sources);
echo "Forward (sources -> template structure):\n";
print_r($forwardTemplate);

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
$reverseSources = ReverseDataMapper::mapFromTemplate($template, $templateData);
echo "\nReverse (template structure -> sources):\n";
print_r($reverseSources);

echo "\n";

// ============================================================================
// Example 4: Bidirectional Mapping with mapToTargetsFromTemplate
// ============================================================================
echo "4. Bidirectional Mapping with mapToTargetsFromTemplate\n";
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
$profileData = DataMapper::mapFromTemplate($bidirectionalTemplate, $userSources);
echo "Forward (sources -> template structure):\n";
print_r($profileData);

// Reverse: template structure -> targets
$updatedProfileData = [
    'profile' => [
        'name' => 'Eve Adams-Smith',
        'email' => 'eve.smith@example.com',
    ],
];
$userTargets = ['user' => ['name' => null, 'email' => null]];
$updatedTargets = ReverseDataMapper::mapToTargetsFromTemplate(
    $updatedProfileData,
    $bidirectionalTemplate,
    $userTargets
);
echo "\nReverse (template structure -> targets):\n";
print_r($updatedTargets);

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
$forwardNames = DataMapper::map(['users' => $users], [], $wildcardMapping);
echo "Forward (users -> names):\n";
print_r($forwardNames);

// Reverse mapping: names -> users
$names = ['Alice', 'Bob', 'Charlie'];
$reverseUsers = ReverseDataMapper::map(['names' => $names], [], $wildcardMapping);
echo "\nReverse (names -> users):\n";
print_r($reverseUsers);

echo "\n";

// ============================================================================
// Example 6: Auto-Map (Symmetric)
// ============================================================================
echo "6. Auto-Map (Symmetric)\n";
echo str_repeat('-', 50) . "\n";

// Auto-map is symmetric, so ReverseDataMapper just delegates to DataMapper
$source = ['name' => 'Ivy Lee', 'email' => 'ivy@example.com'];
$target = ['name' => null, 'email' => null];

$autoMapped = ReverseDataMapper::autoMap($source, $target);
echo "Auto-mapped:\n";
print_r($autoMapped);

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

// Create a pipeline with filters
$pipeline = ReverseDataMapper::pipe([new TrimStrings(), new UppercaseStrings()]);

// Reverse mapping with filters
$input = ['input' => ['name' => '  jack brown  ', 'email' => '  jack@example.com  ']];
$pipelineResult = $pipeline->map($input, [], $pipelineMapping);
echo "Reverse mapping with filters (trim + uppercase):\n";
print_r($pipelineResult);

echo "\n=== End of Examples ===\n";


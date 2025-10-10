<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

// Source data
$sources = [
    'user' => [
        'firstName' => 'alice',
        'lastName' => 'SMITH',
        'email' => '  ALICE@EXAMPLE.COM  ',
        'age' => null,
        'score' => 150,
        'rating' => 2.5,
    ],
    'address' => [
        'city' => 'Berlin',
        'country' => 'Germany',
    ],
];

// Template with expressions
$template = [
    'profile' => [
        // Simple expression
        'firstName' => '{{ user.firstName | ucfirst }}',

        // Expression with default value
        'age' => '{{ user.age ?? 18 }}',

        // Multiple filters
        'email' => '{{ user.email | trim | lower }}',

        // Combined filters
        'fullName' => '{{ user.firstName | ucfirst }}',

        // Regular reference (no expression)
        'city' => '{{ address.city }}',

        // Range validation (between returns boolean)
        'isValidScore' => '{{ user.score | between:0:100 }}',
        'isValidRating' => '{{ user.rating | between:1:5 }}',

        // Value clamping (clamp limits value to range)
        'normalizedScore' => '{{ user.score | clamp:0:100 }}',
        'normalizedRating' => '{{ user.rating | clamp:1:5 }}',
    ],
    'contact' => [
        'email' => '{{ user.email | trim | lower }}',
        'city' => '{{ address.city }}',
        'country' => '{{ address.country }}',
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources);

echo json_encode($result, JSON_PRETTY_PRINT);
echo PHP_EOL;

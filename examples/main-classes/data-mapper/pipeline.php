<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\SkipEmptyValues;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;

// Source data with messy values
$source = [
    'user' => [
        'name' => '  Alice  ',
        'email' => '  ALICE@EXAMPLE.COM  ',
        'phone' => '',
        'city' => '  Berlin  ',
    ],
];

// Mapping configuration
$mapping = [
    'profile' => [
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
        'phone' => '{{ user.phone }}',
        'city' => '{{ user.city }}',
    ],
];

// Apply transformation pipeline
$result = DataMapper::source($source)
    ->target([])
    ->template($mapping)
    ->pipeline([
        new TrimStrings(),           // Trim whitespace
        new LowercaseEmails(),       // Lowercase email addresses
        new SkipEmptyValues(),       // Skip empty values
    ])
    ->map()
    ->getTarget();

echo json_encode($result, JSON_PRETTY_PRINT);
echo PHP_EOL;

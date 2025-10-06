<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\SkipEmptyValues;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;

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
$result = DataMapper::pipe([
    TrimStrings::class,           // Trim whitespace
    LowercaseEmails::class,       // Lowercase email addresses
    SkipEmptyValues::class,       // Skip empty values
])->map($source, [], $mapping);

echo json_encode($result, JSON_PRETTY_PRINT);
echo PHP_EOL;

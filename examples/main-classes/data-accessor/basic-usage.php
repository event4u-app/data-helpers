<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\DataAccessor;

$src = [
    'user' => [
        'name' => 'Alice',
        'emails' => [
            [
                'type' => 'work',
                'value' => 'alice@work.test',
            ],
            [
                'type' => 'home',
                'value' => 'alice@home.test',
            ],
        ],
    ],
];

$acc = new DataAccessor($src);

// Simple path
$name = $acc->get('user.name');

// Wildcard path (returns list)
$emails = $acc->get('user.emails.*.value');

echo json_encode([
    'name' => $name,
    'emails' => $emails,
], JSON_PRETTY_PRINT);
echo PHP_EOL;

<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

$src = [
    'user' => [
        'name' => ' Alice ',
        'emails' => ['a@work', 'a@home'],
    ],
];
$tgt = [];

$map = [
    'profile' => [
        'name' => '{{ user.name }}',
        'contacts' => [
            '*' => '{{ user.emails.* }}',
        ],
    ],
];

$res = DataMapper::source($src)
    ->target($tgt)
    ->template($map)
    ->skipNull(true)
    ->trimValues(true)
    ->map()
    ->getTarget();

echo json_encode($res, JSON_PRETTY_PRINT);
echo PHP_EOL;

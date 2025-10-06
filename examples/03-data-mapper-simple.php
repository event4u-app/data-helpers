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

$res = DataMapper::map($src, $tgt, $map, true, true);

echo json_encode($res, JSON_PRETTY_PRINT);
echo PHP_EOL;

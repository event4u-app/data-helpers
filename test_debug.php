<?php

require __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

$template = [
    'profile' => [
        'fullname' => '{{ user.name }}',
        'copy' => '@profile.fullname',
    ],
];

$sources = [
    'user' => ['name' => 'Alice'],
];

$result = DataMapper::mapFromTemplate($template, $sources);

var_dump($result);

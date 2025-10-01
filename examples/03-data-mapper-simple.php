<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

$src = ['user' => ['name' => ' Alice ', 'emails' => ['a@work', 'a@home']]];
$tgt = [];

$map = [
    'user.name' => 'profile.name',
    'user.emails.*' => 'profile.contacts.*',
];

$res = DataMapper::map($src, $tgt, $map, skipNull: true, reindex: true);

var_export($res); echo "\n";

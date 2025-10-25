<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\DataMutator;

$tgt = [];
DataMutator::make($tgt)
    ->set('profile.name', 'Alice')
    ->set('profile.emails.0', 'alice@work.test')
    ->set('profile.emails.1', 'alice@home.test');

echo json_encode($tgt, JSON_PRETTY_PRINT);
echo PHP_EOL;

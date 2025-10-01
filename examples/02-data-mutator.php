<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMutator;

$tgt = [];
$tgt = DataMutator::set($tgt, 'profile.name', 'Alice');
$tgt = DataMutator::set($tgt, 'profile.emails.0', 'alice@work.test');
$tgt = DataMutator::set($tgt, 'profile.emails.1', 'alice@home.test');

var_export($tgt); echo "\n";

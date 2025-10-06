<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Hooks;
use event4u\DataHelpers\Enums\DataMapperHook;

$src = [
    'user' => [
        'name' => ' Alice ',
    ],
];
$tgt = [];

$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeAll, fn(AllContext $ctx): null // Example: initialize shared state (if needed)
    => null)
    ->on(DataMapperHook::PreTransform, fn($v, PairContext $ctx): mixed // Trim strings before mapping
    => is_string($v) ? trim($v) : $v)
    ->toArray();

$res = DataMapper::map($src, $tgt, [
    'profile' => [
        'name' => '{{ user.name }}',
    ],
], true, false, $hooks);

echo json_encode($res, JSON_PRETTY_PRINT);
echo PHP_EOL;

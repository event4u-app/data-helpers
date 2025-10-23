<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

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
    ->on(DataMapperHook::BeforeTransform, fn($v, PairContext $ctx): mixed // Trim strings before mapping
    => is_string($v) ? trim($v) : $v)
    ->toArray();

$res = DataMapper::source($src)
    ->target($tgt)
    ->template([
        'profile' => [
            'name' => '{{ user.name }}',
        ],
    ])
    ->hooks($hooks)
    ->skipNull(true)
    ->reindexWildcard(false)
    ->map()
    ->getTarget();

echo json_encode($res, JSON_PRETTY_PRINT);
echo PHP_EOL;

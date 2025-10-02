<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Hooks;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\Enums\DataMapperHook;

$src = ['user' => ['name' => ' Alice ']];
$tgt = [];

$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeAll, function (AllContext $ctx): null {
        // Example: initialize shared state (if needed)
        return null;
    })
    ->on(DataMapperHook::PreTransform, function ($v, PairContext $ctx): mixed {
        // Trim strings before mapping
        return is_string($v) ? trim($v) : $v;
    })
    ->toArray();

$res = DataMapper::map($src, $tgt, ['user.name' => 'profile.name'], hooks: $hooks);

var_export($res); echo "\n";

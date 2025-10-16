<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Context\WriteContext;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\Enums\DataMapperHook;

describe('DataMapper Hooks', function (): void {
    test('beforeAll/afterAll are called in simple mapping', function (): void {
        $source = [
            'a' => 1,
            'b' => 2,
        ];
        $target = [];

        $events = [];

        /** @var array<string, mixed> $hooks */
        $hooks = [
            DataMapperHook::BeforeAll->value => function (AllContext $ctx) use (&$events): void {
                $events[] = DataMapperHook::BeforeAll->value . ':' . $ctx->mode();
            },
            DataMapperHook::AfterAll->value => function (AllContext $ctx) use (&$events): void {
                $events[] = DataMapperHook::AfterAll->value . ':' . $ctx->mode();
            },
        ];

        $res = DataMapper::source($source)
            ->target($target)
            ->template([
                'x.a' => '{{ a }}',
                'x.b' => '{{ b }}',
            ])
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($res)->toBe([
            'x' => [
                'a' => 1,
                'b' => 2,
            ],
        ]);
        expect($events)->toEqual([
            DataMapperHook::BeforeAll->value . ':simple',
            DataMapperHook::AfterAll->value . ':simple',
        ]);
    });

    test('beforePair can skip a pair', function (): void {
        $source = [
            'a' => 1,
            'b' => 2,
        ];
        $target = [];

        /** @var array<string, mixed> $hooks */
        $hooks = [
            DataMapperHook::BeforePair->value => [
                // skip when srcPath is 'a'
                'src:a' => function (PairContext $ctx): bool {
                    return false; // cancel this pair
                },
            ],
        ];

        $res = DataMapper::source($source)
            ->target($target)
            ->template([
                'x.a' => '{{ a }}',
                'x.b' => '{{ b }}',
            ])
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($res)->toBe([
            'x' => [
                'b' => 2,
            ],
        ]);
    });

    test('beforeTransform and afterTransform modify values', function (): void {
        $source = [
            'name' => 'alice',
        ];
        $target = [];

        /** @var array<string, mixed> $hooks */
        $hooks = [
            DataMapperHook::BeforeTransform->value => function (mixed $v, PairContext $ctx): mixed {
                if (is_string($v)) {
                    return "pre-{$v}";
                }

                return $v;
            },
            DataMapperHook::AfterTransform->value => function (mixed $v, PairContext $ctx): mixed {
                if (is_string($v)) {
                    return "{$v}-post";
                }

                return $v;
            },
        ];

        $res = DataMapper::source($source)
            ->target($target)
            ->template([
                'out.name' => '{{ name }}',
            ])
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($res)->toBe([
            'out' => [
                'name' => 'pre-alice-post',
            ],
        ]);
    });

    test('beforeWrite can skip and afterWrite can mutate target', function (): void {
        $source = [
            'items' => ['a', null, 'b'],
        ];
        $target = [];

        /** @var array<string, mixed> $hooks */
        $hooks = [
            DataMapperHook::BeforeWrite->value => function (mixed $v, WriteContext $ctx): mixed {
                // Skip writing value 'a'
                if ('a' === $v) {
                    return '__skip__';
                }

                return $v;
            },
            DataMapperHook::AfterWrite->value => function (array|object $tgt, WriteContext $ctx, mixed $written): array|object {
                // Uppercase strings after write
                if (is_string($written)) {
                    $path = $ctx->resolvedTargetPath ?? '';

                    return DataMutator::set($tgt, $path, strtoupper($written));
                }

                return $tgt;
            },
        ];

        $res = DataMapper::source($source)
            ->target($target)
            ->template([
                'out.items.*' => '{{ items.* }}',
            ])
            ->reindexWildcard(true)
            ->hooks($hooks)
            ->map()
            ->getTarget();

        // 'a' skipped by beforeWrite; 'b' gets uppercased by afterWrite
        expect($res)->toBe([
            'out' => [
                'items' => ['B'],
            ],
        ]);
    });

    test('path prefix filters for hooks work (src:/tgt:/mode:)', function (): void {
        $source = [
            'a' => 1,
            'b' => 2,
        ];
        $target = [];

        $calls = [];

        /** @var array<string, mixed> $hooks */
        $hooks = [
            DataMapperHook::BeforePair->value => [
                'src:a' => function (PairContext $ctx) use (&$calls): void {
                    $calls[] = 'src:a';
                },
                'tgt:x.b' => function (PairContext $ctx) use (&$calls): void {
                    $calls[] = 'tgt:x.b';
                },
                'mode:simple' => function (PairContext $ctx) use (&$calls): void {
                    $calls[] = 'mode:simple';
                },
            ],
        ];

        DataMapper::source($source)
            ->target($target)
            ->template([
                'x.a' => '{{ a }}',
                'x.b' => '{{ b }}',
            ])
            ->hooks($hooks)
            ->map();

        // Order is not strictly guaranteed, but all three should have been called at least once
        sort($calls);
        $unique = array_values(array_unique($calls));
        expect($unique)->toEqual(['mode:simple', 'src:a', 'tgt:x.b']);
    });
});

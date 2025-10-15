<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Context\WriteContext;
use event4u\DataHelpers\DataMutator;

describe('DataMapper Hooks', function(): void {
    test('beforeAll/afterAll are called in simple mapping', function(): void {
        $source = [
            'a' => 1,
            'b' => 2,
        ];
        $target = [];

        $events = [];

        /** @var array<string, mixed> $hooks */
        $hooks = [
            'beforeAll' => function(AllContext $ctx) use (&$events): void {
                $events[] = 'beforeAll:' . $ctx->mode();
            },
            'afterAll' => function(AllContext $ctx) use (&$events): void {
                $events[] = 'afterAll:' . $ctx->mode();
            },
        ];

        $res = DataMapper::source($source)
            ->target($target)
            ->template([
                'x.a' => '{{ a }}',
                'x.b' => '{{ b }}',
            ])
            ->skipNull(true)
            ->reindexWildcard(false)
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($res)->toBe([
            'x' => [
                'a' => 1,
                'b' => 2,
            ],
        ]);
        expect($events)->toEqual(['beforeAll:simple', 'afterAll:simple']);
    });

    test('beforePair can skip a pair', function(): void {
        $source = [
            'a' => 1,
            'b' => 2,
        ];
        $target = [];

        /** @var array<string, mixed> $hooks */
        $hooks = [
            'beforePair' => [
                // skip when srcPath is 'a'
                'src:a' => function(PairContext $ctx): bool {
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
            ->skipNull(true)
            ->reindexWildcard(false)
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($res)->toBe([
            'x' => [
                'b' => 2,
            ],
        ]);
    });

    test('preTransform and postTransform modify values', function(): void {
        $source = [
            'name' => 'alice',
        ];
        $target = [];

        /** @var array<string, mixed> $hooks */
        $hooks = [
            'preTransform' => function(mixed $v, PairContext $ctx): mixed {
                if (is_string($v)) {
                    return 'pre-' . $v;
                }

                return $v;
            },
            'postTransform' => function(mixed $v, PairContext $ctx): mixed {
                if (is_string($v)) {
                    return $v . '-post';
                }

                return $v;
            },
        ];

        $res = DataMapper::source($source)
            ->target($target)
            ->template([
                'out.name' => '{{ name }}',
            ])
            ->skipNull(true)
            ->reindexWildcard(false)
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($res)->toBe([
            'out' => [
                'name' => 'pre-alice-post',
            ],
        ]);
    });

    test('beforeWrite can skip and afterWrite can mutate target', function(): void {
        $source = [
            'items' => ['a', null, 'b'],
        ];
        $target = [];

        /** @var array<string, mixed> $hooks */
        $hooks = [
            'beforeWrite' => function(mixed $v, WriteContext $ctx): mixed {
                // Skip writing value 'a'
                if ('a' === $v) {
                    return '__skip__';
                }

                return $v;
            },
            'afterWrite' => function(array|object $tgt, WriteContext $ctx, mixed $written): array|object {
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
            ->skipNull(true)
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

    test('path prefix filters for hooks work (src:/tgt:/mode:)', function(): void {
        $source = [
            'a' => 1,
            'b' => 2,
        ];
        $target = [];

        $calls = [];

        /** @var array<string, mixed> $hooks */
        $hooks = [
            'beforePair' => [
                'src:a' => function(PairContext $ctx) use (&$calls): void {
                    $calls[] = 'src:a';
                },
                'tgt:x.b' => function(PairContext $ctx) use (&$calls): void {
                    $calls[] = 'tgt:x.b';
                },
                'mode:simple' => function(PairContext $ctx) use (&$calls): void {
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
            ->skipNull(true)
            ->reindexWildcard(false)
            ->hooks($hooks)
            ->map();

        // Order is not strictly guaranteed, but all three should have been called at least once
        sort($calls);
        $unique = array_values(array_unique($calls));
        expect($unique)->toEqual(['mode:simple', 'src:a', 'tgt:x.b']);
    });
});

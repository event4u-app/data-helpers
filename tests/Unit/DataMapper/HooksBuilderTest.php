<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Context\WriteContext;
use event4u\DataHelpers\DataMapper\Hooks;
use event4u\DataHelpers\Enums\DataMapperHook;
use event4u\DataHelpers\Enums\Mode;

/**
 * @internal
 */
describe('DataMapper Hooks Builder', function(): void {
    test('fluent convenience methods onForSrc/onForTgt/onForMode work and integrate', function(): void {
        $src = [
            'user' => [
                'name' => ' Alice ',
            ],
        ];
        $tgt = [];

        $beforeAllCount = 0;

        $hooks = Hooks::make()
            ->onForMode(DataMapperHook::BeforeAll, 'simple', function(AllContext $ctx) use (&$beforeAllCount): null {
                $beforeAllCount++;

                return null;
            })
            ->onForSrc(DataMapperHook::BeforePair, 'user.name', fn(PairContext $ctx): false => false) // skip this pair
            ->onForTgt(DataMapperHook::BeforeWrite, 'profile.', fn($v): string => '__skip__')
            ->toArray();

        /** @var array<string, mixed> $res */
        $res = DataMapper::source($src)
            ->target($tgt)
            ->template([
                'user.name' => '{{ profile.name }}',
            ])
            ->skipNull(false)
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($beforeAllCount)->toBe(1)
            ->and($res)->not()->toHaveKey('profile');
    });
    test('build() accepts list-of-pairs with enum names and normalizes', function(): void {
        $hooks = Hooks::build([
            [
                DataMapperHook::BeforeAll,
                fn(AllContext $ctx): null => null,
            ],
            [
                DataMapperHook::BeforePair,
                [
                    'src:user.*' => fn(PairContext $ctx): null => null,
                ],
            ],
        ]);

        expect($hooks)
            ->toHaveKey(DataMapperHook::BeforeAll->value)
            ->and($hooks[DataMapperHook::BeforeAll->value])->toBeCallable()
            ->and($hooks)
            ->toHaveKey(DataMapperHook::BeforePair->value)
            ->and($hooks[DataMapperHook::BeforePair->value])->toBeArray();
        assert(is_array($hooks[DataMapperHook::BeforePair->value]));
        expect(array_key_exists('src:user.*', $hooks[DataMapperHook::BeforePair->value]))->toBeTrue();
    });

    test('merge() merges shallowly and later overrides earlier', function(): void {
        $a = Hooks::build([
            [
                DataMapperHook::BeforeTransform,
                fn($v) => is_string($v) ? trim($v) : $v,
            ],
            [
                DataMapperHook::BeforeWrite,
                fn($v) => $v,
            ],
        ]);
        $b = Hooks::build([
            [
                DataMapperHook::BeforeWrite,
                fn($v) => '' === $v ? '__skip__' : $v,
            ],
        ]);

        $merged = Hooks::merge($a, $b);

        expect($merged)
            ->toHaveKey(DataMapperHook::BeforeTransform->value)
            ->and($merged[DataMapperHook::BeforeTransform->value])->toBeCallable()
            ->and($merged)
            ->toHaveKey(DataMapperHook::BeforeWrite->value)
            ->and($merged[DataMapperHook::BeforeWrite->value])->toBeCallable();
    });

    test('fluent make()/on()/onMany()/mergeIn()/toArray()', function(): void {
        $builder = Hooks::make()
            ->on(DataMapperHook::BeforeAll, fn(AllContext $ctx): null => null)
            ->onMany([
                [
                    DataMapperHook::BeforePair,
                    [
                        'mode:simple' => fn(PairContext $ctx): null => null,
                    ],
                ],
            ])
            ->mergeIn(
                Hooks::build([
                    [
                        DataMapperHook::AfterWrite,
                        fn(WriteContext $ctx, mixed $written, array|object $target): array|object => $target,
                    ],
                ])
            );

        $hooks = $builder->toArray();

        expect($hooks)
            ->toHaveKey(DataMapperHook::BeforeAll->value)
            ->and($hooks[DataMapperHook::BeforeAll->value])->toBeCallable()
            ->and($hooks)
            ->toHaveKey(DataMapperHook::BeforePair->value)
            ->and($hooks[DataMapperHook::BeforePair->value])->toBeArray();
        assert(is_array($hooks[DataMapperHook::BeforePair->value]));
        expect(array_key_exists('mode:simple', $hooks[DataMapperHook::BeforePair->value]))->toBeTrue()
            ->and($hooks)
            ->toHaveKey(DataMapperHook::AfterWrite->value);
    });

    test('integration: DataMapper works with hooks built by builder', function(): void {
        $src = [
            'user' => [
                'name' => '  alice  ',
            ],
        ];
        $tgt = [];

        $hooks = Hooks::make()
            ->on(DataMapperHook::BeforeTransform, fn($v) => is_string($v) ? trim($v) : $v)
            ->on(DataMapperHook::BeforeWrite, fn($v) => '' === $v ? '__skip__' : $v)
            ->toArray();

        /** @var array{profile: array{name: string}} $res */
        $res = DataMapper::source($src)
            ->target($tgt)
            ->template([
                'profile.name' => '{{ user.name }}',
            ])
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($res)
            ->toHaveKey('profile')
            ->and($res['profile'])
            ->toHaveKey('name')
            ->and($res['profile']['name'])->toBe('alice');
    });
    test('onForModeEnum works with Mode enum', function(): void {
        $src = [
            'a' => 1,
        ];
        $tgt = [];

        $count = 0;
        $hooks = Hooks::make()
            ->onForModeEnum(DataMapperHook::BeforeAll, Mode::Simple, function(AllContext $ctx) use (&$count): null {
                $count++;

                return null;
            })
            ->toArray();

        DataMapper::source($src)
            ->target($tgt)
            ->template([
                'a' => '{{ x.a }}',
            ])
            ->skipNull(false)
            ->hooks($hooks)
            ->map();
        expect($count)->toBe(1);
    });

    test('onForPrefix filters by either src or tgt path without double invocation', function(): void {
        $src = [
            'users' => [
                [
                    'email' => 'a@example.com',
                ],
                [
                    'email' => 'b@example.com',
                ],
            ],
        ];
        $tgt = [];

        $calls = 0;
        $hooks = Hooks::make()
            ->onForPrefix(DataMapperHook::AfterTransform, 'users.*.email', function($v) use (&$calls) {
                $calls++;

                return strtoupper((string)$v);
            })
            ->toArray();

        /** @var array{dest: array{mails: array<int,string>}} $res */
        $res = DataMapper::source($src)
            ->target($tgt)
            ->template([
                'dest.mails.*' => '{{ users.*.email }}',
            ])
            ->skipNull(false)
            ->reindexWildcard(true)
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($calls)->toBe(2)
            ->and($res['dest']['mails'])->toEqual(['A@EXAMPLE.COM', 'B@EXAMPLE.COM']);
    });

    test('onForPrefix can target tgt path (beforeWrite)', function(): void {
        $src = [
            'user' => [
                'name' => 'Alice',
            ],
        ];
        $tgt = [];

        $hooks = Hooks::make()
            ->onForPrefix(DataMapperHook::BeforeWrite, 'profile.', fn($v): string => '__skip__')
            ->toArray();

        /** @var array<string,mixed> $res */
        $res = DataMapper::source($src)
            ->target($tgt)
            ->template([
                'user.name' => '{{ profile.name }}',
            ])
            ->skipNull(false)
            ->hooks($hooks)
            ->map()
            ->getTarget();

        expect($res)->not()->toHaveKey('profile');
    });
});

<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Context\WriteContext;
use event4u\DataHelpers\Enums\Mode;

describe('PairContext', function(): void {
    it('creates context with all required properties', function(): void {
        $source = ['name' => 'John'];
        $target = ['name' => 'Jane'];

        $context = new PairContext('simple', 0, 'source.name', 'target.name', $source, $target);

        expect($context->mode)->toBe('simple');
        expect($context->pairIndex)->toBe(0);
        expect($context->srcPath)->toBe('source.name');
        expect($context->tgtPath)->toBe('target.name');
        expect($context->source)->toBe($source);
        expect($context->target)->toBe($target);
    });

    it('creates context with wildcard index', function(): void {
        $context = new PairContext('simple', 5, 'items.*.name', 'results.*.name', [], [], 3);

        expect($context->wildcardIndex)->toBe(3);
    });

    it('creates context with extra data', function(): void {
        /** @var array<int, mixed> $extraData */
        $extraData = ['value1', 'value2'];
        $context = new PairContext('simple', 0, 'src', 'tgt', [], [], null, $extraData);

        expect($context->extra())->toBe($extraData);
    });

    it('returns mode as string', function(): void {
        $context = new PairContext('simple', 0, 'src', 'tgt', [], []);

        expect($context->mode())->toBe('simple');
    });

    it('returns mode as enum', function(): void {
        $context = new PairContext('structured', 0, 'src', 'tgt', [], []);

        expect($context->modeEnum())->toBe(Mode::Structured);
    });

    it('returns srcPath', function(): void {
        $context = new PairContext('simple', 0, 'source.path', 'target.path', [], []);

        expect($context->srcPath())->toBe('source.path');
    });

    it('returns tgtPath', function(): void {
        $context = new PairContext('simple', 0, 'source.path', 'target.path', [], []);

        expect($context->tgtPath())->toBe('target.path');
    });

    it('returns empty array for extra when not provided', function(): void {
        $context = new PairContext('simple', 0, 'src', 'tgt', [], []);

        expect($context->extra())->toBe([]);
    });

    it('implements HookContext interface', function(): void {
        $context = new PairContext('simple', 0, 'src', 'tgt', [], []);

        expect($context)->toBeInstanceOf(HookContext::class);
    });

    it('handles wildcard paths', function(): void {
        $context = new PairContext('simple', 2, 'items.*.name', 'results.*.fullName', [], [], 5);

        expect($context->srcPath)->toBe('items.*.name');
        expect($context->tgtPath)->toBe('results.*.fullName');
        expect($context->wildcardIndex)->toBe(5);
    });

    it('handles null wildcard index', function(): void {
        $context = new PairContext('simple', 0, 'name', 'fullName', [], []);

        expect($context->wildcardIndex)->toBeNull();
    });

    it('handles string wildcard index', function(): void {
        $context = new PairContext('simple', 0, 'items.*.name', 'results.*.name', [], [], 'key123');

        expect($context->wildcardIndex)->toBe('key123');
    });

    it('preserves source and target references', function(): void {
        $source = (object)['id' => 1];
        $target = (object)['id' => 2];

        $context = new PairContext('simple', 0, 'src', 'tgt', $source, $target);

        expect($context->source)->toBe($source);
        expect($context->target)->toBe($target);

        $sourceAcc = new DataAccessor($context->source);
        $targetAcc = new DataAccessor($context->target);
        expect($sourceAcc->get('id'))->toBe(1);
        expect($targetAcc->get('id'))->toBe(2);
    });

    it('handles high pair index', function(): void {
        $context = new PairContext('simple', 999, 'src', 'tgt', [], []);

        expect($context->pairIndex)->toBe(999);
    });

    it('handles complex extra data', function(): void {
        /** @var array<int, mixed> $extraData */
        $extraData = [
            'uppercase',
            ['trim' => true, 'maxLength' => 100],
            ['required', 'email'],
        ];

        $context = new PairContext('simple', 0, 'src', 'tgt', [], [], null, $extraData);

        $extra = $context->extra();
        expect($extra)->toBe($extraData);
        expect($extra[0] ?? null)->toBe('uppercase');
        expect($extra[1] ?? null)->toBeArray();
    });

    it('is not final class', function(): void {
        $reflection = new ReflectionClass(PairContext::class);

        expect($reflection->isFinal())->toBeFalse();
    });

    it('can be extended by WriteContext', function(): void {
        $reflection = new ReflectionClass(WriteContext::class);
        $parent = $reflection->getParentClass();

        expect(false !== $parent ? $parent->getName() : null)->toBe(PairContext::class);
    });

    it('handles empty paths', function(): void {
        $context = new PairContext('simple', 0, '', '', [], []);

        expect($context->srcPath)->toBe('');
        expect($context->tgtPath)->toBe('');
    });

    it('handles dot notation paths', function(): void {
        $context = new PairContext('simple', 0, 'user.profile.name', 'person.fullName', [], []);

        expect($context->srcPath)->toBe('user.profile.name');
        expect($context->tgtPath)->toBe('person.fullName');
    });

    it('is used for beforePair and afterPair hooks', function(): void {
        // This is a documentation test - PairContext is for beforePair/afterPair
        $context = new PairContext('simple', 0, 'src', 'tgt', [], []);

        expect($context)->toBeInstanceOf(PairContext::class);
        // Verify it has access to pair-specific data
        expect($context->pairIndex)->toBeInt();
        expect($context->srcPath())->toBeString();
        expect($context->tgtPath())->toBeString();
    });
});

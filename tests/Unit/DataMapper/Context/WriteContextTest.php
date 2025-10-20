<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Context\WriteContext;
use event4u\DataHelpers\Enums\Mode;

describe('WriteContext', function(): void {
    it('creates context with all required properties', function(): void {
        $source = ['name' => 'John'];
        $target = ['name' => 'Jane'];

        $context = new WriteContext('simple', 0, 'source.name', 'target.name', $source, $target);

        expect($context->mode)->toBe('simple');
        expect($context->pairIndex)->toBe(0);
        expect($context->srcPath)->toBe('source.name');
        expect($context->tgtPath)->toBe('target.name');
        expect($context->source)->toBe($source);
        expect($context->target)->toBe($target);
    });

    it('creates context with resolved target path', function(): void {
        $context = new WriteContext('simple', 0, 'items.*.name', 'results.*.name', [], [], 'results.0.name');

        expect($context->resolvedTargetPath)->toBe('results.0.name');
    });

    it('creates context with wildcard index', function(): void {
        $context = new WriteContext('simple', 5, 'items.*.name', 'results.*.name', [], [], null, 3);

        expect($context->wildcardIndex)->toBe(3);
    });

    it('extends PairContext', function(): void {
        $context = new WriteContext('simple', 0, 'src', 'tgt', [], []);

        expect($context)->toBeInstanceOf(PairContext::class);
    });

    it('returns mode as string', function(): void {
        $context = new WriteContext('simple', 0, 'src', 'tgt', [], []);

        expect($context->mode())->toBe('simple');
    });

    it('returns mode as enum', function(): void {
        $context = new WriteContext('structured', 0, 'src', 'tgt', [], []);

        expect($context->modeEnum())->toBe(Mode::Structured);
    });

    it('returns srcPath', function(): void {
        $context = new WriteContext('simple', 0, 'source.path', 'target.path', [], []);

        expect($context->srcPath())->toBe('source.path');
    });

    it('returns tgtPath', function(): void {
        $context = new WriteContext('simple', 0, 'source.path', 'target.path', [], []);

        expect($context->tgtPath())->toBe('target.path');
    });

    it('implements HookContext interface', function(): void {
        $context = new WriteContext('simple', 0, 'src', 'tgt', [], []);

        expect($context)->toBeInstanceOf(HookContext::class);
    });

    it('handles null resolved target path', function(): void {
        $context = new WriteContext('simple', 0, 'src', 'tgt', [], []);

        expect($context->resolvedTargetPath)->toBeNull();
    });

    it('handles wildcard resolution', function(): void {
        $context = new WriteContext(
            'simple',
            2,
            'items.*.name',
            'results.*.fullName',
            [],
            [],
            'results.2.fullName',
            2
        );

        expect($context->srcPath)->toBe('items.*.name');
        expect($context->tgtPath)->toBe('results.*.fullName');
        expect($context->resolvedTargetPath)->toBe('results.2.fullName');
        expect($context->wildcardIndex)->toBe(2);
    });

    it('preserves source and target references', function(): void {
        $source = (object)['id' => 1];
        $target = (object)['id' => 2];

        $context = new WriteContext('simple', 0, 'src', 'tgt', $source, $target);

        expect($context->source)->toBe($source);
        expect($context->target)->toBe($target);

        $sourceAcc = new DataAccessor($context->source);
        $targetAcc = new DataAccessor($context->target);
        expect($sourceAcc->get('id'))->toBe(1);
        expect($targetAcc->get('id'))->toBe(2);
    });

    it('inherits extra method from PairContext', function(): void {
        $context = new WriteContext('simple', 0, 'src', 'tgt', [], []);

        expect($context->extra())->toBe([]);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(WriteContext::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('has resolvedTargetPath as additional property', function(): void {
        $reflection = new ReflectionClass(WriteContext::class);

        expect($reflection->hasProperty('resolvedTargetPath'))->toBeTrue();
    });

    it('handles complex resolved paths', function(): void {
        $context = new WriteContext(
            'simple',
            10,
            'data.items.*.nested.*.value',
            'output.results.*.data.*.val',
            [],
            [],
            'output.results.5.data.3.val'
        );

        expect($context->resolvedTargetPath)->toBe('output.results.5.data.3.val');
    });

    it('handles string wildcard index', function(): void {
        $context = new WriteContext('simple', 0, 'items.*.name', 'results.*.name', [], [], null, 'key123');

        expect($context->wildcardIndex)->toBe('key123');
    });

    it('handles high pair index', function(): void {
        $context = new WriteContext('simple', 999, 'src', 'tgt', [], []);

        expect($context->pairIndex)->toBe(999);
    });

    it('handles empty paths', function(): void {
        $context = new WriteContext('simple', 0, '', '', [], []);

        expect($context->srcPath)->toBe('');
        expect($context->tgtPath)->toBe('');
    });

    it('handles dot notation paths', function(): void {
        $context = new WriteContext('simple', 0, 'user.profile.name', 'person.fullName', [], [], 'person.fullName');

        expect($context->srcPath)->toBe('user.profile.name');
        expect($context->tgtPath)->toBe('person.fullName');
        expect($context->resolvedTargetPath)->toBe('person.fullName');
    });

    it('is used for beforeWrite and afterWrite hooks', function(): void {
        // This is a documentation test - WriteContext is for beforeWrite/afterWrite
        $context = new WriteContext('simple', 0, 'src', 'tgt', [], []);

        expect($context)->toBeInstanceOf(WriteContext::class);
        // Verify it has access to write-specific data
        expect($context->resolvedTargetPath)->toBeNull();
    });

    it('resolvedTargetPath differs from tgtPath for wildcards', function(): void {
        $context = new WriteContext(
            'simple',
            0,
            'items.*.name',
            'results.*.name',
            [],
            [],
            'results.0.name'
        );

        expect($context->tgtPath)->toBe('results.*.name');
        expect($context->resolvedTargetPath)->toBe('results.0.name');
        expect($context->tgtPath)->not->toBe($context->resolvedTargetPath);
    });
});


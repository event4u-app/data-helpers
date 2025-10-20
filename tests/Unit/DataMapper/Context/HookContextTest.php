<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\DataMapper\Context\EntryContext;
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Context\WriteContext;
use event4u\DataHelpers\Enums\Mode;

describe('HookContext Interface', function(): void {
    it('is implemented by AllContext', function(): void {
        $context = new AllContext('simple', [], [], []);

        expect($context)->toBeInstanceOf(HookContext::class);
    });

    it('is implemented by EntryContext', function(): void {
        $context = new EntryContext('structured', [], [], []);

        expect($context)->toBeInstanceOf(HookContext::class);
    });

    it('is implemented by PairContext', function(): void {
        $context = new PairContext('simple', 0, 'src', 'tgt', [], []);

        expect($context)->toBeInstanceOf(HookContext::class);
    });

    it('is implemented by WriteContext', function(): void {
        $context = new WriteContext('simple', 0, 'src', 'tgt', [], []);

        expect($context)->toBeInstanceOf(HookContext::class);
    });

    it('requires mode method', function(): void {
        $reflection = new ReflectionClass(HookContext::class);

        expect($reflection->hasMethod('mode'))->toBeTrue();
        $returnType = $reflection->getMethod('mode')->getReturnType();
        assert($returnType instanceof ReflectionNamedType);
        expect($returnType->getName())->toBe('string');
    });

    it('requires modeEnum method', function(): void {
        $reflection = new ReflectionClass(HookContext::class);

        expect($reflection->hasMethod('modeEnum'))->toBeTrue();
        $returnType = $reflection->getMethod('modeEnum')->getReturnType();
        assert($returnType instanceof ReflectionNamedType);
        expect($returnType->getName())->toBe(Mode::class);
    });

    it('requires srcPath method', function(): void {
        $reflection = new ReflectionClass(HookContext::class);

        expect($reflection->hasMethod('srcPath'))->toBeTrue();
    });

    it('requires tgtPath method', function(): void {
        $reflection = new ReflectionClass(HookContext::class);

        expect($reflection->hasMethod('tgtPath'))->toBeTrue();
    });

    it('requires extra method', function(): void {
        $reflection = new ReflectionClass(HookContext::class);

        expect($reflection->hasMethod('extra'))->toBeTrue();
    });

    it('is an interface', function(): void {
        $reflection = new ReflectionClass(HookContext::class);

        expect($reflection->isInterface())->toBeTrue();
    });

    it('has exactly 5 methods', function(): void {
        $reflection = new ReflectionClass(HookContext::class);

        expect($reflection->getMethods())->toHaveCount(5);
    });

    it('all implementations return consistent mode', function(): void {
        $contexts = [
            new AllContext('simple', [], [], []),
            new EntryContext('simple', [], [], []),
            new PairContext('simple', 0, 'src', 'tgt', [], []),
            new WriteContext('simple', 0, 'src', 'tgt', [], []),
        ];

        foreach ($contexts as $context) {
            expect($context->mode())->toBe('simple');
            expect($context->modeEnum())->toBe(Mode::Simple);
        }
    });

    it('all implementations return consistent extra array', function(): void {
        $contexts = [
            new AllContext('simple', [], [], []),
            new EntryContext('simple', [], [], []),
            new PairContext('simple', 0, 'src', 'tgt', [], []),
            new WriteContext('simple', 0, 'src', 'tgt', [], []),
        ];

        foreach ($contexts as $context) {
            expect($context->extra())->toBeArray();
        }
    });

    it('PairContext and WriteContext return srcPath', function(): void {
        $pair = new PairContext('simple', 0, 'source.path', 'target.path', [], []);
        $write = new WriteContext('simple', 0, 'source.path', 'target.path', [], []);

        expect($pair->srcPath())->toBe('source.path');
        expect($write->srcPath())->toBe('source.path');
    });

    it('PairContext and WriteContext return tgtPath', function(): void {
        $pair = new PairContext('simple', 0, 'source.path', 'target.path', [], []);
        $write = new WriteContext('simple', 0, 'source.path', 'target.path', [], []);

        expect($pair->tgtPath())->toBe('target.path');
        expect($write->tgtPath())->toBe('target.path');
    });

    it('AllContext and EntryContext return null for paths', function(): void {
        $all = new AllContext('simple', [], [], []);
        $entry = new EntryContext('simple', [], [], []);

        expect($all->srcPath())->toBeNull();
        expect($all->tgtPath())->toBeNull();
        expect($entry->srcPath())->toBeNull();
        expect($entry->tgtPath())->toBeNull();
    });
});


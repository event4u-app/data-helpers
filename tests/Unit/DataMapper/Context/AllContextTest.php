<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\Enums\Mode;

describe('AllContext', function(): void {
    it('creates context with all properties', function(): void {
        $mapping = ['target.name' => 'source.name', 'target.id' => 'source.id'];
        $source = ['data' => 'source'];
        $target = ['data' => 'target'];

        $context = new AllContext('simple', $mapping, $source, $target);

        expect($context->mode)->toBe('simple');
        expect($context->mapping)->toBe($mapping);
        expect($context->source)->toBe($source);
        expect($context->target)->toBe($target);
    });

    it('returns mode as string', function(): void {
        $context = new AllContext('simple', [], [], []);

        expect($context->mode())->toBe('simple');
    });

    it('returns mode as enum for simple', function(): void {
        $context = new AllContext('simple', [], [], []);

        expect($context->modeEnum())->toBe(Mode::Simple);
    });

    it('returns mode as enum for structured', function(): void {
        $context = new AllContext('structured', [], [], []);

        expect($context->modeEnum())->toBe(Mode::Structured);
    });

    it('returns null for srcPath', function(): void {
        $context = new AllContext('simple', [], [], []);

        expect($context->srcPath())->toBeNull();
    });

    it('returns null for tgtPath', function(): void {
        $context = new AllContext('simple', [], [], []);

        expect($context->tgtPath())->toBeNull();
    });

    it('returns empty array for extra', function(): void {
        $context = new AllContext('simple', [], [], []);

        expect($context->extra())->toBe([]);
    });

    it('implements HookContext interface', function(): void {
        $context = new AllContext('simple', [], [], []);

        expect($context)->toBeInstanceOf(HookContext::class);
    });

    it('handles complex mapping array', function(): void {
        $mapping = [
            'user.name' => 'person.fullName',
            'user.email' => 'person.contact.email',
            'user.address.*' => 'person.addresses.*',
            'user.tags' => '__static__:["admin", "user"]',
        ];

        $context = new AllContext('simple', $mapping, [], []);

        expect($context->mapping)->toBe($mapping);
        expect($context->mapping)->toHaveCount(4);
        expect($context->mapping['user.name'])->toBe('person.fullName');
    });

    it('handles empty mapping', function(): void {
        $context = new AllContext('simple', [], [], []);

        expect($context->mapping)->toBe([]);
        expect($context->mapping)->toBeEmpty();
    });

    it('handles structured mode mapping with numeric keys', function(): void {
        $mapping = [
            ['target' => 'name', 'source' => 'fullName'],
            ['target' => 'email', 'source' => 'contact.email'],
        ];

        $context = new AllContext('structured', $mapping, [], []);

        expect($context->mapping)->toBe($mapping);
        expect($context->mapping)->toHaveCount(2);
    });

    it('preserves source and target references', function(): void {
        $source = (object)['id' => 1];
        $target = (object)['id' => 2];

        $context = new AllContext('simple', [], $source, $target);

        expect($context->source)->toBe($source);
        expect($context->target)->toBe($target);
        expect($context->source->id)->toBe(1);
        expect($context->target->id)->toBe(2);
    });

    it('allows modification of mapping array', function(): void {
        $mapping = ['target.id' => 'source.id'];
        $context = new AllContext('simple', $mapping, [], []);

        $context->mapping['target.name'] = 'source.name';

        expect($context->mapping)->toHaveKey('target.name');
        expect($context->mapping['target.name'])->toBe('source.name');
        expect($context->mapping)->toHaveCount(2);
    });

    it('handles mixed source types', function(): void {
        $source = ['array' => 'data'];
        $target = (object)['object' => 'data'];

        $context = new AllContext('simple', [], $source, $target);

        expect($context->source)->toBeArray();
        expect($context->target)->toBeObject();
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(AllContext::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('is used for beforeAll and afterAll hooks', function(): void {
        // This is a documentation test - AllContext is specifically for beforeAll/afterAll
        $context = new AllContext('simple', [], [], []);

        expect($context)->toBeInstanceOf(AllContext::class);
        // Verify it has access to the full mapping
        expect($context->mapping)->toBeArray();
    });
});


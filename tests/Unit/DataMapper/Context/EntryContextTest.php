<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Context\EntryContext;
use event4u\DataHelpers\Enums\Mode;

describe('EntryContext', function(): void {
    it('creates context with all properties', function(): void {
        $entry = ['id' => 1, 'name' => 'Test'];
        $source = ['data' => 'source'];
        $target = ['data' => 'target'];

        $context = new EntryContext('structured', $entry, $source, $target);

        expect($context->mode)->toBe('structured');
        expect($context->entry)->toBe($entry);
        expect($context->source)->toBe($source);
        expect($context->target)->toBe($target);
    });

    it('returns mode as string', function(): void {
        $context = new EntryContext('structured', [], [], []);

        expect($context->mode())->toBe('structured');
    });

    it('returns mode as enum', function(): void {
        $context = new EntryContext('structured', [], [], []);

        expect($context->modeEnum())->toBe(Mode::Structured);
    });

    it('returns mode enum for simple mode', function(): void {
        $context = new EntryContext('simple', [], [], []);

        expect($context->modeEnum())->toBe(Mode::Simple);
    });

    it('returns null for srcPath', function(): void {
        $context = new EntryContext('structured', [], [], []);

        expect($context->srcPath())->toBeNull();
    });

    it('returns null for tgtPath', function(): void {
        $context = new EntryContext('structured', [], [], []);

        expect($context->tgtPath())->toBeNull();
    });

    it('returns empty array for extra', function(): void {
        $context = new EntryContext('structured', [], [], []);

        expect($context->extra())->toBe([]);
    });

    it('implements HookContext interface', function(): void {
        $context = new EntryContext('structured', [], [], []);

        expect($context)->toBeInstanceOf(HookContext::class);
    });

    it('handles complex entry data', function(): void {
        $entry = [
            'id' => 123,
            'name' => 'John Doe',
            'nested' => [
                'address' => '123 Main St',
                'city' => 'New York',
            ],
            'tags' => ['php', 'testing'],
        ];

        $context = new EntryContext('structured', $entry, [], []);

        expect($context->entry)->toBe($entry);
        expect($context->entry['id'])->toBe(123);
        expect($context->entry['nested']['city'])->toBe('New York');
        expect($context->entry['tags'])->toHaveCount(2);
    });

    it('handles empty entry', function(): void {
        $context = new EntryContext('structured', [], [], []);

        expect($context->entry)->toBe([]);
        expect($context->entry)->toBeEmpty();
    });

    it('preserves source and target references', function(): void {
        $source = (object)['id' => 1];
        $target = (object)['id' => 2];

        $context = new EntryContext('structured', [], $source, $target);

        expect($context->source)->toBe($source);
        expect($context->target)->toBe($target);
        expect($context->source->id)->toBe(1);
        expect($context->target->id)->toBe(2);
    });

    it('allows modification of entry array', function(): void {
        $entry = ['id' => 1];
        $context = new EntryContext('structured', $entry, [], []);

        $context->entry['name'] = 'Added';

        expect($context->entry)->toHaveKey('name');
        expect($context->entry['name'])->toBe('Added');
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(EntryContext::class);

        expect($reflection->isFinal())->toBeTrue();
    });
});


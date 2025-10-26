<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\FilterRegistry;
use Tests\Utils\Filters\AlternatingCase;

describe('Custom Filter Filters in Template Expressions', function(): void {
    beforeEach(function(): void {
        // Clear and register custom filter
        FilterRegistry::clear();
        FilterRegistry::register(AlternatingCase::class);
    });

    afterEach(function(): void {
        FilterRegistry::clear();
    });

    it('uses custom filter with single alias', function(): void {
        $template = [
            'name' => '{{ user.name | alternating }}',
        ];

        $sources = [
            'user' => ['name' => 'hello world'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->toBe([
            'name' => 'hElLo wOrLd',
        ]);
    });

    it('uses custom filter with alternative alias', function(): void {
        $template = [
            'name' => '{{ user.name | alt_case }}',
        ];

        $sources = [
            'user' => ['name' => 'test'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->toBe([
            'name' => 'tEsT',
        ]);
    });

    it('uses custom filter with third alias', function(): void {
        $template = [
            'name' => '{{ user.name | zigzag }}',
        ];

        $sources = [
            'user' => ['name' => 'abcdef'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->toBe([
            'name' => 'aBcDeF',
        ]);
    });

    it('chains custom filter with built-in filters', function(): void {
        $template = [
            'name' => '{{ user.name | upper | alternating }}',
        ];

        $sources = [
            'user' => ['name' => 'hello'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        // First: upper => 'HELLO'
        // Then: alternating => 'hElLo'
        expect($result)->toBe([
            'name' => 'hElLo',
        ]);
    });

    it('works with multiple fields using custom filter', function(): void {
        $template = [
            'firstName' => '{{ user.firstName | alternating }}',
            'lastName' => '{{ user.lastName | alternating }}',
            'email' => '{{ user.email | lower }}',
        ];

        $sources = [
            'user' => [
                'firstName' => 'john',
                'lastName' => 'doe',
                'email' => 'JOHN@EXAMPLE.COM',
            ],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->toBe([
            'firstName' => 'jOhN',
            'lastName' => 'dOe',
            'email' => 'john@example.com',
        ]);
    });

    it('handles empty strings', function(): void {
        $template = [
            'name' => '{{ user.name | alternating }}',
        ];

        $sources = [
            'user' => ['name' => ''],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->toBe([
            'name' => '',
        ]);
    });

    it('handles single character', function(): void {
        $template = [
            'name' => '{{ user.name | alternating }}',
        ];

        $sources = [
            'user' => ['name' => 'a'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->toBe([
            'name' => 'a',
        ]);
    });

    it('handles unicode characters', function(): void {
        $template = [
            'name' => '{{ user.name | alternating }}',
        ];

        $sources = [
            'user' => ['name' => 'äöü'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->toBe([
            'name' => 'äÖü',
        ]);
    });

    it('works in nested structures', function(): void {
        $template = [
            'profile' => [
                'name' => '{{ user.name | alternating }}',
                'bio' => '{{ user.bio | alternating }}',
            ],
        ];

        $sources = [
            'user' => [
                'name' => 'alice',
                'bio' => 'developer',
            ],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->toBe([
            'profile' => [
                'name' => 'aLiCe',
                'bio' => 'dEvElOpEr',
            ],
        ]);
    });
});

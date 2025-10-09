<?php

declare(strict_types=1);

namespace Tests\Unit\DataMapper\Pipeline;

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\SkipEmptyValues;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;

describe('DataMapper Pipeline', function(): void {
    it('works with TrimStrings transformer', function(): void {
        $source = [
            'user' => [
                'name' => '  Alice  ',
                'email' => '  alice@example.com  ',
            ],
        ];

        $mapping = [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
        ];

        $result = DataMapper::pipe([new TrimStrings()])
            ->map($source, [], $mapping);

        expect($result['name'])->toBe('Alice');
        expect($result['email'])->toBe('alice@example.com');
    });

    it('works with LowercaseEmails transformer', function(): void {
        $source = [
            'user' => [
                'name' => 'Alice',
                'email' => 'ALICE@EXAMPLE.COM',
            ],
        ];

        $mapping = [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
        ];

        $result = DataMapper::pipe([new LowercaseEmails()])
            ->map($source, [], $mapping);

        expect($result['name'])->toBe('Alice');
        expect($result['email'])->toBe('alice@example.com');
    });

    it('works with SkipEmptyValues transformer', function(): void {
        $source = [
            'user' => [
                'name' => 'Alice',
                'email' => '',
                'phone' => '123456',
            ],
        ];

        $mapping = [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
            'phone' => '{{ user.phone }}',
        ];

        $result = DataMapper::pipe([new SkipEmptyValues()])
            ->map($source, [], $mapping);

        expect($result['name'])->toBe('Alice');
        expect($result)->not->toHaveKey('email');
        expect($result['phone'])->toBe('123456');
    });

    it('chains multiple transformers', function(): void {
        $source = [
            'user' => [
                'name' => '  Alice  ',
                'email' => '  ALICE@EXAMPLE.COM  ',
            ],
        ];

        $mapping = [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
        ];

        $result = DataMapper::pipe([
            new TrimStrings(),
            new LowercaseEmails(),
        ])->map($source, [], $mapping);

        expect($result['name'])->toBe('Alice');
        expect($result['email'])->toBe('alice@example.com');
    });

    it('chains transformers with SkipEmptyValues', function(): void {
        $source = [
            'user' => [
                'name' => '  Alice  ',
                'email' => '  ',
                'phone' => '  123456  ',
            ],
        ];

        $mapping = [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
            'phone' => '{{ user.phone }}',
        ];

        $result = DataMapper::pipe([
            new TrimStrings(),
            new SkipEmptyValues(),
        ])->map($source, [], $mapping);

        expect($result['name'])->toBe('Alice');
        expect($result)->not->toHaveKey('email'); // Trimmed to empty, then skipped
        expect($result['phone'])->toBe('123456');
    });

    it('works with wildcard mappings', function(): void {
        $source = [
            'users' => [
                ['name' => 'Alice', 'age' => 30],
                ['name' => 'Bob', 'age' => 25],
            ],
        ];

        $mapping = [
            'names.*' => '{{ users.*.name }}',
            'ages.*' => '{{ users.*.age }}',
        ];

        $result = DataMapper::pipe([
            new TrimStrings(),
        ])->map($source, [], $mapping);

        expect($result['names'])->toBe(['Alice', 'Bob']);
        expect($result['ages'])->toBe([30, 25]);
    });

    it('allows additional hooks via withHooks', function(): void {
        $source = [
            'user' => [
                'name' => '  alice  ',
            ],
        ];

        $mapping = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::pipe([new TrimStrings()])
            ->withHooks([
                'postTransform' => fn($value) => is_string($value) ? strtoupper($value) : $value,
            ])
            ->map($source, [], $mapping);

        expect($result['name'])->toBe('ALICE'); // Trimmed then uppercased
    });
});

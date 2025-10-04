<?php

declare(strict_types=1);

namespace Tests\Unit\DataMapper\Template;

use event4u\DataHelpers\DataMapper;

describe('Template Expressions', function(): void {
    it('evaluates simple expression', function(): void {
        $template = [
            'fullname' => '{{ user.name }}',
        ];

        $sources = [
            'user' => ['name' => 'Alice'],
        ];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result['fullname'])->toBe('Alice');
    });

    it('evaluates expression with default value', function(): void {
        $template = [
            'fullname' => "{{ user.name ?? 'Unknown' }}",
        ];

        $sources = [
            'user' => ['email' => 'alice@example.com'],
        ];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result['fullname'])->toBe('Unknown');
    });

    it('evaluates expression with filter', function(): void {
        $template = [
            'email' => '{{ user.email | lower }}',
        ];

        $sources = [
            'user' => ['email' => 'ALICE@EXAMPLE.COM'],
        ];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result['email'])->toBe('alice@example.com');
    });

    it('evaluates expression with multiple filters', function(): void {
        $template = [
            'name' => '{{ user.name | lower | ucfirst }}',
        ];

        $sources = [
            'user' => ['name' => 'ALICE'],
        ];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result['name'])->toBe('Alice');
    });

    it('evaluates alias reference', function(): void {
        $template = [
            'fullname' => '{{ user.name }}',
            'copy' => '@fullname',
        ];

        $sources = [
            'user' => ['name' => 'Alice'],
        ];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result['fullname'])->toBe('Alice');
        expect($result['copy'])->toBe('Alice');
    });

    it('combines expressions with regular references', function(): void {
        $template = [
            'name' => '{{ user.name | upper }}',
            'email' => 'user.email',
            'city' => '{{ address.city ?? "Unknown" }}',
        ];

        $sources = [
            'user' => ['name' => 'alice', 'email' => 'alice@example.com'],
            'address' => [],
        ];

        $result = DataMapper::mapFromTemplate($template, $sources);

        expect($result['name'])->toBe('ALICE');
        expect($result['email'])->toBe('alice@example.com');
        expect($result['city'])->toBe('Unknown');
    });
});

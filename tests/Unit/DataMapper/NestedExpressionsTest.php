<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('Nested Expressions', function(): void {
    it('parses simple parentheses', function(): void {
        $data = [
            'user' => [
                'name' => null,
                'surname' => 'Doe',
            ],
        ];

        $result = DataMapper::source($data)
            ->template([
                'fullname' => '{{ user.name ?? (user.surname) }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'fullname' => 'Doe',
        ]);
    });

    it('parses nested null coalescing operators', function(): void {
        $data = [
            'user' => [
                'name' => null,
                'surname' => null,
            ],
        ];

        $result = DataMapper::source($data)
            ->template([
                'fullname' => '{{ user.name ?? (user.surname ?? "UNKNOWN") }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'fullname' => 'UNKNOWN',
        ]);
    });

    it('parses nested operators with filters inside parentheses', function(): void {
        $data = [
            'user' => [
                'name' => null,
                'surname' => 'doe',
            ],
        ];

        $result = DataMapper::source($data)
            ->template([
                'fullname' => '{{ user.name ?? (user.surname | upper) }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'fullname' => 'DOE',
        ]);
    });

    it('parses complex nested expression', function(): void {
        $data = [
            'user' => [
                'name' => null,
                'surname' => null,
            ],
        ];

        $result = DataMapper::source($data)
            ->template([
                'fullname' => '{{ user.name ?? (user.surname ?? "UNKNOWN" | lower) | upper }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'fullname' => 'UNKNOWN',
        ]);
    });

    it('parses parentheses with elvis operator', function(): void {
        $data = [
            'user' => [
                'name' => '',
                'surname' => 'Doe',
            ],
        ];

        $result = DataMapper::source($data)
            ->template([
                'fullname' => '{{ user.name ?: (user.surname) }}',
            ])
            ->skipNull(false)
            ->map()
            ->getTarget();

        expect($result)->toBe([
            'fullname' => 'Doe',
        ]);
    });
});

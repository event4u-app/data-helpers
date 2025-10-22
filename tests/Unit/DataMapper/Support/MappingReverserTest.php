<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Support\MappingReverser;

describe('MappingReverser', function(): void {
    test('it reverses simple mapping', function(): void {
        $mapping = [
            'full_name' => '{{ firstName }}',
            'contact_email' => '{{ email }}',
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        expect($reversed)->toBe([
            'firstName' => '{{ full_name }}',
            'email' => '{{ contact_email }}',
        ]);
    });

    test('it reverses nested mapping', function(): void {
        $mapping = [
            'profile' => [
                'fullName' => '{{ user.name }}',
                'age' => '{{ user.age }}',
            ],
            'location' => [
                'city' => '{{ address.city }}',
            ],
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        expect($reversed)->toBe([
            'user.name' => '{{ profile.fullName }}',
            'user.age' => '{{ profile.age }}',
            'address.city' => '{{ location.city }}',
        ]);
    });

    test('it reverses mapping without template syntax', function(): void {
        $mapping = [
            'full_name' => 'firstName',
            'email' => 'contact.email',
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        expect($reversed)->toBe([
            'firstName' => '{{ full_name }}',
            'contact.email' => '{{ email }}',
        ]);
    });

    test('it reverses mapping with mixed syntax', function(): void {
        $mapping = [
            'full_name' => '{{ firstName }}',
            'email' => 'contact.email',
            'age' => '{{user.age}}',
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        expect($reversed)->toBe([
            'firstName' => '{{ full_name }}',
            'contact.email' => '{{ email }}',
            'user.age' => '{{ age }}',
        ]);
    });

    test('it skips non-string keys when reversing mapping', function(): void {
        $mapping = [
            'full_name' => '{{ firstName }}',
            0 => 'should be skipped',
            'email' => '{{ contact.email }}',
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        expect($reversed)->toBe([
            'firstName' => '{{ full_name }}',
            'contact.email' => '{{ email }}',
        ]);
    });

    test('it skips callbacks when reversing mapping', function(): void {
        $mapping = [
            'full_name' => '{{ firstName }}',
            'computed' => fn($source): int|float => $source['value'] * 2,
            'email' => '{{ contact.email }}',
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        expect($reversed)->toBe([
            'firstName' => '{{ full_name }}',
            'contact.email' => '{{ email }}',
        ]);
    });

    test('it reverses template', function(): void {
        $template = [
            'profile' => [
                'name' => 'user.name',
                'email' => 'user.email',
            ],
            'company' => [
                'name' => 'organization.name',
            ],
        ];

        $reversed = MappingReverser::reverseTemplate($template);

        $expected = [
            'user' => [
                'name' => 'profile.name',
                'email' => 'profile.email',
            ],
            'organization' => [
                'name' => 'company.name',
            ],
        ];

        expect($reversed)->toBe($expected);
    });

    test('it reverses template with deep nesting', function(): void {
        $template = [
            'data' => [
                'user' => [
                    'profile' => [
                        'name' => 'source.person.fullName',
                    ],
                ],
            ],
        ];

        $reversed = MappingReverser::reverseTemplate($template);

        $expected = [
            'source' => [
                'person' => [
                    'fullName' => 'data.user.profile.name',
                ],
            ],
        ];

        expect($reversed)->toBe($expected);
    });

    test('it reverses template with template syntax', function(): void {
        $template = [
            'profile' => [
                'name' => '{{ user.name }}',
                'email' => 'user.email',
            ],
        ];

        $reversed = MappingReverser::reverseTemplate($template);

        $expected = [
            'user' => [
                'name' => 'profile.name',
                'email' => 'profile.email',
            ],
        ];

        expect($reversed)->toBe($expected);
    });

    test('it reverses empty mapping', function(): void {
        $mapping = [];

        $reversed = MappingReverser::reverseMapping($mapping);

        expect($reversed)->toBe([]);
    });

    test('it reverses empty template', function(): void {
        $template = [];

        $reversed = MappingReverser::reverseTemplate($template);

        expect($reversed)->toBe([]);
    });
});

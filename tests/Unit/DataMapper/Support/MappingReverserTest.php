<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Tests\Unit\DataMapper\Support;

use event4u\DataHelpers\DataMapper\Support\MappingReverser;
use PHPUnit\Framework\TestCase;

class MappingReverserTest extends TestCase
{
    public function test_reverse_simple_mapping(): void
    {
        $mapping = [
            'full_name' => '{{ firstName }}',
            'contact_email' => '{{ email }}',
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        $this->assertSame([
            'firstName' => '{{ full_name }}',
            'email' => '{{ contact_email }}',
        ], $reversed);
    }

    public function test_reverse_nested_mapping(): void
    {
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

        $this->assertSame([
            'user.name' => '{{ profile.fullName }}',
            'user.age' => '{{ profile.age }}',
            'address.city' => '{{ location.city }}',
        ], $reversed);
    }

    public function test_reverse_mapping_without_template_syntax(): void
    {
        $mapping = [
            'full_name' => 'firstName',
            'email' => 'contact.email',
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        $this->assertSame([
            'firstName' => '{{ full_name }}',
            'contact.email' => '{{ email }}',
        ], $reversed);
    }

    public function test_reverse_mapping_with_mixed_syntax(): void
    {
        $mapping = [
            'full_name' => '{{ firstName }}',
            'email' => 'contact.email',
            'age' => '{{user.age}}',
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        $this->assertSame([
            'firstName' => '{{ full_name }}',
            'contact.email' => '{{ email }}',
            'user.age' => '{{ age }}',
        ], $reversed);
    }

    public function test_reverse_mapping_skips_non_string_keys(): void
    {
        $mapping = [
            'full_name' => '{{ firstName }}',
            0 => 'should be skipped',
            'email' => '{{ contact.email }}',
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        $this->assertSame([
            'firstName' => '{{ full_name }}',
            'contact.email' => '{{ email }}',
        ], $reversed);
    }

    public function test_reverse_mapping_skips_callbacks(): void
    {
        $mapping = [
            'full_name' => '{{ firstName }}',
            'computed' => fn($source): int|float => $source['value'] * 2,
            'email' => '{{ contact.email }}',
        ];

        $reversed = MappingReverser::reverseMapping($mapping);

        $this->assertSame([
            'firstName' => '{{ full_name }}',
            'contact.email' => '{{ email }}',
        ], $reversed);
    }

    public function test_reverse_template(): void
    {
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

        $this->assertSame($expected, $reversed);
    }

    public function test_reverse_template_with_deep_nesting(): void
    {
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

        $this->assertSame($expected, $reversed);
    }

    public function test_reverse_template_with_template_syntax(): void
    {
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

        $this->assertSame($expected, $reversed);
    }

    public function test_reverse_empty_mapping(): void
    {
        $mapping = [];

        $reversed = MappingReverser::reverseMapping($mapping);

        $this->assertSame([], $reversed);
    }

    public function test_reverse_empty_template(): void
    {
        $template = [];

        $reversed = MappingReverser::reverseTemplate($template);

        $this->assertSame([], $reversed);
    }
}


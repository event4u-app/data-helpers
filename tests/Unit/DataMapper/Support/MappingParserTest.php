<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Support\MappingParser;

describe('MappingParser', function(): void {
    describe('parseEntry', function(): void {
        it('parses static value', function(): void {
            $result = MappingParser::parseEntry(['__static__' => 'John Doe']);

            expect($result)->toBe([
                'isStatic' => true,
                'sourcePath' => 'John Doe',
                'filters' => [],
                'defaultValue' => null,
                'hasFilters' => false,
            ]);
        });

        it('parses simple dynamic path', function(): void {
            $result = MappingParser::parseEntry('user.name');

            expect($result)->toBe([
                'isStatic' => false,
                'sourcePath' => 'user.name',
                'filters' => [],
                'defaultValue' => null,
                'hasFilters' => false,
            ]);
        });

        it('parses dynamic path with single filter', function(): void {
            $result = MappingParser::parseEntry('user.name | upper');

            expect($result)->toBe([
                'isStatic' => false,
                'sourcePath' => 'user.name',
                'filters' => ['upper'],
                'defaultValue' => null,
                'hasFilters' => true,
            ]);
        });

        it('parses dynamic path with multiple filters', function(): void {
            $result = MappingParser::parseEntry('user.name | trim | upper');

            expect($result)->toBe([
                'isStatic' => false,
                'sourcePath' => 'user.name',
                'filters' => ['trim', 'upper'],
                'defaultValue' => null,
                'hasFilters' => true,
            ]);
        });

        it('parses dynamic path with default value', function(): void {
            $result = MappingParser::parseEntry('user.name ?? "Unknown"');

            expect($result)->toBe([
                'isStatic' => false,
                'sourcePath' => 'user.name',
                'filters' => [],
                'defaultValue' => 'Unknown', // Quotes are removed by ExpressionParser
                'hasFilters' => false,
            ]);
        });

        it('parses dynamic path with filter and default value', function(): void {
            // Note: ?? after filter is treated as part of the filter, not as default operator
            // This is current behavior - to use default with filters, put ?? before filters
            $result = MappingParser::parseEntry('user.name ?? "UNKNOWN" | upper');

            expect($result)->toBe([
                'isStatic' => false,
                'sourcePath' => 'user.name',
                'filters' => ['upper'],
                'defaultValue' => 'UNKNOWN', // Quotes are removed when ?? is before filter
                'hasFilters' => true,
            ]);
        });

        it('parses wildcard path', function(): void {
            $result = MappingParser::parseEntry('users.*.name');

            expect($result)->toBe([
                'isStatic' => false,
                'sourcePath' => 'users.*.name',
                'filters' => [],
                'defaultValue' => null,
                'hasFilters' => false,
            ]);
        });

        it('parses wildcard path with filter', function(): void {
            $result = MappingParser::parseEntry('users.*.name | upper');

            expect($result)->toBe([
                'isStatic' => false,
                'sourcePath' => 'users.*.name',
                'filters' => ['upper'],
                'defaultValue' => null,
                'hasFilters' => true,
            ]);
        });
    });

    describe('parseMapping', function(): void {
        it('parses complete mapping', function(): void {
            $mapping = [
                'name' => 'user.name | upper',
                'email' => 'user.email | lower',
                'role' => 'user.role ?? "user"',
                'company' => ['__static__' => 'Acme Inc'],
            ];

            $result = MappingParser::parseMapping($mapping);

            expect($result)->toBe([
                'name' => [
                    'isStatic' => false,
                    'sourcePath' => 'user.name',
                    'filters' => ['upper'],
                    'defaultValue' => null,
                    'hasFilters' => true,
                ],
                'email' => [
                    'isStatic' => false,
                    'sourcePath' => 'user.email',
                    'filters' => ['lower'],
                    'defaultValue' => null,
                    'hasFilters' => true,
                ],
                'role' => [
                    'isStatic' => false,
                    'sourcePath' => 'user.role',
                    'filters' => [],
                    'defaultValue' => 'user', // Quotes are removed by ExpressionParser
                    'hasFilters' => false,
                ],
                'company' => [
                    'isStatic' => true,
                    'sourcePath' => 'Acme Inc',
                    'filters' => [],
                    'defaultValue' => null,
                    'hasFilters' => false,
                ],
            ]);
        });

        it('parses empty mapping', function(): void {
            $result = MappingParser::parseMapping([]);

            expect($result)->toBe([]);
        });
    });
});

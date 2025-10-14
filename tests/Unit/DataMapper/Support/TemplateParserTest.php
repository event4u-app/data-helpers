<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Support\TemplateParser;

describe('TemplateParser', function(): void {
    describe('isTemplate()', function(): void {
        it('returns true for template expressions', function(): void {
            expect(TemplateParser::isTemplate('{{ user.name }}'))->toBeTrue();
            expect(TemplateParser::isTemplate('{{user.name}}'))->toBeTrue();
            expect(TemplateParser::isTemplate('{{  user.name  }}'))->toBeTrue();
        });

        it('returns false for non-template strings', function(): void {
            expect(TemplateParser::isTemplate('user.name'))->toBeFalse();
            expect(TemplateParser::isTemplate('John Doe'))->toBeFalse();
            expect(TemplateParser::isTemplate('{{ incomplete'))->toBeFalse();
            expect(TemplateParser::isTemplate('incomplete }}'))->toBeFalse();
        });
    });

    describe('extractPath()', function(): void {
        it('extracts path from template expressions', function(): void {
            expect(TemplateParser::extractPath('{{ user.name }}'))->toBe('user.name');
            expect(TemplateParser::extractPath('{{user.name}}'))->toBe('user.name');
            expect(TemplateParser::extractPath('{{  user.name  }}'))->toBe('user.name');
            expect(TemplateParser::extractPath('{{ items.* }}'))->toBe('items.*');
        });

        it('returns string as-is if not a template', function(): void {
            expect(TemplateParser::extractPath('user.name'))->toBe('user.name');
            expect(TemplateParser::extractPath('John Doe'))->toBe('John Doe');
        });
    });

    describe('parseMapping()', function(): void {
        it('parses template expressions to paths', function(): void {
            $mapping = [
                'name' => '{{ user.name }}',
                'email' => '{{ user.email }}',
            ];

            $result = TemplateParser::parseMapping($mapping);

            expect($result)->toBe([
                'name' => 'user.name',
                'email' => 'user.email',
            ]);
        });

        it('marks static values with marker', function(): void {
            $mapping = [
                'name' => '{{ user.name }}',
                'status' => 'active',
                'count' => 42,
            ];

            $result = TemplateParser::parseMapping($mapping);

            expect($result)->toBe([
                'name' => 'user.name',
                'status' => ['__static__' => 'active'],
                'count' => ['__static__' => 42],
            ]);
        });

        it('supports custom static marker', function(): void {
            $mapping = [
                'name' => '{{ user.name }}',
                'status' => 'active',
            ];

            $result = TemplateParser::parseMapping($mapping, '__literal__');

            expect($result)->toBe([
                'name' => 'user.name',
                'status' => ['__literal__' => 'active'],
            ]);
        });
    });

    describe('wrap()', function(): void {
        it('wraps path in template syntax', function(): void {
            expect(TemplateParser::wrap('user.name'))->toBe('{{ user.name }}');
            expect(TemplateParser::wrap('items.*'))->toBe('{{ items.* }}');
        });
    });

    describe('isStaticValue()', function(): void {
        it('returns true for static marker arrays', function(): void {
            expect(TemplateParser::isStaticValue(['__static__' => 'value']))->toBeTrue();
        });

        it('returns false for non-static values', function(): void {
            expect(TemplateParser::isStaticValue('value'))->toBeFalse();
            expect(TemplateParser::isStaticValue(['key' => 'value']))->toBeFalse();
            expect(TemplateParser::isStaticValue([]))->toBeFalse();
        });

        it('supports custom static marker', function(): void {
            expect(TemplateParser::isStaticValue(['__literal__' => 'value'], '__literal__'))->toBeTrue();
            expect(TemplateParser::isStaticValue(['__static__' => 'value'], '__literal__'))->toBeFalse();
        });
    });

    describe('extractStaticValue()', function(): void {
        it('extracts value from static marker array', function(): void {
            expect(TemplateParser::extractStaticValue(['__static__' => 'active']))->toBe('active');
            expect(TemplateParser::extractStaticValue(['__static__' => 42]))->toBe(42);
        });

        it('supports custom static marker', function(): void {
            expect(TemplateParser::extractStaticValue(['__literal__' => 'value'], '__literal__'))->toBe('value');
        });
    });

    describe('normalizePath()', function(): void {
        it('extracts path from templates', function(): void {
            expect(TemplateParser::normalizePath('{{ user.name }}'))->toBe('user.name');
        });

        it('returns plain paths as-is', function(): void {
            expect(TemplateParser::normalizePath('user.name'))->toBe('user.name');
        });
    });
});


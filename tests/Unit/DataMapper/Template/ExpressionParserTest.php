<?php

declare(strict_types=1);

namespace Tests\Unit\DataMapper\Template;

use event4u\DataHelpers\DataMapper\Template\ExpressionParser;

describe('ExpressionParser', function(): void {
    it('detects template expressions', function(): void {
        expect(ExpressionParser::hasExpression('{{ user.name }}'))->toBeTrue();
        expect(ExpressionParser::hasExpression('@profile.fullname'))->toBeTrue();
        expect(ExpressionParser::hasExpression('user.name'))->toBeFalse();
    });

    it('parses simple expression', function(): void {
        $result = ExpressionParser::parse('{{ user.name }}');

        expect($result)->not->toBeNull();
        assert(is_array($result));
        expect($result['type'])->toBe('expression');
        expect($result['path'])->toBe('user.name');
        expect($result['default'])->toBeNull();
        expect($result['filters'])->toBe([]);
    });

    it('parses expression with default value', function(): void {
        $result = ExpressionParser::parse("{{ user.name ?? 'Unknown' }}");

        expect($result)->not->toBeNull();
        assert(is_array($result));
        expect($result['type'])->toBe('expression');
        expect($result['path'])->toBe('user.name');
        expect($result['default'])->toBe('Unknown');
    });

    it('parses expression with filter', function(): void {
        $result = ExpressionParser::parse('{{ user.email | lower }}');

        expect($result)->not->toBeNull();
        assert(is_array($result));
        expect($result['type'])->toBe('expression');
        expect($result['path'])->toBe('user.email');
        expect($result['filters'])->toBe(['lower']);
    });

    it('parses expression with multiple filters', function(): void {
        $result = ExpressionParser::parse('{{ user.email | lower | trim }}');

        expect($result)->not->toBeNull();
        assert(is_array($result));
        expect($result['filters'])->toBe(['lower', 'trim']);
    });

    it('parses alias reference', function(): void {
        $result = ExpressionParser::parse('@profile.fullname');

        expect($result)->not->toBeNull();
        assert(is_array($result));
        expect($result['type'])->toBe('alias');
        expect($result['path'])->toBe('profile.fullname');
    });
});

<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\LikeOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;

describe('LikeOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new LikeOperator();

        expect($operator->getName())->toBe('LIKE');
    });

    it('matches exact string', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'test'],
            ['name' => 'other'],
        ];
        $config = ['name' => ['pattern' => 'test']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['name'])->toBe('test');
    });

    it('does not match different string', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'other'],
        ];
        $config = ['name' => ['pattern' => 'test']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('matches with % wildcard at end', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'testing'],
            ['name' => 'test'],
            ['name' => 'other'],
        ];
        $config = ['name' => ['pattern' => 'test%']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'name'))->toBe(['testing', 'test']);
    });

    it('matches with % wildcard at start', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'mytest'],
            ['name' => 'test'],
            ['name' => 'other'],
        ];
        $config = ['name' => ['pattern' => '%test']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'name'))->toBe(['mytest', 'test']);
    });

    it('matches with % wildcard in middle', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'test123ing'],
            ['name' => 'testing'],
            ['name' => 'other'],
        ];
        $config = ['name' => ['pattern' => 'test%ing']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'name'))->toBe(['test123ing', 'testing']);
    });

    it('matches with % wildcard on both sides', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'mytest123'],
            ['name' => 'test'],
            ['name' => 'other'],
        ];
        $config = ['name' => ['pattern' => '%test%']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'name'))->toBe(['mytest123', 'test']);
    });

    it('matches with _ wildcard for single character', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'test1'],
            ['name' => 'test12'],
            ['name' => 'test'],
        ];
        $config = ['name' => ['pattern' => 'test_']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['name'])->toBe('test1');
    });

    it('does not match _ wildcard with multiple characters', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'test12'],
        ];
        $config = ['name' => ['pattern' => 'test_']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('matches with multiple _ wildcards', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'test12'],
            ['name' => 'test1'],
            ['name' => 'test123'],
        ];
        $config = ['name' => ['pattern' => 'test__']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['name'])->toBe('test12');
    });

    it('matches with mixed % and _ wildcards', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'test1ing'],
            ['name' => 'test12ing'],
            ['name' => 'testing'],
        ];
        $config = ['name' => ['pattern' => 'test_%ing']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'name'))->toBe(['test1ing', 'test12ing']);
    });

    it('is case insensitive', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'TEST'],
            ['name' => 'Test'],
            ['name' => 'test'],
            ['name' => 'other'],
        ];
        $config = ['name' => ['pattern' => 'test']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(3);
        expect(array_column($result, 'name'))->toBe(['TEST', 'Test', 'test']);
    });

    it('excludes non-string values', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 123],
            ['name' => 'test'],
        ];
        $config = ['name' => ['pattern' => 'test']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['name'])->toBe('test');
    });

    it('excludes all items when pattern is non-string', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'test'],
        ];
        $config = ['name' => ['pattern' => 123]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('handles empty string', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => ''],
            ['name' => 'test'],
        ];
        $config = ['name' => ['pattern' => '']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['name'])->toBe('');
    });

    it('handles empty pattern with wildcard', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'test'],
            ['name' => ''],
        ];
        $config = ['name' => ['pattern' => '%']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('handles special regex characters in pattern', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'test.file'],
            ['name' => 'testXfile'],
        ];
        $config = ['name' => ['pattern' => 'test.file']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['name'])->toBe('test.file');
    });

    it('handles brackets in pattern', function(): void {
        $operator = new LikeOperator();
        $items = [
            ['name' => 'test[1]'],
            ['name' => 'test1'],
        ];
        $config = ['name' => ['pattern' => 'test[1]']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['name'])->toBe('test[1]');
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(LikeOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new LikeOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});

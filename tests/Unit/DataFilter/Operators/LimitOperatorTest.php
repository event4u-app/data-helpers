<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\LimitOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;

describe('LimitOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new LimitOperator();

        expect($operator->getName())->toBe('LIMIT');
    });

    it('limits items to specified count', function(): void {
        $operator = new LimitOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
            ['id' => 5],
        ];
        $config = 3;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(3);
        expect(array_column($result, 'id'))->toBe([1, 2, 3]);
    });

    it('returns all items when limit is greater than count', function(): void {
        $operator = new LimitOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
        ];
        $config = 10;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('returns empty array when limit is zero', function(): void {
        $operator = new LimitOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
        ];
        $config = 0;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('returns single item when limit is one', function(): void {
        $operator = new LimitOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];
        $config = 1;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['id'])->toBe(1);
    });

    it('handles empty array', function(): void {
        $operator = new LimitOperator();
        $items = [];
        $config = 5;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('preserves array keys', function(): void {
        $operator = new LimitOperator();
        $items = [
            10 => ['id' => 1],
            20 => ['id' => 2],
            30 => ['id' => 3],
        ];
        $config = 2;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_keys($result))->toBe([10, 20]);
    });

    it('returns original items when config is not integer', function(): void {
        $operator = new LimitOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
        ];
        $config = 'invalid';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('returns original items when config is negative', function(): void {
        $operator = new LimitOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
        ];
        $config = -5;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('handles large limit values', function(): void {
        $operator = new LimitOperator();
        $items = array_map(fn($i): array => ['id' => $i], range(1, 100));
        $config = 50;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(50);
        expect($result[0]['id'])->toBe(1);
        expect($result[49]['id'])->toBe(50);
    });

    it('works with associative arrays', function(): void {
        $operator = new LimitOperator();
        $items = [
            'a' => ['id' => 1],
            'b' => ['id' => 2],
            'c' => ['id' => 3],
        ];
        $config = 2;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_keys($result))->toBe(['a', 'b']);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(LimitOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new LimitOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});


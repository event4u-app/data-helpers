<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\OffsetOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;

describe('OffsetOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new OffsetOperator();

        expect($operator->getName())->toBe('OFFSET');
    });

    it('skips specified number of items', function(): void {
        $operator = new OffsetOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
            ['id' => 5],
        ];
        $config = 2;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(3);
        expect(array_column($result, 'id'))->toBe([3, 4, 5]);
    });

    it('returns empty array when offset is greater than count', function(): void {
        $operator = new OffsetOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
        ];
        $config = 10;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('returns original items when offset is zero', function(): void {
        $operator = new OffsetOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
        ];
        $config = 0;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('skips first item when offset is one', function(): void {
        $operator = new OffsetOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];
        $config = 1;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'id'))->toBe([2, 3]);
    });

    it('returns single item when offset is count minus one', function(): void {
        $operator = new OffsetOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];
        $config = 2;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[2]['id'])->toBe(3);
    });

    it('handles empty array', function(): void {
        $operator = new OffsetOperator();
        $items = [];
        $config = 5;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('preserves array keys', function(): void {
        $operator = new OffsetOperator();
        $items = [
            10 => ['id' => 1],
            20 => ['id' => 2],
            30 => ['id' => 3],
        ];
        $config = 1;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_keys($result))->toBe([20, 30]);
    });

    it('returns original items when config is not integer', function(): void {
        $operator = new OffsetOperator();
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
        $operator = new OffsetOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
        ];
        $config = -5;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('handles large offset values', function(): void {
        $operator = new OffsetOperator();
        $items = array_map(fn($i): array => ['id' => $i], range(1, 100));
        $config = 50;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(50);
        expect($result[50]['id'])->toBe(51);
        expect($result[99]['id'])->toBe(100);
    });

    it('works with associative arrays', function(): void {
        $operator = new OffsetOperator();
        $items = [
            'a' => ['id' => 1],
            'b' => ['id' => 2],
            'c' => ['id' => 3],
        ];
        $config = 1;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_keys($result))->toBe(['b', 'c']);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(OffsetOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new OffsetOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});


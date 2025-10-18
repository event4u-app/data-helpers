<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\OrderByOperator;

describe('OrderByOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new OrderByOperator();

        expect($operator->getName())->toBe('ORDER BY');
    });

    it('has ORDER alias', function(): void {
        $operator = new OrderByOperator();

        expect($operator->getAliases())->toBe(['ORDER']);
    });

    it('sorts items in ascending order', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['id' => 3],
            ['id' => 1],
            ['id' => 2],
        ];
        $config = ['id' => 'ASC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect(array_column($result, 'id'))->toBe([1, 2, 3]);
    });

    it('sorts items in descending order', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['id' => 1],
            ['id' => 3],
            ['id' => 2],
        ];
        $config = ['id' => 'DESC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect(array_column($result, 'id'))->toBe([3, 2, 1]);
    });

    it('sorts strings alphabetically', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['name' => 'Charlie'],
            ['name' => 'Alice'],
            ['name' => 'Bob'],
        ];
        $config = ['name' => 'ASC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect(array_column($result, 'name'))->toBe(['Alice', 'Bob', 'Charlie']);
    });

    it('handles null values in ascending order', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['value' => 2],
            ['value' => null],
            ['value' => 1],
        ];
        $config = ['value' => 'ASC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        $values = array_column($result, 'value');
        expect($values[0])->toBeNull();
        expect($values[1])->toBe(1);
        expect($values[2])->toBe(2);
    });

    it('handles null values in descending order', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['value' => 2],
            ['value' => null],
            ['value' => 1],
        ];
        $config = ['value' => 'DESC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        $values = array_column($result, 'value');
        expect($values[0])->toBe(2);
        expect($values[1])->toBe(1);
        expect($values[2])->toBeNull();
    });

    it('handles float values', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['price' => 2.5],
            ['price' => 1.5],
            ['price' => 3.5],
        ];
        $config = ['price' => 'ASC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect(array_column($result, 'price'))->toBe([1.5, 2.5, 3.5]);
    });

    it('handles string numeric values', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['value' => '20'],
            ['value' => '3'],
            ['value' => '100'],
        ];
        $config = ['value' => 'ASC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect(array_column($result, 'value'))->toBe(['3', '20', '100']);
    });

    it('sorts by multiple fields', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['category' => 'B', 'price' => 20],
            ['category' => 'A', 'price' => 30],
            ['category' => 'B', 'price' => 10],
            ['category' => 'A', 'price' => 20],
        ];
        $config = ['category' => 'ASC', 'price' => 'ASC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        $values = array_values($result);
        expect($values[0])->toBe(['category' => 'A', 'price' => 20]);
        expect($values[1])->toBe(['category' => 'A', 'price' => 30]);
        expect($values[2])->toBe(['category' => 'B', 'price' => 10]);
        expect($values[3])->toBe(['category' => 'B', 'price' => 20]);
    });

    it('sorts by multiple fields with mixed directions', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['category' => 'B', 'price' => 20],
            ['category' => 'A', 'price' => 30],
            ['category' => 'B', 'price' => 10],
            ['category' => 'A', 'price' => 20],
        ];
        $config = ['category' => 'ASC', 'price' => 'DESC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        $values = array_values($result);
        expect($values[0])->toBe(['category' => 'A', 'price' => 30]);
        expect($values[1])->toBe(['category' => 'A', 'price' => 20]);
        expect($values[2])->toBe(['category' => 'B', 'price' => 20]);
        expect($values[3])->toBe(['category' => 'B', 'price' => 10]);
    });

    it('handles empty array', function(): void {
        $operator = new OrderByOperator();
        $items = [];
        $config = ['id' => 'ASC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('handles single item', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['id' => 1],
        ];
        $config = ['id' => 'ASC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
    });

    it('preserves array keys', function(): void {
        $operator = new OrderByOperator();
        $items = [
            10 => ['id' => 3],
            20 => ['id' => 1],
            30 => ['id' => 2],
        ];
        $config = ['id' => 'ASC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect(array_keys($result))->toBe([20, 30, 10]);
    });

    it('returns original items when config is not array', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['id' => 3],
            ['id' => 1],
        ];
        $config = 'invalid';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect(array_column($result, 'id'))->toBe([3, 1]);
    });

    it('handles case-insensitive direction', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['id' => 3],
            ['id' => 1],
            ['id' => 2],
        ];
        $config = ['id' => 'asc'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect(array_column($result, 'id'))->toBe([1, 2, 3]);
    });

    it('handles nested field paths', function(): void {
        $operator = new OrderByOperator();
        $items = [
            ['user' => ['age' => 30]],
            ['user' => ['age' => 20]],
            ['user' => ['age' => 25]],
        ];
        $config = ['user.age' => 'ASC'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        $ages = array_map(fn(array $item) => $item['user']['age'], array_values($result));
        expect($ages)->toBe([20, 25, 30]);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(OrderByOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new OrderByOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});


<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\DistinctOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;

describe('DistinctOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new DistinctOperator();

        expect($operator->getName())->toBe('DISTINCT');
    });

    it('removes duplicate values', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 1],
            ['id' => 3],
        ];
        $config = 'id';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(3);
        expect(array_column($result, 'id'))->toBe([1, 2, 3]);
    });

    it('keeps first occurrence of duplicate', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['id' => 1, 'name' => 'first'],
            ['id' => 2, 'name' => 'second'],
            ['id' => 1, 'name' => 'duplicate'],
        ];
        $config = 'id';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect($result[0]['name'])->toBe('first');
        expect($result[1]['name'])->toBe('second');
    });

    it('handles string values', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['name' => 'apple'],
            ['name' => 'banana'],
            ['name' => 'apple'],
            ['name' => 'cherry'],
        ];
        $config = 'name';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(3);
        expect(array_column($result, 'name'))->toBe(['apple', 'banana', 'cherry']);
    });

    it('handles null values', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['value' => null],
            ['value' => 1],
            ['value' => null],
            ['value' => 2],
        ];
        $config = 'value';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(3);
    });

    it('handles boolean values', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['active' => true],
            ['active' => false],
            ['active' => true],
            ['active' => false],
        ];
        $config = 'active';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('handles array values', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['tags' => [1, 2]],
            ['tags' => [3, 4]],
            ['tags' => [1, 2]],
        ];
        $config = 'tags';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('handles mixed type values', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['value' => 1],
            ['value' => '1'],
            ['value' => 1.0],
            ['value' => true],
        ];
        $config = 'value';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        // JSON encoding treats 1, 1.0 as same, but '1' and true as different
        expect($result)->toHaveCount(3);
    });

    it('handles empty array', function(): void {
        $operator = new DistinctOperator();
        $items = [];
        $config = 'id';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('handles all unique values', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];
        $config = 'id';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(3);
    });

    it('handles all duplicate values', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['id' => 1],
            ['id' => 1],
            ['id' => 1],
        ];
        $config = 'id';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
    });

    it('preserves array keys', function(): void {
        $operator = new DistinctOperator();
        $items = [
            10 => ['id' => 1],
            20 => ['id' => 2],
            30 => ['id' => 1],
        ];
        $config = 'id';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_keys($result))->toBe([10, 20]);
    });

    it('returns original items when config is not string', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['id' => 1],
            ['id' => 1],
        ];
        $config = 123;
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('handles nested field paths', function(): void {
        $operator = new DistinctOperator();
        $items = [
            ['user' => ['id' => 1]],
            ['user' => ['id' => 2]],
            ['user' => ['id' => 1]],
        ];
        $config = 'user.id';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(DistinctOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new DistinctOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});

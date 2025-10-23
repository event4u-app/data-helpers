<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\BetweenOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;

describe('BetweenOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new BetweenOperator();

        expect($operator->getName())->toBe('BETWEEN');
    });

    it('filters items where value is between min and max', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['price' => 15],
            ['price' => 5],
            ['price' => 25],
        ];
        $config = ['price' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['price'])->toBe(15);
    });

    it('includes items where value equals min', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['price' => 10],
            ['price' => 5],
        ];
        $config = ['price' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['price'])->toBe(10);
    });

    it('includes items where value equals max', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['price' => 20],
            ['price' => 25],
        ];
        $config = ['price' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['price'])->toBe(20);
    });

    it('excludes items where value is below min', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['price' => 5],
            ['price' => 15],
        ];
        $config = ['price' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['price'])->toBe(15);
    });

    it('excludes items where value is above max', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['price' => 25],
            ['price' => 15],
        ];
        $config = ['price' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['price'])->toBe(15);
    });

    it('handles negative numbers', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => -5],
            ['value' => 0],
            ['value' => 5],
            ['value' => -15],
            ['value' => 15],
        ];
        $config = ['value' => ['min' => -10, 'max' => 10]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(3);
        expect(array_column($result, 'value'))->toBe([-5, 0, 5]);
    });

    it('handles float values', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => 2.0],
            ['value' => 1.5],
            ['value' => 2.5],
            ['value' => 1.0],
            ['value' => 3.0],
        ];
        $config = ['value' => ['min' => 1.5, 'max' => 2.5]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(3);
        expect(array_column($result, 'value'))->toBe([2.0, 1.5, 2.5]);
    });

    it('handles string numeric values', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => '15'],
            ['value' => '5'],
        ];
        $config = ['value' => ['min' => '10', 'max' => '20']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe('15');
    });

    it('excludes items with non-numeric value', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => 'test'],
            ['value' => 15],
        ];
        $config = ['value' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['value'])->toBe(15);
    });

    it('excludes all items when min is non-numeric', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => 15],
            ['value' => 20],
        ];
        $config = ['value' => ['min' => 'test', 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('excludes all items when max is non-numeric', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => 15],
            ['value' => 20],
        ];
        $config = ['value' => ['min' => 10, 'max' => 'test']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('handles zero as value', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => 0],
            ['value' => -10],
            ['value' => 10],
        ];
        $config = ['value' => ['min' => -5, 'max' => 5]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe(0);
    });

    it('handles zero as min', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => 0],
            ['value' => 5],
            ['value' => -1],
        ];
        $config = ['value' => ['min' => 0, 'max' => 10]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'value'))->toBe([0, 5]);
    });

    it('handles zero as max', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => 0],
            ['value' => -5],
            ['value' => 1],
        ];
        $config = ['value' => ['min' => -10, 'max' => 0]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'value'))->toBe([0, -5]);
    });

    it('handles same min and max', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => 10],
            ['value' => 9],
            ['value' => 11],
        ];
        $config = ['value' => ['min' => 10, 'max' => 10]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe(10);
    });

    it('handles large numbers', function(): void {
        $operator = new BetweenOperator();
        $items = [
            ['value' => 1500000],
            ['value' => 500000],
            ['value' => 2500000],
        ];
        $config = ['value' => ['min' => 1000000, 'max' => 2000000]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe(1500000);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(BetweenOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new BetweenOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});

<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\BetweenOperator;
use event4u\DataHelpers\DataFilter\Operators\NotBetweenOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;

describe('NotBetweenOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new NotBetweenOperator();

        expect($operator->getName())->toBe('NOT BETWEEN');
    });

    it('excludes items where value is between min and max', function(): void {
        $operator = new NotBetweenOperator();
        $items = [
            ['price' => 15],
            ['price' => 5],
        ];
        $config = ['price' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['price'])->toBe(5);
    });

    it('excludes items where value equals min', function(): void {
        $operator = new NotBetweenOperator();
        $items = [
            ['price' => 10],
            ['price' => 5],
        ];
        $config = ['price' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['price'])->toBe(5);
    });

    it('excludes items where value equals max', function(): void {
        $operator = new NotBetweenOperator();
        $items = [
            ['price' => 20],
            ['price' => 25],
        ];
        $config = ['price' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['price'])->toBe(25);
    });

    it('includes items where value is below min', function(): void {
        $operator = new NotBetweenOperator();
        $items = [
            ['price' => 5],
            ['price' => 15],
        ];
        $config = ['price' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['price'])->toBe(5);
    });

    it('includes items where value is above max', function(): void {
        $operator = new NotBetweenOperator();
        $items = [
            ['price' => 25],
            ['price' => 15],
        ];
        $config = ['price' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['price'])->toBe(25);
    });

    it('handles negative numbers', function(): void {
        $operator = new NotBetweenOperator();
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

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'value'))->toBe([-15, 15]);
    });

    it('handles float values', function(): void {
        $operator = new NotBetweenOperator();
        $items = [
            ['value' => 2.0],
            ['value' => 1.0],
            ['value' => 3.0],
        ];
        $config = ['value' => ['min' => 1.5, 'max' => 2.5]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'value'))->toBe([1.0, 3.0]);
    });

    it('excludes items with non-numeric value', function(): void {
        $operator = new NotBetweenOperator();
        $items = [
            ['value' => 'test'],
            ['value' => 5],
        ];
        $config = ['value' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['value'])->toBe(5);
    });

    it('excludes all items when min is non-numeric', function(): void {
        $operator = new NotBetweenOperator();
        $items = [
            ['value' => 5],
            ['value' => 25],
        ];
        $config = ['value' => ['min' => 'test', 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('excludes all items when max is non-numeric', function(): void {
        $operator = new NotBetweenOperator();
        $items = [
            ['value' => 5],
            ['value' => 25],
        ];
        $config = ['value' => ['min' => 10, 'max' => 'test']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('excludes items where value equals min and max when they are same', function(): void {
        $operator = new NotBetweenOperator();
        $items = [
            ['value' => 10],
            ['value' => 9],
            ['value' => 11],
        ];
        $config = ['value' => ['min' => 10, 'max' => 10]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'value'))->toBe([9, 11]);
    });

    it('is inverse of BetweenOperator', function(): void {
        $notBetween = new NotBetweenOperator();
        $between = new BetweenOperator();
        $items = [
            ['value' => 5],
            ['value' => 10],
            ['value' => 15],
            ['value' => 20],
            ['value' => 25],
        ];
        $config = ['value' => ['min' => 10, 'max' => 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $betweenResult = $between->apply($items, $config, $context);
        $notBetweenResult = $notBetween->apply($items, $config, $context);

        // Combined results should equal original items
        expect(count($betweenResult) + count($notBetweenResult))->toBe(count($items));

        // No overlap
        $betweenValues = array_column($betweenResult, 'value');
        $notBetweenValues = array_column($notBetweenResult, 'value');
        expect(array_intersect($betweenValues, $notBetweenValues))->toBeEmpty();
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(NotBetweenOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new NotBetweenOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});

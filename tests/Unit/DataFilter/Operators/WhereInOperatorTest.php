<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\WhereInOperator;

describe('WhereInOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new WhereInOperator();

        expect($operator->getName())->toBe('WHERE IN');
    });

    it('has IN alias', function(): void {
        $operator = new WhereInOperator();

        expect($operator->getAliases())->toBe(['IN']);
    });

    it('includes items where value is in array', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['id' => 2],
            ['id' => 4],
        ];
        $config = ['id' => ['values' => [1, 2, 3]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['id'])->toBe(2);
    });

    it('excludes items where value is not in array', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['id' => 4],
        ];
        $config = ['id' => ['values' => [1, 2, 3]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('handles string values', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['fruit' => 'banana'],
            ['fruit' => 'orange'],
        ];
        $config = ['fruit' => ['values' => ['apple', 'banana', 'cherry']]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['fruit'])->toBe('banana');
    });

    it('uses strict comparison', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['id' => '1'],
            ['id' => 1],
        ];
        $config = ['id' => ['values' => [1, 2, 3]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['id'])->toBe(1);
    });

    it('handles null value', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['value' => null],
            ['value' => 1],
        ];
        $config = ['value' => ['values' => [null, 1, 2]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('handles boolean values', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['active' => true],
            ['active' => false],
            ['active' => 1],
        ];
        $config = ['active' => ['values' => [true, false]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'active'))->toBe([true, false]);
    });

    it('handles empty array', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['id' => 1],
        ];
        $config = ['id' => ['values' => []]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('excludes all items when values is not an array', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['id' => 1],
        ];
        $config = ['id' => ['values' => 'not-an-array']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('handles mixed type array', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['value' => 1],
            ['value' => 'two'],
            ['value' => 3.0],
            ['value' => null],
            ['value' => true],
            ['value' => 2],
        ];
        $config = ['value' => ['values' => [1, 'two', 3.0, null, true]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(5);
    });

    it('handles float values', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['value' => 2.5],
            ['value' => 2.0],
        ];
        $config = ['value' => ['values' => [1.5, 2.5, 3.5]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe(2.5);
    });

    it('handles array values', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['value' => [1, 2]],
            ['value' => [3, 4]],
            ['value' => [1, 3]],
        ];
        $config = ['value' => ['values' => [[1, 2], [3, 4]]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('handles single value in array', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['id' => 42],
            ['id' => 43],
        ];
        $config = ['id' => ['values' => [42]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['id'])->toBe(42);
    });

    it('handles large array', function(): void {
        $operator = new WhereInOperator();
        $items = [
            ['id' => 500],
            ['id' => 1001],
        ];
        $values = range(1, 1000);
        $config = ['id' => ['values' => $values]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['id'])->toBe(500);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(WhereInOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new WhereInOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});

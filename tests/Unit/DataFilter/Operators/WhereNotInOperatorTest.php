<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\WhereInOperator;
use event4u\DataHelpers\DataFilter\Operators\WhereNotInOperator;

describe('WhereNotInOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new WhereNotInOperator();

        expect($operator->getName())->toBe('WHERE NOT IN');
    });

    it('has NOT IN alias', function(): void {
        $operator = new WhereNotInOperator();

        expect($operator->getAliases())->toBe(['NOT IN']);
    });

    it('excludes items where value is in array', function(): void {
        $operator = new WhereNotInOperator();
        $items = [
            ['id' => 2],
            ['id' => 4],
        ];
        $config = ['id' => ['values' => [1, 2, 3]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['id'])->toBe(4);
    });

    it('includes items where value is not in array', function(): void {
        $operator = new WhereNotInOperator();
        $items = [
            ['id' => 4],
        ];
        $config = ['id' => ['values' => [1, 2, 3]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['id'])->toBe(4);
    });

    it('handles string values', function(): void {
        $operator = new WhereNotInOperator();
        $items = [
            ['fruit' => 'banana'],
            ['fruit' => 'orange'],
        ];
        $config = ['fruit' => ['values' => ['apple', 'banana', 'cherry']]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['fruit'])->toBe('orange');
    });

    it('uses strict comparison', function(): void {
        $operator = new WhereNotInOperator();
        $items = [
            ['id' => '1'],
            ['id' => 1],
        ];
        $config = ['id' => ['values' => [1, 2, 3]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['id'])->toBe('1');
    });

    it('handles null value', function(): void {
        $operator = new WhereNotInOperator();
        $items = [
            ['value' => null],
            ['value' => 1],
            ['value' => 2],
        ];
        $config = ['value' => ['values' => [null, 1]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[2]['value'])->toBe(2);
    });

    it('handles boolean values', function(): void {
        $operator = new WhereNotInOperator();
        $items = [
            ['active' => true],
            ['active' => false],
            ['active' => 1],
        ];
        $config = ['active' => ['values' => [true]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'active'))->toBe([false, 1]);
    });

    it('includes all items when values is empty array', function(): void {
        $operator = new WhereNotInOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
        ];
        $config = ['id' => ['values' => []]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('excludes all items when values is not an array', function(): void {
        $operator = new WhereNotInOperator();
        $items = [
            ['id' => 1],
        ];
        $config = ['id' => ['values' => 'not-an-array']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('handles mixed type array', function(): void {
        $operator = new WhereNotInOperator();
        $items = [
            ['value' => 1],
            ['value' => 'two'],
            ['value' => 3.0],
            ['value' => null],
            ['value' => true],
            ['value' => 2],
        ];
        $config = ['value' => ['values' => [1, 'two']]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(4);
    });

    it('is inverse of WhereInOperator', function(): void {
        $notIn = new WhereNotInOperator();
        $in = new WhereInOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
            ['id' => 5],
        ];
        $config = ['id' => ['values' => [2, 3, 4]]];
        $context = new OperatorContext(null, null, false, [], []);

        $inResult = $in->apply($items, $config, $context);
        $notInResult = $notIn->apply($items, $config, $context);

        // Combined results should equal original items
        expect(count($inResult) + count($notInResult))->toBe(count($items));

        // No overlap
        $inValues = array_column($inResult, 'id');
        $notInValues = array_column($notInResult, 'id');
        expect(array_intersect($inValues, $notInValues))->toBeEmpty();
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(WhereNotInOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new WhereNotInOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});

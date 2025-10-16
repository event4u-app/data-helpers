<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\WhereNullOperator;
use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\WhereNotNullOperator;

describe('WhereNotNullOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new WhereNotNullOperator();

        expect($operator->getName())->toBe('WHERE NOT NULL');
    });

    it('has IS NOT NULL and NOT NULL aliases', function(): void {
        $operator = new WhereNotNullOperator();

        expect($operator->getAliases())->toBe(['IS NOT NULL', 'NOT NULL']);
    });

    it('excludes items where value is null', function(): void {
        $operator = new WhereNotNullOperator();
        $items = [
            ['value' => null],
            ['value' => 1],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['value'])->toBe(1);
    });

    it('includes items where value is non-null', function(): void {
        $operator = new WhereNotNullOperator();
        $items = [
            ['value' => 1],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe(1);
    });

    it('includes items where value is zero', function(): void {
        $operator = new WhereNotNullOperator();
        $items = [
            ['value' => 0],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe(0);
    });

    it('includes items where value is empty string', function(): void {
        $operator = new WhereNotNullOperator();
        $items = [
            ['value' => ''],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe('');
    });

    it('includes items where value is false boolean', function(): void {
        $operator = new WhereNotNullOperator();
        $items = [
            ['value' => false],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe(false);
    });

    it('includes items where value is empty array', function(): void {
        $operator = new WhereNotNullOperator();
        $items = [
            ['value' => []],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe([]);
    });

    it('includes items where value is string null', function(): void {
        $operator = new WhereNotNullOperator();
        $items = [
            ['value' => 'null'],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe('null');
    });

    it('includes items where value is any non-null value', function(): void {
        $operator = new WhereNotNullOperator();
        $items = [
            ['value' => 1],
            ['value' => 'test'],
            ['value' => true],
            ['value' => [1, 2]],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(4);
        expect(array_column($result, 'value'))->toBe([1, 'test', true, [1, 2]]);
    });

    it('is inverse of WhereNullOperator', function(): void {
        $notNull = new WhereNotNullOperator();
        $isNull = new WhereNullOperator();
        $items = [
            ['value' => null],
            ['value' => 0],
            ['value' => ''],
            ['value' => false],
            ['value' => 'test'],
            ['value' => 1],
            ['value' => true],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $isNullResult = $isNull->apply($items, $config, $context);
        $notNullResult = $notNull->apply($items, $config, $context);

        // Combined results should equal original items
        expect(count($isNullResult) + count($notNullResult))->toBe(count($items));

        // Verify they are truly inverse - null should only be in isNull result
        expect($isNullResult)->toHaveCount(1);
        expect($notNullResult)->toHaveCount(6);
        expect($isNullResult[0]['value'])->toBeNull();
    });

    it('requires no config parameters', function(): void {
        $operator = new WhereNotNullOperator();
        $items = [
            ['value' => 'test'],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(WhereNotNullOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new WhereNotNullOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});


<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\WhereNullOperator;

describe('WhereNullOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new WhereNullOperator();

        expect($operator->getName())->toBe('WHERE NULL');
    });

    it('has IS NULL alias', function(): void {
        $operator = new WhereNullOperator();

        expect($operator->getAliases())->toBe(['IS NULL']);
    });

    it('includes items where value is null', function(): void {
        $operator = new WhereNullOperator();
        $items = [
            ['value' => null],
            ['value' => 1],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBeNull();
    });

    it('excludes items where value is non-null', function(): void {
        $operator = new WhereNullOperator();
        $items = [
            ['value' => 1],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('excludes items where value is zero', function(): void {
        $operator = new WhereNullOperator();
        $items = [
            ['value' => 0],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['value'])->toBeNull();
    });

    it('excludes items where value is empty string', function(): void {
        $operator = new WhereNullOperator();
        $items = [
            ['value' => ''],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['value'])->toBeNull();
    });

    it('excludes items where value is false boolean', function(): void {
        $operator = new WhereNullOperator();
        $items = [
            ['value' => false],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['value'])->toBeNull();
    });

    it('excludes items where value is empty array', function(): void {
        $operator = new WhereNullOperator();
        $items = [
            ['value' => []],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['value'])->toBeNull();
    });

    it('excludes items where value is string null', function(): void {
        $operator = new WhereNullOperator();
        $items = [
            ['value' => 'null'],
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['value'])->toBeNull();
    });

    it('excludes items where value is any non-null value', function(): void {
        $operator = new WhereNullOperator();
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

        expect($result)->toHaveCount(1);
        expect($result[4]['value'])->toBeNull();
    });

    it('requires no config parameters', function(): void {
        $operator = new WhereNullOperator();
        $items = [
            ['value' => null],
        ];
        $config = ['value' => []];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(WhereNullOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new WhereNullOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });
});

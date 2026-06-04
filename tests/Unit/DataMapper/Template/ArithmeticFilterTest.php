<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('Arithmetic Filters', function(): void {
    describe('Multiply', function(): void {
        it('multiplies by a literal factor (hours -> minutes)', function(): void {
            $template = ['result' => '{{ data.hours | multiply:60 }}'];
            $sources = ['data' => ['hours' => 2]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(120);
        });

        it('multiplies by a decimal literal', function(): void {
            $template = ['result' => '{{ data.net | multiply:1.5 }}'];
            $sources = ['data' => ['net' => 100]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(150.0);
        });

        it('multiplies by a factor from the same source', function(): void {
            $template = ['result' => '{{ data.net | multiply:data.factor }}'];
            $sources = ['data' => ['net' => 100, 'factor' => 1.5]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(150.0);
        });

        it('multiplies by a factor from a different named source', function(): void {
            $template = ['result' => '{{ order.net | multiply:config.factor }}'];
            $sources = ['order' => ['net' => 100], 'config' => ['factor' => 3]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(300);
        });

        it('returns non-numeric value unchanged', function(): void {
            $template = ['result' => '{{ data.value | multiply:60 }}'];
            $sources = ['data' => ['value' => 'not-a-number']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('not-a-number');
        });
    });

    describe('Divide', function(): void {
        it('divides by a literal divisor (minutes -> hours)', function(): void {
            $template = ['result' => '{{ data.minutes | divide:60 }}'];
            $sources = ['data' => ['minutes' => 120]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(2);
        });

        it('produces a decimal result', function(): void {
            $template = ['result' => '{{ data.minutes | divide:60 }}'];
            $sources = ['data' => ['minutes' => 90]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(1.5);
        });

        it('divides by a divisor from the source', function(): void {
            $template = ['result' => '{{ data.total | divide:data.installments }}'];
            $sources = ['data' => ['total' => 120, 'installments' => 4]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(30);
        });

        it('returns the value unchanged on division by zero', function(): void {
            $template = ['result' => '{{ data.value | divide:0 }}'];
            $sources = ['data' => ['value' => 50]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(50);
        });

        it('returns the value unchanged when a source divisor is zero', function(): void {
            $template = ['result' => '{{ data.value | divide:data.divisor }}'];
            $sources = ['data' => ['value' => 50, 'divisor' => 0]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(50);
        });
    });

    describe('Add and Subtract', function(): void {
        it('adds a literal', function(): void {
            $template = ['result' => '{{ data.count | add:1 }}'];
            $sources = ['data' => ['count' => 41]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(42);
        });

        it('adds a value from the source', function(): void {
            $template = ['result' => '{{ data.net | add:data.shipping }}'];
            $sources = ['data' => ['net' => 100, 'shipping' => 9]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(109);
        });

        it('subtracts a literal', function(): void {
            $template = ['result' => '{{ data.gross | subtract:19 }}'];
            $sources = ['data' => ['gross' => 119]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(100);
        });

        it('subtracts a value from the source', function(): void {
            $template = ['result' => '{{ data.gross | subtract:data.discount }}'];
            $sources = ['data' => ['gross' => 100, 'discount' => 15]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(85);
        });
    });

    describe('Chaining', function(): void {
        it('chains divide and multiply', function(): void {
            // minutes -> hours -> back to minutes
            $template = ['result' => '{{ data.minutes | divide:60 | multiply:60 }}'];
            $sources = ['data' => ['minutes' => 120]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(120);
        });

        it('combines a default with arithmetic for a missing value', function(): void {
            $template = ['result' => '{{ data.hours ?? 1 | multiply:60 }}'];
            $sources = ['data' => ['hours' => null]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(60);
        });
    });
});

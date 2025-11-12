<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\FluentDataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToArray;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToBoolean;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToDecimal;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToFloat;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToInteger;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToJson;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToString;

describe('Cast Filters', function(): void {
    describe('CastToInteger', function(): void {
        it('casts numeric string to integer using filter name', function(): void {
            $source = ['value' => '123'];
            $target = [];
            $template = ['result' => '{{ value | int }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToInteger::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe(123);
            expect($result['result'])->toBeInt();
        });

        it('casts numeric string to integer using alias', function(): void {
            $source = ['value' => '456'];
            $target = [];
            $template = ['result' => '{{ value | integer }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToInteger::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe(456);
            expect($result['result'])->toBeInt();
        });

        it('skips null values', function(): void {
            $source = ['value' => null];
            $target = [];
            $template = ['result' => '{{ value | int }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToInteger::class])
                ->skipNull(false);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBeNull();
        });
    });

    describe('CastToBoolean', function(): void {
        it('casts string to boolean using filter name', function(): void {
            $source = ['value' => 'true'];
            $target = [];
            $template = ['result' => '{{ value | bool }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToBoolean::class])
                ->skipNull(false);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBeTrue();
        });

        it('casts string to boolean using alias', function(): void {
            $source = ['value' => 'false'];
            $target = [];
            $template = ['result' => '{{ value | boolean }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToBoolean::class])
                ->skipNull(false);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBeFalse();
        });

        it('converts various true values', function(): void {
            $trueValues = ['1', 'true', 'yes', 'on'];

            foreach ($trueValues as $value) {
                $source = ['value' => $value];
                $target = [];
                $template = ['result' => '{{ value | bool }}'];

                $mapper = FluentDataMapper::make($source)
                    ->target($target)
                    ->template($template)
                    ->pipeline([CastToBoolean::class])
                ->skipNull(false);

                $result = $mapper->map()->getTarget();

                expect($result['result'])->toBeTrue();
            }
        });

        it('converts various false values', function(): void {
            $falseValues = ['0', 'false', 'no', 'off', ''];

            foreach ($falseValues as $value) {
                $source = ['value' => $value];
                $target = [];
                $template = ['result' => '{{ value | bool }}'];

                $mapper = FluentDataMapper::make($source)
                    ->target($target)
                    ->template($template)
                    ->pipeline([CastToBoolean::class])
                ->skipNull(false);

                $result = $mapper->map()->getTarget();

                expect($result['result'])->toBeFalse();
            }
        });
    });

    describe('CastToFloat', function(): void {
        it('casts string to float', function(): void {
            $source = ['value' => '123.45'];
            $target = [];
            $template = ['result' => '{{ value | float }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToFloat::class])
                ->skipNull(false);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe(123.45);
            expect($result['result'])->toBeFloat();
        });

        it('skips null values', function(): void {
            $source = ['value' => null];
            $target = [];
            $template = ['result' => '{{ value | float }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToFloat::class])
                ->skipNull(false);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBeNull();
        });
    });

    describe('CastToString', function(): void {
        it('casts integer to string', function(): void {
            $source = ['value' => 123];
            $target = [];
            $template = ['result' => '{{ value | string }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToString::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe('123');
            expect($result['result'])->toBeString();
        });

        it('casts float to string', function(): void {
            $source = ['value' => 123.45];
            $target = [];
            $template = ['result' => '{{ value | string }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToString::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe('123.45');
            expect($result['result'])->toBeString();
        });

        it('casts boolean to string', function(): void {
            $source = ['value' => true];
            $target = [];
            $template = ['result' => '{{ value | string }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToString::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe('1');
            expect($result['result'])->toBeString();
        });

        it('skips null values', function(): void {
            $source = ['value' => null];
            $target = [];
            $template = ['result' => '{{ value | string }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToString::class])
                ->skipNull(false);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBeNull();
        });
    });

    describe('CastToArray', function(): void {
        it('wraps scalar value in array', function(): void {
            $source = ['value' => 'test'];
            $target = [];
            $template = ['result' => '{{ value | array }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToArray::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe(['test']);
            expect($result['result'])->toBeArray();
        });

        it('converts object to array', function(): void {
            $obj = new stdClass();
            $obj->key = 'value';

            $source = ['value' => $obj];
            $target = [];
            $template = ['result' => '{{ value | array }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToArray::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe(['key' => 'value']);
            expect($result['result'])->toBeArray();
        });

        it('keeps existing arrays unchanged', function(): void {
            $source = ['value' => ['a', 'b', 'c']];
            $target = [];
            $template = ['result' => '{{ value | array }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToArray::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe(['a', 'b', 'c']);
        });
    });

    describe('CastToDecimal', function(): void {
        it('formats number to decimal with 2 places', function(): void {
            $source = ['value' => 123.456];
            $target = [];
            $template = ['result' => '{{ value | decimal }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([new CastToDecimal(2)]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe('123.46');
            expect($result['result'])->toBeString();
        });

        it('formats integer to decimal with 2 places', function(): void {
            $source = ['value' => 100];
            $target = [];
            $template = ['result' => '{{ value | decimal }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([new CastToDecimal(2)]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe('100.00');
        });

        it('formats with custom precision', function(): void {
            $source = ['value' => 123.46];
            $target = [];
            $template = ['result' => '{{ value | decimal }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([new CastToDecimal(4)]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe('123.4600');
        });
    });

    describe('CastToJson', function(): void {
        it('converts array to JSON string', function(): void {
            $source = ['value' => ['key' => 'value', 'number' => 123]];
            $target = [];
            $template = ['result' => '{{ value | json }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToJson::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe('{"key":"value","number":123}');
            expect($result['result'])->toBeString();
        });

        it('converts object to JSON string', function(): void {
            $obj = new stdClass();
            $obj->name = 'John';
            $obj->age = 30;

            $source = ['value' => $obj];
            $target = [];
            $template = ['result' => '{{ value | json }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToJson::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe('{"name":"John","age":30}');
        });

        it('converts null to JSON null', function(): void {
            $source = ['value' => null];
            $target = [];
            $template = ['result' => '{{ value | json }}'];

            $mapper = FluentDataMapper::make($source)
                ->target($target)
                ->template($template)
                ->pipeline([CastToJson::class]);

            $result = $mapper->map()->getTarget();

            expect($result['result'])->toBe('null');
        });
    });
});

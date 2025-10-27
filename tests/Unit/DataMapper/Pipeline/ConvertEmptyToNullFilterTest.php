<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\ConvertEmptyToNull;

describe('ConvertEmptyToNull Filter', function(): void {
    describe('Default Behavior (Empty Strings and Arrays)', function(): void {
        it('converts empty string to null in template', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null }}'];
            $sources = ['data' => ['value' => '']];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('converts empty array to null in template', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null }}'];
            $sources = ['data' => ['value' => []]];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('does not convert zero by default', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null }}'];
            $sources = ['data' => ['value' => 0]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(0);
        });

        it('does not convert string zero by default', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null }}'];
            $sources = ['data' => ['value' => '0']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('0');
        });

        it('does not convert boolean false', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null }}'];
            $sources = ['data' => ['value' => false]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(false);
        });

        it('keeps non-empty values unchanged', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null }}'];
            $sources = ['data' => ['value' => 'hello']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('hello');
        });
    });

    describe('With convertZero Parameter', function(): void {
        it('converts integer zero to null when enabled', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null:"zero" }}'];
            $sources = ['data' => ['value' => 0]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('does not convert string zero when only convertZero enabled', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null:"zero" }}'];
            $sources = ['data' => ['value' => '0']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('0');
        });

        it('still converts empty string when convertZero enabled', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null:"zero" }}'];
            $sources = ['data' => ['value' => '']];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['result'])->toBeNull();
        });
    });

    describe('With convertStringZero Parameter', function(): void {
        it('converts string zero to null when enabled', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null:"string_zero" }}'];
            $sources = ['data' => ['value' => '0']];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('does not convert integer zero when only convertStringZero enabled', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null:"string_zero" }}'];
            $sources = ['data' => ['value' => 0]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(0);
        });

        it('still converts empty string when convertStringZero enabled', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null:"string_zero" }}'];
            $sources = ['data' => ['value' => '']];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['result'])->toBeNull();
        });
    });

    describe('With Both Parameters Enabled', function(): void {
        it('converts both zero types to null', function(): void {
            $template = [
                'intZero' => '{{ data.intZero | empty_to_null:"zero,string_zero" }}',
                'stringZero' => '{{ data.stringZero | empty_to_null:"zero,string_zero" }}',
            ];
            $sources = ['data' => [
                'intZero' => 0,
                'stringZero' => '0',
            ]];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['intZero'])->toBeNull();
            expect($result['stringZero'])->toBeNull();
        });

        it('still converts empty strings and arrays', function(): void {
            $template = [
                'emptyString' => '{{ data.emptyString | empty_to_null:"zero,string_zero" }}',
                'emptyArray' => '{{ data.emptyArray | empty_to_null:"zero,string_zero" }}',
            ];
            $sources = ['data' => [
                'emptyString' => '',
                'emptyArray' => [],
            ]];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['emptyString'])->toBeNull();
            expect($result['emptyArray'])->toBeNull();
        });

        it('does not convert boolean false', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null:"zero,string_zero" }}'];
            $sources = ['data' => ['value' => false]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(false);
        });
    });

    describe('Pipeline Mode', function(): void {
        it('converts empty string in pipeline mode', function(): void {
            $source = ['value' => ''];
            $mapping = ['result' => '{{ value }}'];

            $result = DataMapper::source($source)
                ->template($mapping)
                ->pipeline([new ConvertEmptyToNull()])
                ->map()
                ->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('converts empty array in pipeline mode', function(): void {
            $source = ['value' => []];
            $mapping = ['result' => '{{ value }}'];

            $result = DataMapper::source($source)
                ->template($mapping)
                ->pipeline([new ConvertEmptyToNull()])
                ->map()
                ->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('converts zero in pipeline mode when enabled', function(): void {
            $source = ['value' => 0];
            $mapping = ['result' => '{{ value }}'];

            $result = DataMapper::source($source)
                ->template($mapping)
                ->pipeline([new ConvertEmptyToNull(convertZero: true)])
                ->map()
                ->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('converts string zero in pipeline mode when enabled', function(): void {
            $source = ['value' => '0'];
            $mapping = ['result' => '{{ value }}'];

            $result = DataMapper::source($source)
                ->template($mapping)
                ->pipeline([new ConvertEmptyToNull(convertStringZero: true)])
                ->map()
                ->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('converts both zero types in pipeline mode when both enabled', function(): void {
            $source = [
                'intZero' => 0,
                'stringZero' => '0',
            ];
            $mapping = [
                'intZero' => '{{ intZero }}',
                'stringZero' => '{{ stringZero }}',
            ];

            $result = DataMapper::source($source)
                ->template($mapping)
                ->pipeline([new ConvertEmptyToNull(convertZero: true, convertStringZero: true)])
                ->map()
                ->getTarget();

            expect($result['intZero'])->toBeNull();
            expect($result['stringZero'])->toBeNull();
        });
    });

    describe('Chaining with Other Filters', function(): void {
        it('chains with trim filter', function(): void {
            $template = ['result' => '{{ data.value | trim | empty_to_null }}'];
            $sources = ['data' => ['value' => '   ']];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('chains with upper filter', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null | upper }}'];
            $sources = ['data' => ['value' => 'hello']];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe('HELLO');
        });
    });

    describe('Real-World Examples', function(): void {
        it('cleans API response with empty optional fields', function(): void {
            $template = [
                'name' => '{{ data.name }}',
                'email' => '{{ data.email }}',
                'phone' => '{{ data.phone | empty_to_null }}',
                'address' => '{{ data.address | empty_to_null }}',
            ];
            $sources = ['data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '',
                'address' => '',
            ]];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['name'])->toBe('John Doe');
            expect($result['email'])->toBe('john@example.com');
            expect($result['phone'])->toBeNull();
            expect($result['address'])->toBeNull();
        });

        it('handles form data with empty fields', function(): void {
            $template = [
                'name' => '{{ form.name }}',
                'bio' => '{{ form.bio | empty_to_null }}',
                'tags' => '{{ form.tags | empty_to_null }}',
            ];
            $sources = ['form' => [
                'name' => 'Jane Smith',
                'bio' => '',
                'tags' => [],
            ]];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['name'])->toBe('Jane Smith');
            expect($result['bio'])->toBeNull();
            expect($result['tags'])->toBeNull();
        });
    });

    describe('Convert False Option', function(): void {
        it('converts false to null with "false" option', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null:"false" }}'];
            $sources = ['data' => ['value' => false]];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('does not convert false without "false" option', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null }}'];
            $sources = ['data' => ['value' => false]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(false);
        });

        it('does not convert true with "false" option', function(): void {
            $template = ['result' => '{{ data.value | empty_to_null:"false" }}'];
            $sources = ['data' => ['value' => true]];

            $result = DataMapper::source($sources)->template($template)->map()->getTarget();

            expect($result['result'])->toBe(true);
        });

        it('combines zero, string_zero and false options', function(): void {
            $template = [
                'zero' => '{{ data.zero | empty_to_null:"zero,string_zero,false" }}',
                'string_zero' => '{{ data.string_zero | empty_to_null:"zero,string_zero,false" }}',
                'false' => '{{ data.false | empty_to_null:"zero,string_zero,false" }}',
                'empty' => '{{ data.empty | empty_to_null:"zero,string_zero,false" }}',
            ];
            $sources = ['data' => [
                'zero' => 0,
                'string_zero' => '0',
                'false' => false,
                'empty' => '',
            ]];

            $result = DataMapper::source($sources)->template($template)->skipNull(false)->map()->getTarget();

            expect($result['zero'])->toBeNull();
            expect($result['string_zero'])->toBeNull();
            expect($result['false'])->toBeNull();
            expect($result['empty'])->toBeNull();
        });

        it('converts false with constructor parameter', function(): void {
            $filter = new ConvertEmptyToNull(convertFalse: true);
            $template = ['result' => '{{ data.value }}'];
            $sources = ['data' => ['value' => false]];

            $result = DataMapper::source($sources)
                ->template($template)
                ->pipeline([$filter])
                ->skipNull(false)
                ->map()
                ->getTarget();

            expect($result['result'])->toBeNull();
        });

        it('keeps true unchanged with constructor parameter', function(): void {
            $filter = new ConvertEmptyToNull(convertFalse: true);
            $template = ['result' => '{{ data.value }}'];
            $sources = ['data' => ['value' => true]];

            $result = DataMapper::source($sources)
                ->template($template)
                ->pipeline([$filter])
                ->map()
                ->getTarget();

            expect($result['result'])->toBe(true);
        });
    });
});

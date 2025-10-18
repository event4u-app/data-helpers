<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('Replace Filter Integration', function(): void {
    it('replaces simple string with template syntax', function(): void {
        $source = ['text' => 'Hello Mr Smith'];
        $target = [];

        $mapping = [
            'result' => '{{ text | replace:"Mr":"Herr" }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['result'])->toBe('Hello Herr Smith');
    });

    it('replaces multiple searches with single replacement', function(): void {
        $source = ['text' => 'Mr and Mrs Smith'];
        $target = [];

        $mapping = [
            'result' => '{{ text | replace:[Mr,Mrs]:Person }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['result'])->toBe('Person and Person Smith');
    });

    it('replaces multiple searches with multiple replacements', function(): void {
        $source = ['text' => 'Mr and Mrs Smith'];
        $target = [];

        $mapping = [
            'result' => '{{ text | replace:[Mr,Mrs]:[Herr,Frau] }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['result'])->toBe('Herr and Frau Smith');
    });

    it('chains with other filters', function(): void {
        $source = ['text' => 'hello mr smith'];
        $target = [];

        $mapping = [
            'result' => '{{ text | replace:"mr":"herr" | ucfirst }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['result'])->toBe('Hello herr smith');
    });

    it('works with multiple replacements in chain', function(): void {
        $source = ['text' => 'Mr and Mrs Smith'];
        $target = [];

        $mapping = [
            'result' => '{{ text | replace:[Mr,Mrs,Smith]:[Herr,Frau,Schmidt] }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['result'])->toBe('Herr and Frau Schmidt');
    });

    it('handles empty replacement', function(): void {
        $source = ['text' => 'Hello Mr Smith'];
        $target = [];

        $mapping = [
            'result' => '{{ text | replace:"Mr":"" }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['result'])->toBe('Hello  Smith');
    });

    it('handles case-sensitive replacement', function(): void {
        $source = ['text' => 'Hello mr and Mr Smith'];
        $target = [];

        $mapping = [
            'result' => '{{ text | replace:"Mr":"Herr" }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['result'])->toBe('Hello mr and Herr Smith');
    });

    it('handles case-insensitive replacement', function(): void {
        $source = ['text' => 'Hello mr and Mr Smith'];
        $target = [];

        $mapping = [
            'result' => '{{ text | replace:"mr":"Herr":true }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['result'])->toBe('Hello Herr and Herr Smith');
    });

    it('works in array context', function(): void {
        $source = [
            'items' => [
                ['text' => 'Hello Mr Smith'],
                ['text' => 'Hello Mrs Jones'],
            ],
        ];
        $target = [];

        $mapping = [
            'results.*' => '{{ items.*.text | replace:[Mr,Mrs]:[Herr,Frau] }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['results'])->toBe([
            'Hello Herr Smith',
            'Hello Frau Jones',
        ]);
    });

    it('replaces with special characters', function(): void {
        $source = ['text' => 'Price: $100'];
        $target = [];

        $mapping = [
            'result' => '{{ text | replace:"$":"€" }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['result'])->toBe('Price: €100');
    });

    it('handles numeric values in replacement', function(): void {
        $source = ['text' => 'Version 1.0'];
        $target = [];

        $mapping = [
            'result' => '{{ text | replace:"1.0":"2.0" }}',
        ];

        $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
        expect($result['result'])->toBe('Version 2.0');
    });
});


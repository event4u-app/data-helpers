<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\FluentDataMapper;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;

describe('Fluent API end() Methods', function(): void {
    it('query() returns MapperQuery with end() method', function(): void {
        $mapper = DataMapper::template([
            'users' => [
                '*' => [
                    'name' => '{{ users.*.name }}',
                ],
            ],
        ]);

        // query() returns MapperQuery, end() returns FluentDataMapper
        $result = $mapper->query('users.*')
            ->where('age', '>', 18)
            ->orderBy('name', 'ASC')
            ->limit(10)
            ->end();

        expect($result)->toBeInstanceOf(FluentDataMapper::class);
        expect($result)->toBe($mapper);
    });

    it('property() returns DataMapperProperty with end() method', function(): void {
        $mapper = DataMapper::template([
            'user' => [
                'name' => '{{ name }}',
            ],
        ]);

        // property() returns DataMapperProperty, end() returns FluentDataMapper
        $result = $mapper->property('user.name')
            ->setFilter(new TrimStrings())
            ->end();

        expect($result)->toBeInstanceOf(FluentDataMapper::class);
        expect($result)->toBe($mapper);
    });

    it('reset() returns DataMapperReset with end() method', function(): void {
        $mapper = DataMapper::template([
            'users' => [
                '*' => [
                    'name' => '{{ users.*.name }}',
                ],
                'WHERE' => [
                    '{{ users.*.age }}' => ['>', 18],
                ],
            ],
        ]);

        // reset() returns DataMapperReset, end() returns FluentDataMapper
        $result = $mapper->reset()
            ->where()
            ->end();

        expect($result)->toBeInstanceOf(FluentDataMapper::class);
        expect($result)->toBe($mapper);
    });

    it('delete() returns DataMapperDelete with end() method', function(): void {
        $mapper = DataMapper::template([
            'users' => [
                '*' => [
                    'name' => '{{ users.*.name }}',
                ],
                'WHERE' => [
                    '{{ users.*.age }}' => ['>', 18],
                ],
            ],
        ]);

        // delete() returns DataMapperDelete, end() returns FluentDataMapper
        $result = $mapper->delete()
            ->where()
            ->end();

        expect($result)->toBeInstanceOf(FluentDataMapper::class);
        expect($result)->toBe($mapper);
    });

    it('end() returns the same mapper instance for continued chaining', function(): void {
        $mapper = DataMapper::template([
            'users' => [
                '*' => [
                    'name' => '{{ users.*.name }}',
                ],
            ],
        ]);

        // Verify that end() returns the exact same instance
        $afterQuery = $mapper->query('users.*')->where('age', '>', 18)->end();
        $afterProperty = $mapper->property('users.*.name')->setFilter(new TrimStrings())->end();
        $afterReset = $mapper->reset()->where()->end();
        $afterDelete = $mapper->delete()->where()->end();

        expect($afterQuery)->toBe($mapper);
        expect($afterProperty)->toBe($mapper);
        expect($afterReset)->toBe($mapper);
        expect($afterDelete)->toBe($mapper);
    });
});

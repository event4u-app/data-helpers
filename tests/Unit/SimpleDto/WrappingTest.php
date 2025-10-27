<?php

declare(strict_types=1);

use Tests\Unit\SimpleDto\Fixtures\UserDto;

describe('Wrapping', function(): void {
    describe('Basic Wrapping', function(): void {
        it('wraps Dto in data key', function(): void {
            $user = UserDto::fromArray(['name' => 'John Doe', 'age' => 30]);
            $wrapped = $user->wrap('data')->toArray();

            expect($wrapped)->toHaveKey('data')
                ->and($wrapped['data'])->toHaveKey('name')
                ->and($wrapped['data'])->toHaveKey('age')
                ->and($wrapped['data']['name'])->toBe('John Doe')
                ->and($wrapped['data']['age'])->toBe(30);
        });

        it('wraps Dto in custom key', function(): void {
            $user = UserDto::fromArray(['name' => 'Jane Doe', 'age' => 25]);
            $wrapped = $user->wrap('user')->toArray();

            expect($wrapped)->toHaveKey('user')
                ->and($wrapped['user'])->toHaveKey('name')
                ->and($wrapped['user'])->toHaveKey('age')
                ->and($wrapped['user']['name'])->toBe('Jane Doe')
                ->and($wrapped['user']['age'])->toBe(25);
        });

        it('wraps Dto in result key', function(): void {
            $user = UserDto::fromArray(['name' => 'Bob', 'age' => 35]);
            $wrapped = $user->wrap('result')->toArray();

            expect($wrapped)->toHaveKey('result')
                ->and($wrapped['result']['name'])->toBe('Bob');
        });

        it('does not wrap by default', function(): void {
            $user = UserDto::fromArray(['name' => 'Alice', 'age' => 28]);
            $array = $user->toArray();

            expect($array)->toHaveKey('name')
                ->and($array)->toHaveKey('age')
                ->and($array)->not->toHaveKey('data');
        });
    });

    describe('Wrap Key Methods', function(): void {
        it('returns wrap key', function(): void {
            $user = UserDto::fromArray(['name' => 'John', 'age' => 30]);
            $wrapped = $user->wrap('data');

            expect($wrapped->getWrapKey())->toBe('data');
        });

        it('returns null when not wrapped', function(): void {
            $user = UserDto::fromArray(['name' => 'John', 'age' => 30]);

            expect($user->getWrapKey())->toBeNull();
        });

        it('checks if wrapped', function(): void {
            $user = UserDto::fromArray(['name' => 'John', 'age' => 30]);
            $wrapped = $user->wrap('data');

            expect($user->isWrapped())->toBeFalse()
                ->and($wrapped->isWrapped())->toBeTrue();
        });
    });

    describe('Unwrapping', function(): void {
        it('unwraps data from data key', function(): void {
            $wrappedData = [
                'data' => [
                    'name' => 'John Doe',
                    'age' => 30,
                ],
            ];

            $unwrapped = UserDto::unwrap($wrappedData, 'data');

            expect($unwrapped)->toHaveKey('name')
                ->and($unwrapped)->toHaveKey('age')
                ->and($unwrapped['name'])->toBe('John Doe')
                ->and($unwrapped['age'])->toBe(30);
        });

        it('unwraps data from custom key', function(): void {
            $wrappedData = [
                'user' => [
                    'name' => 'Jane Doe',
                    'age' => 25,
                ],
            ];

            $unwrapped = UserDto::unwrap($wrappedData, 'user');

            expect($unwrapped)->toHaveKey('name')
                ->and($unwrapped['name'])->toBe('Jane Doe');
        });

        it('returns empty array when key not found', function(): void {
            $wrappedData = [
                'other' => [
                    'name' => 'John',
                ],
            ];

            $unwrapped = UserDto::unwrap($wrappedData, 'data');

            expect($unwrapped)->toBeArray()
                ->and($unwrapped)->toBeEmpty();
        });

        it('can create Dto from unwrapped data', function(): void {
            $wrappedData = [
                'data' => [
                    'name' => 'John Doe',
                    'age' => 30,
                ],
            ];

            $unwrapped = UserDto::unwrap($wrappedData, 'data');
            $user = UserDto::fromArray($unwrapped);

            expect($user->name)->toBe('John Doe')
                ->and($user->age)->toBe(30);
        });
    });

    describe('Wrapping with JSON', function(): void {
        it('wraps Dto in JSON', function(): void {
            $user = UserDto::fromArray(['name' => 'John Doe', 'age' => 30]);
            $wrapped = $user->wrap('data');
            $json = json_encode($wrapped);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('data')
                ->and($decoded['data'])->toHaveKey('name')
                ->and($decoded['data']['name'])->toBe('John Doe');
        });

        it('does not wrap JSON by default', function(): void {
            $user = UserDto::fromArray(['name' => 'John Doe', 'age' => 30]);
            $json = json_encode($user);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('name')
                ->and($decoded)->not->toHaveKey('data');
        });
    });

    describe('Wrapping Immutability', function(): void {
        it('does not modify original Dto', function(): void {
            $user = UserDto::fromArray(['name' => 'John', 'age' => 30]);
            $wrapped = $user->wrap('data');

            expect($user->isWrapped())->toBeFalse()
                ->and($wrapped->isWrapped())->toBeTrue()
                ->and($user->getWrapKey())->toBeNull()
                ->and($wrapped->getWrapKey())->toBe('data');
        });

        it('can wrap multiple times with different keys', function(): void {
            $user = UserDto::fromArray(['name' => 'John', 'age' => 30]);
            $wrapped1 = $user->wrap('data');
            $wrapped2 = $user->wrap('user');

            expect($wrapped1->getWrapKey())->toBe('data')
                ->and($wrapped2->getWrapKey())->toBe('user')
                ->and($user->getWrapKey())->toBeNull();
        });
    });

    describe('Edge Cases', function(): void {
        it('handles empty Dto', function(): void {
            $user = UserDto::fromArray(['name' => '', 'age' => 0]);
            $wrapped = $user->wrap('data')->toArray();

            expect($wrapped)->toHaveKey('data')
                ->and($wrapped['data'])->toHaveKey('name')
                ->and($wrapped['data']['name'])->toBe('');
        });

        it('handles wrapping with empty key', function(): void {
            $user = UserDto::fromArray(['name' => 'John', 'age' => 30]);
            $wrapped = $user->wrap('')->toArray();

            expect($wrapped)->toHaveKey('')
                ->and($wrapped[''])->toHaveKey('name');
        });

        it('unwraps with empty key', function(): void {
            $wrappedData = [
                '' => [
                    'name' => 'John',
                    'age' => 30,
                ],
            ];

            $unwrapped = UserDto::unwrap($wrappedData, '');

            expect($unwrapped)->toHaveKey('name')
                ->and($unwrapped['name'])->toBe('John');
        });
    });
});

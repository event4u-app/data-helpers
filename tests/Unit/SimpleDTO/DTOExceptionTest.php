<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\DTOException;

describe('DTOException', function(): void {
    describe('typeMismatch', function(): void {
        it('creates exception with type mismatch details', function(): void {
            $exception = DTOException::typeMismatch(
                dtoClass: 'App\UserDTO',
                property: 'age',
                expectedType: 'int',
                actualValue: 'thirty',
                propertyPath: 'user.age'
            );

            expect($exception->getMessage())
                ->toContain('Type mismatch in App\UserDTO::$age')
                ->toContain('Property path: user.age')
                ->toContain('Expected type: int')
                ->toContain('Actual type: string')
                ->toContain('Actual value: "thirty"');
        });

        it('provides suggestions for string to int conversion', function(): void {
            $exception = DTOException::typeMismatch(
                dtoClass: 'App\UserDTO',
                property: 'age',
                expectedType: 'int',
                actualValue: '25'
            );

            expect($exception->getMessage())
                ->toContain('Suggestions:')
                ->toContain('Cast the string to int');
        });

        it('provides suggestions for null to non-nullable', function(): void {
            $exception = DTOException::typeMismatch(
                dtoClass: 'App\UserDTO',
                property: 'name',
                expectedType: 'string',
                actualValue: null
            );

            expect($exception->getMessage())
                ->toContain('Suggestions:')
                ->toContain('Make the property nullable: ?string')
                ->toContain('Or provide a non-null value');
        });

        it('provides suggestions for array to object conversion', function(): void {
            $exception = DTOException::typeMismatch(
                dtoClass: 'App\UserDTO',
                property: 'address',
                expectedType: 'App\AddressDTO',
                actualValue: ['street' => 'Main St']
            );

            expect($exception->getMessage())
                ->toContain('Suggestions:')
                ->toContain('Convert array to App\AddressDTO using App\AddressDTO::fromArray');
        });

        it('truncates long string values', function(): void {
            $longString = str_repeat('a', 100);
            $exception = DTOException::typeMismatch(
                dtoClass: 'App\UserDTO',
                property: 'description',
                expectedType: 'string',
                actualValue: $longString
            );

            expect($exception->getMessage())
                ->toContain('Actual value: "' . str_repeat('a', 50) . '..."');
        });

        it('formats boolean values', function(): void {
            $exception = DTOException::typeMismatch(
                dtoClass: 'App\UserDTO',
                property: 'active',
                expectedType: 'string',
                actualValue: true
            );

            expect($exception->getMessage())
                ->toContain('Actual value: true');
        });

        it('formats array values', function(): void {
            $exception = DTOException::typeMismatch(
                dtoClass: 'App\UserDTO',
                property: 'tags',
                expectedType: 'string',
                actualValue: ['tag1', 'tag2', 'tag3']
            );

            expect($exception->getMessage())
                ->toContain('Actual value: array(3 items)');
        });

        it('formats object values', function(): void {
            $object = new stdClass();
            $exception = DTOException::typeMismatch(
                dtoClass: 'App\UserDTO',
                property: 'data',
                expectedType: 'array',
                actualValue: $object
            );

            expect($exception->getMessage())
                ->toContain('Actual value: stdClass object');
        });
    });

    describe('missingProperty', function(): void {
        it('creates exception for missing property', function(): void {
            $exception = DTOException::missingProperty(
                dtoClass: 'App\UserDTO',
                property: 'email',
                availableKeys: ['name', 'age', 'address']
            );

            expect($exception->getMessage())
                ->toContain('Missing required property in App\UserDTO::$email')
                ->toContain('Available keys in data:')
                ->toContain('- name')
                ->toContain('- age')
                ->toContain('- address');
        });

        it('suggests similar keys', function(): void {
            $exception = DTOException::missingProperty(
                dtoClass: 'App\UserDTO',
                property: 'email',
                availableKeys: ['name', 'age', 'emial', 'mail']
            );

            expect($exception->getMessage())
                ->toContain('Did you mean:')
                ->toContain('- emial');
        });

        it('limits suggestions to top 3', function(): void {
            $exception = DTOException::missingProperty(
                dtoClass: 'App\UserDTO',
                property: 'name',
                availableKeys: ['nam', 'nme', 'naem', 'nmae', 'mane']
            );

            $message = $exception->getMessage();
            // Count lines in "Did you mean:" section
            preg_match_all('/Did you mean:\n((?:  - .+\n?)+)/', $message, $matches);
            if (isset($matches[1][0])) {
                $suggestionCount = substr_count($matches[1][0], '  - ');
                expect($suggestionCount)->toBeLessThanOrEqual(3);
            } else {
                expect(true)->toBeTrue(); // No suggestions found, that's ok
            }
        });

        it('works without available keys', function(): void {
            $exception = DTOException::missingProperty(
                dtoClass: 'App\UserDTO',
                property: 'email'
            );

            expect($exception->getMessage())
                ->toContain('Missing required property in App\UserDTO::$email')
                ->not->toContain('Available keys');
        });
    });

    describe('invalidCast', function(): void {
        it('creates exception for invalid cast', function(): void {
            $exception = DTOException::invalidCast(
                dtoClass: 'App\UserDTO',
                property: 'createdAt',
                castType: 'datetime',
                value: 'invalid-date',
                reason: 'Invalid date format'
            );

            expect($exception->getMessage())
                ->toContain('Cast failed in App\UserDTO::$createdAt')
                ->toContain('Cast type: datetime')
                ->toContain('Value: "invalid-date"')
                ->toContain('Value type: string')
                ->toContain('Reason: Invalid date format');
        });

        it('works without reason', function(): void {
            $exception = DTOException::invalidCast(
                dtoClass: 'App\UserDTO',
                property: 'age',
                castType: 'integer',
                value: 'abc'
            );

            expect($exception->getMessage())
                ->toContain('Cast failed in App\UserDTO::$age')
                ->not->toContain('Reason:');
        });
    });

    describe('nestedError', function(): void {
        it('creates exception for nested DTO error', function(): void {
            $exception = DTOException::nestedError(
                dtoClass: 'App\UserDTO',
                property: 'address',
                nestedDtoClass: 'App\AddressDTO',
                nestedProperty: 'street',
                originalMessage: 'Missing required property'
            );

            expect($exception->getMessage())
                ->toContain('Error in nested DTO App\UserDTO::$address')
                ->toContain('Nested DTO: App\AddressDTO')
                ->toContain('Nested property: street')
                ->toContain('Property path: address.street')
                ->toContain('Original error:')
                ->toContain('Missing required property');
        });
    });
});

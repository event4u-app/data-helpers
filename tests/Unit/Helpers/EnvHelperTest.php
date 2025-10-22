<?php

declare(strict_types=1);

use event4u\DataHelpers\Helpers\EnvHelper;

describe('EnvHelper::get()', function(): void {
    afterEach(function(): void {
        unset($_ENV['TEST_VAR']);
    });

    it('returns value from $_ENV', function(): void {
        $_ENV['TEST_VAR'] = 'test-value';

        expect(EnvHelper::get('TEST_VAR'))->toBe('test-value');
    });

    it('returns default when key does not exist', function(): void {
        expect(EnvHelper::get('NON_EXISTENT', 'default'))->toBe('default');
    });

    it('returns null when key does not exist and no default', function(): void {
        expect(EnvHelper::get('NON_EXISTENT'))->toBeNull();
    });

    it('returns empty string when set to empty string', function(): void {
        $_ENV['TEST_VAR'] = '';

        expect(EnvHelper::get('TEST_VAR'))->toBe('');
    });

    it('returns zero when set to zero', function(): void {
        $_ENV['TEST_VAR'] = '0';

        expect(EnvHelper::get('TEST_VAR'))->toBe('0');
    });
});

describe('EnvHelper::string()', function(): void {
    afterEach(function(): void {
        unset($_ENV['TEST_STRING']);
    });

    it('returns string value', function(): void {
        $_ENV['TEST_STRING'] = 'hello world';

        expect(EnvHelper::string('TEST_STRING'))->toBe('hello world');
    });

    it('casts numeric string to string', function(): void {
        $_ENV['TEST_STRING'] = '123';

        expect(EnvHelper::string('TEST_STRING'))->toBe('123');
    });

    it('uses default value when key does not exist', function(): void {
        expect(EnvHelper::string('NON_EXISTENT', 'default'))->toBe('default');
    });

    it('returns empty string for null value', function(): void {
        expect(EnvHelper::string('NON_EXISTENT', null))->toBe('');
    });
});

describe('EnvHelper::integer()', function(): void {
    afterEach(function(): void {
        unset($_ENV['TEST_INT']);
    });

    it('casts numeric string to integer', function(): void {
        $_ENV['TEST_INT'] = '123';

        expect(EnvHelper::integer('TEST_INT'))->toBe(123);
    });

    it('casts float string to integer', function(): void {
        $_ENV['TEST_INT'] = '123.99';

        expect(EnvHelper::integer('TEST_INT'))->toBe(123);
    });

    it('uses default value when key does not exist', function(): void {
        expect(EnvHelper::integer('NON_EXISTENT', 999))->toBe(999);
    });

    it('handles negative integers', function(): void {
        $_ENV['TEST_INT'] = '-42';

        expect(EnvHelper::integer('TEST_INT'))->toBe(-42);
    });

    it('handles zero', function(): void {
        $_ENV['TEST_INT'] = '0';

        expect(EnvHelper::integer('TEST_INT'))->toBe(0);
    });
});

describe('EnvHelper::float()', function(): void {
    afterEach(function(): void {
        unset($_ENV['TEST_FLOAT']);
    });

    it('casts numeric string to float', function(): void {
        $_ENV['TEST_FLOAT'] = '123.45';

        expect(EnvHelper::float('TEST_FLOAT'))->toBe(123.45);
    });

    it('casts integer to float', function(): void {
        $_ENV['TEST_FLOAT'] = '42';

        expect(EnvHelper::float('TEST_FLOAT'))->toBe(42.0);
    });

    it('uses default value when key does not exist', function(): void {
        expect(EnvHelper::float('NON_EXISTENT', 9.99))->toBe(9.99);
    });

    it('handles negative floats', function(): void {
        $_ENV['TEST_FLOAT'] = '-3.14';

        expect(EnvHelper::float('TEST_FLOAT'))->toBe(-3.14);
    });

    it('handles scientific notation', function(): void {
        $_ENV['TEST_FLOAT'] = '1.23e-4';

        expect(EnvHelper::float('TEST_FLOAT'))->toBe(0.000123);
    });
});

describe('EnvHelper::boolean()', function(): void {
    afterEach(function(): void {
        unset($_ENV['TEST_BOOL']);
    });

    it('casts string "true" to boolean true', function(): void {
        $_ENV['TEST_BOOL'] = 'true';

        expect(EnvHelper::boolean('TEST_BOOL'))->toBeTrue();
    });

    it('casts string "TRUE" to boolean true (case insensitive)', function(): void {
        $_ENV['TEST_BOOL'] = 'TRUE';

        expect(EnvHelper::boolean('TEST_BOOL'))->toBeTrue();
    });

    it('casts string "false" to boolean false', function(): void {
        $_ENV['TEST_BOOL'] = 'false';

        expect(EnvHelper::boolean('TEST_BOOL'))->toBeFalse();
    });

    it('casts string "anything" to boolean false', function(): void {
        $_ENV['TEST_BOOL'] = 'anything';

        expect(EnvHelper::boolean('TEST_BOOL'))->toBeFalse();
    });

    it('casts string "1" to boolean true', function(): void {
        $_ENV['TEST_BOOL'] = '1';

        expect(EnvHelper::boolean('TEST_BOOL'))->toBeTrue();
    });

    it('casts string "0" to boolean false', function(): void {
        $_ENV['TEST_BOOL'] = '0';

        expect(EnvHelper::boolean('TEST_BOOL'))->toBeFalse();
    });

    it('uses default value when key does not exist', function(): void {
        expect(EnvHelper::boolean('NON_EXISTENT', true))->toBeTrue();
    });
});

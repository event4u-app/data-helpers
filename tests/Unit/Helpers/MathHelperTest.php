<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\MathException;
use event4u\DataHelpers\Helpers\MathHelper;

describe('MathHelper - Basic Arithmetic', function(): void {
    describe('add()', function(): void {
        it('adds positive numbers', function(): void {
            expect(MathHelper::add(10, 5))->toBe(15.0);
            expect(MathHelper::add(5, 3))->toBe(8.0);
        });

        it('adds negative numbers', function(): void {
            expect(MathHelper::add(-10, -5))->toBe(-15.0);
            expect(MathHelper::add(-5, 3))->toBe(-2.0);
        });

        it('adds decimals', function(): void {
            expect(MathHelper::add(10.5, 5.3))->toBe(15.8);
            expect(MathHelper::add(2.5, 3.5))->toBe(6.0);
        });

        it('adds string numbers', function(): void {
            expect(MathHelper::add('5.5', '3.5'))->toBe(9.0);
        });

        it('handles null values', function(): void {
            expect(MathHelper::add(null, 5))->toBe(5.0);
            expect(MathHelper::add('', 5))->toBe(5.0);
        });

        it('handles scientific notation', function(): void {
            expect(MathHelper::add('3.8773213097356E-12', 1))->toBeGreaterThan(0);
        });

        it('throws exception for malformed input by default', function(): void {
            MathHelper::add('not_a_number', 5);
        })->throws(MathException::class);

        it('converts malformed input to zero when configured', function(): void {
            MathHelper::setConvertMalformedInputToZero(true);
            expect(MathHelper::add('not_a_number', 5))->toBe(5.0);
            MathHelper::setConvertMalformedInputToZero(false);
        });
    });

    describe('subtract()', function(): void {
        it('subtracts positive numbers', function(): void {
            expect(MathHelper::subtract(10, 5))->toBe(5.0);
            expect(MathHelper::subtract(10, 4))->toBe(6.0);
        });

        it('subtracts negative numbers', function(): void {
            expect(MathHelper::subtract(-5, -3))->toBe(-2.0);
        });

        it('subtracts decimals', function(): void {
            expect(MathHelper::subtract(10.5, 5.3))->toBe(5.2);
            expect(abs(MathHelper::subtract(5.5, 2.3) - 3.2) < 0.001)->toBeTrue();
        });

        it('throws exception for malformed input', function(): void {
            MathHelper::subtract('abc', 5);
        })->throws(MathException::class);

        it('converts malformed input to zero when configured', function(): void {
            MathHelper::setConvertMalformedInputToZero(true);
            expect(MathHelper::subtract('invalid', 5))->toBe(-5.0);
            MathHelper::setConvertMalformedInputToZero(false);
        });
    });

    describe('multiply()', function(): void {
        it('multiplies positive numbers', function(): void {
            expect(MathHelper::multiply(10, 5))->toBe(50.0);
            expect(MathHelper::multiply(5, 3))->toBe(15.0);
        });

        it('multiplies decimals', function(): void {
            expect(MathHelper::multiply(2.5, 4))->toBe(10.0);
        });

        it('multiplies negative numbers', function(): void {
            expect(MathHelper::multiply(-2, 3))->toBe(-6.0);
        });

        it('multiplies with zero', function(): void {
            expect(MathHelper::multiply(10, 0))->toBe(0.0);
        });

        it('throws exception for malformed input', function(): void {
            MathHelper::multiply('invalid', 5);
        })->throws(MathException::class);

        it('converts malformed input to zero when configured', function(): void {
            MathHelper::setConvertMalformedInputToZero(true);
            expect(MathHelper::multiply('invalid', 5))->toBe(0.0);
            MathHelper::setConvertMalformedInputToZero(false);
        });
    });

    describe('divide()', function(): void {
        it('divides positive numbers', function(): void {
            expect(MathHelper::divide(10, 5))->toBe(2.0);
            expect(MathHelper::divide(10, 2))->toBe(5.0);
        });

        it('divides with decimals', function(): void {
            expect(MathHelper::divide(5, 2))->toBe(2.5);
        });

        it('throws exception on division by zero', function(): void {
            MathHelper::divide(10, 0);
        })->throws(MathException::class);

        it('returns zero when division by zero and exception disabled', function(): void {
            expect(MathHelper::divide(10, 0, MathHelper::DEFAULT_SCALE, false))->toBe(0.0);
        });
    });

    describe('modulo()', function(): void {
        it('calculates modulo', function(): void {
            expect(MathHelper::modulo(10, 3))->toBe(1.0);
        });

        it('throws exception on modulo by zero', function(): void {
            MathHelper::modulo(10, 0);
        })->throws(MathException::class);
    });

    describe('powerOf()', function(): void {
        it('calculates power', function(): void {
            expect(MathHelper::powerOf(2, 3))->toBe(8.0);
            expect(MathHelper::powerOf(5, 2))->toBe(25.0);
        });

        it('handles edge cases', function(): void {
            expect(MathHelper::powerOf(0, 0))->toBe(1.0);
        });
    });

    describe('squareRoot()', function(): void {
        it('calculates square root', function(): void {
            expect(MathHelper::squareRoot(16))->toBe(4.0);
            expect(abs(MathHelper::squareRoot(2) - 1.414) < 0.001)->toBeTrue();
        });

        it('handles zero', function(): void {
            expect(MathHelper::squareRoot(0))->toBe(0.0);
        });

        it('throws exception for negative numbers', function(): void {
            MathHelper::squareRoot(-1);
        })->throws(MathException::class);
    });

    describe('compare()', function(): void {
        it('returns 0 when numbers are equal', function(): void {
            expect(MathHelper::compare(10, 10))->toBe(0);
            expect(MathHelper::compare(5, 5))->toBe(0);
        });

        it('returns 1 when first number is greater', function(): void {
            expect(MathHelper::compare(10, 5))->toBe(1);
            expect(MathHelper::compare(5, 3))->toBe(1);
        });

        it('returns -1 when first number is smaller', function(): void {
            expect(MathHelper::compare(5, 10))->toBe(-1);
            expect(MathHelper::compare(3, 5))->toBe(-1);
        });
    });
});

describe('MathHelper - Array Operations', function(): void {
    describe('min()', function(): void {
        it('finds minimum value', function(): void {
            expect(MathHelper::min([10, 5, 20, 3]))->toBe(3);
            expect(MathHelper::min([5, 2, 8, 1, 9]))->toBe(1);
        });

        it('finds minimum with decimals', function(): void {
            expect(MathHelper::min([5.5, 2.3, 8.1, 1.2, 9.7]))->toBe(1.2);
        });

        it('returns zero for empty array', function(): void {
            expect(MathHelper::min([]))->toBe(0);
        });
    });

    describe('max()', function(): void {
        it('finds maximum value', function(): void {
            expect(MathHelper::max([10, 5, 20, 3]))->toBe(20);
            expect(MathHelper::max([5, 2, 8, 1, 9]))->toBe(9);
        });

        it('finds maximum with decimals', function(): void {
            expect(MathHelper::max([5.5, 2.3, 8.1, 1.2, 9.7]))->toBe(9.7);
        });

        it('returns zero for empty array', function(): void {
            expect(MathHelper::max([]))->toBe(0);
        });
    });

    describe('sum()', function(): void {
        it('calculates sum', function(): void {
            expect(MathHelper::sum([10, 5, 20, 3]))->toBe(38.0);
            expect(MathHelper::sum([1, 2, 3, 4, 5]))->toBe(15.0);
        });

        it('calculates sum with decimals', function(): void {
            expect(MathHelper::sum([1.5, 2.5, 3.5]))->toBe(7.5);
        });

        it('returns zero for empty array', function(): void {
            expect(MathHelper::sum([]))->toBe(0.0);
        });
    });

    describe('subSum()', function(): void {
        it('calculates subtraction sum', function(): void {
            expect(MathHelper::subSum([10, 5, 3]))->toBe(-18.0);
        });

        it('handles null values', function(): void {
            expect(MathHelper::subSum([10, null, 5]))->toBe(-15.0);
        });
    });

    describe('average()', function(): void {
        it('calculates average', function(): void {
            expect(MathHelper::average([10, 20, 30]))->toBe(20.0);
            expect(MathHelper::average([1, 2, 3, 4, 5]))->toBe(3.0);
        });

        it('calculates average with decimals', function(): void {
            expect(MathHelper::average([1.5, 2.5, 3.5]))->toBe(2.5);
        });

        it('returns zero for empty array', function(): void {
            expect(MathHelper::average([]))->toBe(0.0);
        });
    });

    describe('product()', function(): void {
        it('calculates product', function(): void {
            expect(MathHelper::product([2, 3, 4]))->toBe(24.0);
        });

        it('calculates product with decimals', function(): void {
            expect(MathHelper::product([1.5, 2, 3]))->toBe(9.0);
        });

        it('returns 1 for empty array', function(): void {
            expect(MathHelper::product([]))->toBe(1.0);
        });
    });
});

describe('MathHelper - Time Conversions', function(): void {
    describe('convertMinutesToDecimalHours()', function(): void {
        it('converts to decimal hours as string', function(): void {
            expect(MathHelper::convertMinutesToDecimalHours(60))->toBe('1');
            expect(MathHelper::convertMinutesToDecimalHours(90, 2))->toBe('1.50');
        });
    });

    describe('convertMinutesToDecimalHoursAsFloat()', function(): void {
        it('converts to decimal hours as float', function(): void {
            expect(MathHelper::convertMinutesToDecimalHoursAsFloat(60))->toBe(1.0);
            expect(MathHelper::convertMinutesToDecimalHoursAsFloat(120, 2))->toBe(2.0);
            expect(MathHelper::convertMinutesToDecimalHoursAsFloat(90, 2))->toBe(1.5);
        });

        it('throws exception for invalid input', function(): void {
            MathHelper::convertMinutesToDecimalHoursAsFloat('not_a_number');
        })->throws(MathException::class);

        it('converts malformed input to zero when configured', function(): void {
            MathHelper::setConvertMalformedInputToZero(true);
            expect(MathHelper::convertMinutesToDecimalHoursAsFloat('invalid'))->toBe(0.0);
            MathHelper::setConvertMalformedInputToZero(false);
        });
    });

    describe('convertMinutesToDecimalHoursRounded()', function(): void {
        it('converts and rounds to decimal hours', function(): void {
            expect(MathHelper::convertMinutesToDecimalHoursRounded(90))->toBe(2.0);
            expect(MathHelper::convertMinutesToDecimalHoursRounded(125))->toBe(2.0);
            expect(MathHelper::convertMinutesToDecimalHoursRounded(125, 1))->toBe(2.1);
        });
    });

    describe('convertMinutesToHourMinutes()', function(): void {
        it('converts to HH:MM format', function(): void {
            expect(MathHelper::convertMinutesToHourMinutes(125))->toBe('02:05');
            expect(MathHelper::convertMinutesToHourMinutes(60))->toBe('01:00');
            expect(MathHelper::convertMinutesToHourMinutes(90))->toBe('01:30');
        });

        it('handles zero', function(): void {
            expect(MathHelper::convertMinutesToHourMinutes(0))->toBe('00:00');
        });
    });

    describe('convertHoursMinutesToMinutes()', function(): void {
        it('converts HH:MM to minutes', function(): void {
            expect(MathHelper::convertHoursMinutesToMinutes('02:05'))->toBe(125);
            expect(MathHelper::convertHoursMinutesToMinutes('01:00'))->toBe(60);
            expect(MathHelper::convertHoursMinutesToMinutes('01:30'))->toBe(90);
        });

        it('handles zero', function(): void {
            expect(MathHelper::convertHoursMinutesToMinutes('00:00'))->toBe(0);
        });
    });

    describe('convertDecimalHoursToSeconds()', function(): void {
        it('converts to seconds', function(): void {
            expect(MathHelper::convertDecimalHoursToSeconds(1))->toBe(3600.0);
            expect(MathHelper::convertDecimalHoursToSeconds(1.5))->toBe(5400.0);
        });
    });

    describe('convertDecimalHoursToSecondsRounded()', function(): void {
        it('converts and rounds to seconds', function(): void {
            expect(MathHelper::convertDecimalHoursToSecondsRounded(1.5))->toBe(5400.0);
        });
    });
});

describe('MathHelper - Edge Cases & Error Handling', function(): void {
    it('handles extreme scale inputs', function(): void {
        $largeNumber = '1' . str_repeat('0', 100);
        expect(fn(): float => MathHelper::add($largeNumber, 1))->not->toThrow(MathException::class);
    });

    it('throws exception on number overflow', function(): void {
        $hugeNumber = PHP_FLOAT_MAX * 10000000;
        expect(fn(): float => MathHelper::add($hugeNumber, $hugeNumber))->toThrow(MathException::class);
    });

    it('MathException contains error data', function(): void {
        try {
            MathHelper::divide(10, 0);
            expect(true)->toBeFalse();
        } catch (MathException $mathException) {
            expect($mathException->getData())->toHaveKey('method');
            expect($mathException->getData())->toHaveKey('num1');
            expect($mathException->getData())->toHaveKey('num2');
        }
    });
});

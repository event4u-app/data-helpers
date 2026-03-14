<?php

declare(strict_types=1);

namespace Tests\Unit\DataMapper\Template;

use DateTimeImmutable;
use DateTimeZone;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;

describe('Template Expressions', function(): void {
    it('evaluates simple expression', function(): void {
        $template = [
            'fullname' => '{{ user.name }}',
        ];

        $sources = [
            'user' => ['name' => 'Alice'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['fullname'])->toBe('Alice');
    });

    it('evaluates expression with default value', function(): void {
        $template = [
            'fullname' => "{{ user.name ?? 'Unknown' }}",
        ];

        $sources = [
            'user' => ['email' => 'alice@example.com'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['fullname'])->toBe('Unknown');
    });

    it('evaluates expression with filter', function(): void {
        $template = [
            'email' => '{{ user.email | lower }}',
        ];

        $sources = [
            'user' => ['email' => 'ALICE@EXAMPLE.COM'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['email'])->toBe('alice@example.com');
    });

    it('evaluates expression with multiple filters', function(): void {
        $template = [
            'name' => '{{ user.name | lower | ucfirst }}',
        ];

        $sources = [
            'user' => ['name' => 'ALICE'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['name'])->toBe('Alice');
    });

    it('evaluates alias reference', function(): void {
        $template = [
            'fullname' => '{{ user.name }}',
            'copy' => '{{ @fullname }}',
        ];

        $sources = [
            'user' => ['name' => 'Alice'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['fullname'])->toBe('Alice');
        expect($result['copy'])->toBe('Alice'); // Copies value from 'fullname' in target
    });

    it('evaluates unordered alias reference', function(): void {
        $template = [
            'copy' => '{{ @fullname }}',
            'fullname' => '{{ user.name }}',
        ];

        $sources = [
            'user' => ['name' => 'Alice'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['fullname'])->toBe('Alice');
        expect($result['copy'])->toBe('Alice'); // Copies value from 'fullname' in target
    });

    it('distinguishes between source, target alias and static values', function(): void {
        $template = [
            'name' => '{{ user.name }}',           // Source reference
            'copyName' => '{{ @name }}',           // Target alias reference
            'staticValue' => 'hardcoded',          // Static string
            'anotherStatic' => 'user.name',        // Static string (looks like path but no {{ }})
        ];

        $sources = [
            'user' => ['name' => 'Alice'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['name'])->toBe('Alice');              // From source
        expect($result['copyName'])->toBe('Alice');          // Copied from target 'name'
        expect($result['staticValue'])->toBe('hardcoded');   // Static string
        expect($result['anotherStatic'])->toBe('user.name'); // Static string (not resolved)
    });

    it('handles nested alias references', function(): void {
        $template = [
            'firstName' => '{{ user.firstName }}',
            'lastName' => '{{ user.lastName }}',
            'copyFirstName' => '{{ @firstName }}',
            'copyLastName' => '{{ @lastName }}',
        ];

        $sources = [
            'user' => ['firstName' => 'Alice', 'lastName' => 'Smith'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['firstName'])->toBe('Alice');
        expect($result['lastName'])->toBe('Smith');
        expect($result['copyFirstName'])->toBe('Alice');  // Copied from target 'firstName'
        expect($result['copyLastName'])->toBe('Smith');   // Copied from target 'lastName'
    });

    it('combines expressions with regular references', function(): void {
        $template = [
            'name' => '{{ user.name | upper }}',
            'email' => '{{ user.email }}',
            'city' => '{{ address.city ?? "Unknown" }}',
        ];

        $sources = [
            'user' => ['name' => 'alice', 'email' => 'alice@example.com'],
            'address' => [],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['name'])->toBe('ALICE');
        expect($result['email'])->toBe('alice@example.com');
        expect($result['city'])->toBe('Unknown');
    });

    it('formats DateTime with date filter using default format', function(): void {
        $template = [
            'created' => '{{ event.created | date }}',
        ];

        $sources = [
            'event' => ['created' => new DateTimeImmutable('2024-01-15 10:30:00')],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['created'])->toBe('2024-01-15 10:30:00');
    });

    it('formats DateTime with date filter and custom format', function(): void {
        $template = [
            'date' => '{{ event.created | date:"Y-m-d" }}',
        ];

        $sources = [
            'event' => ['created' => new DateTimeImmutable('2024-01-15 10:30:00')],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['date'])->toBe('2024-01-15');
    });

    it('formats DateTime with date filter and German format', function(): void {
        $template = [
            'date' => '{{ event.created | date:"d.m.Y" }}',
        ];

        $sources = [
            'event' => ['created' => new DateTimeImmutable('2024-01-15')],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['date'])->toBe('15.01.2024');
    });

    it('formats date string with date filter', function(): void {
        $template = [
            'date' => '{{ event.created | date:"Y-m-d" }}',
        ];

        $sources = [
            'event' => ['created' => '2024-01-15 10:30:00'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['date'])->toBe('2024-01-15');
    });

    it('formats unix timestamp with date filter', function(): void {
        $template = [
            'date' => '{{ event.created | date:"Y-m-d" }}',
        ];

        $sources = [
            'event' => ['created' => 1705276800], // 2024-01-15 00:00:00 UTC
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        $expected = (new DateTimeImmutable('@1705276800'))->format('Y-m-d');
        expect($result['date'])->toBe($expected);
    });

    it('converts DateTime to timestamp', function(): void {
        $dt = new DateTimeImmutable('2024-01-15 00:00:00', new DateTimeZone('UTC'));

        $template = [
            'ts' => '{{ event.created | timestamp }}',
        ];

        $sources = [
            'event' => ['created' => $dt],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['ts'])->toBe($dt->getTimestamp());
    });

    it('converts date string to timestamp', function(): void {
        $template = [
            'ts' => '{{ event.created | timestamp }}',
        ];

        $sources = [
            'event' => ['created' => '2024-01-15 00:00:00'],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        $expected = (new DateTimeImmutable('2024-01-15 00:00:00'))->getTimestamp();
        expect($result['ts'])->toBe($expected);
    });

    it('passes through int value in timestamp filter', function(): void {
        $template = [
            'ts' => '{{ event.created | timestamp }}',
        ];

        $sources = [
            'event' => ['created' => 1705276800],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['ts'])->toBe(1705276800);
    });

    it('skips null values in date and timestamp filters', function(): void {
        $template = [
            'date' => '{{ event.created | date:"Y-m-d" }}',
            'ts' => '{{ event.created | timestamp }}',
        ];

        $sources = [
            'event' => ['created' => null],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        // Null source values are not written to target (general DataMapper behavior)
        expect($result)->not->toHaveKey('date');
        expect($result)->not->toHaveKey('ts');
    });

    it('uses date_format alias', function(): void {
        $template = [
            'date' => '{{ event.created | date_format:"Y-m-d" }}',
        ];

        $sources = [
            'event' => ['created' => new DateTimeImmutable('2024-01-15 10:30:00')],
        ];

        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['date'])->toBe('2024-01-15');
    });
});

// =============================================================================
// InList / NotInList Filters
// =============================================================================

describe('InList filter (| in)', function(): void {
    beforeEach(function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);
        MapperExceptions::clearExceptions();
    });

    afterEach(function(): void {
        MapperExceptions::clearExceptions();
        MapperExceptions::setCollectExceptionsEnabled(false);
    });

    it('passes through value that is in the allowed list', function(): void {
        $template = [
            'type' => '{{ item.type | in:[VEHICLE,ORDER,PROJECT] }}',
        ];

        $sources = ['item' => ['type' => 'VEHICLE']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['type'])->toBe('VEHICLE');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('returns null and collects exception for value not in list', function(): void {
        $template = [
            'type' => '{{ item.type | in:[VEHICLE,ORDER,PROJECT] }}',
        ];

        $sources = ['item' => ['type' => 'UNKNOWN']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['type'])->toBeNull();
        expect(MapperExceptions::hasExceptions())->toBeTrue();
    });

    it('returns null and collects exception for empty string without optional', function(): void {
        $template = [
            'type' => '{{ item.type | in:[VEHICLE,ORDER] }}',
        ];

        $sources = ['item' => ['type' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['type'])->toBeNull();
        expect(MapperExceptions::hasExceptions())->toBeTrue();
    });

    it('returns null without error for empty string with optional flag', function(): void {
        $template = [
            'type' => '{{ item.type | in:[VEHICLE,ORDER]:optional }}',
        ];

        $sources = ['item' => ['type' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['type'])->toBeNull();
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('works with chained filters like string and upper', function(): void {
        $template = [
            'type' => '{{ item.type | string | upper | in:[VEHICLE,ORDER,PROJECT] }}',
        ];

        $sources = ['item' => ['type' => 'vehicle']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['type'])->toBe('VEHICLE');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('uses in_list alias', function(): void {
        $template = [
            'type' => '{{ item.type | in_list:[ACTIVE,INACTIVE] }}',
        ];

        $sources = ['item' => ['type' => 'ACTIVE']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['type'])->toBe('ACTIVE');
    });
});

describe('NotInList filter (| not_in)', function(): void {
    beforeEach(function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);
        MapperExceptions::clearExceptions();
    });

    afterEach(function(): void {
        MapperExceptions::clearExceptions();
        MapperExceptions::setCollectExceptionsEnabled(false);
    });

    it('passes through value that is not in the blocked list', function(): void {
        $template = [
            'status' => '{{ item.status | not_in:[DELETED,ARCHIVED] }}',
        ];

        $sources = ['item' => ['status' => 'ACTIVE']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['status'])->toBe('ACTIVE');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('returns null and collects exception for value in blocked list', function(): void {
        $template = [
            'status' => '{{ item.status | not_in:[DELETED,ARCHIVED] }}',
        ];

        $sources = ['item' => ['status' => 'DELETED']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['status'])->toBeNull();
        expect(MapperExceptions::hasExceptions())->toBeTrue();
    });

    it('returns null without error for empty string with optional flag', function(): void {
        $template = [
            'status' => '{{ item.status | not_in:[DELETED,ARCHIVED]:optional }}',
        ];

        $sources = ['item' => ['status' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['status'])->toBeNull();
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('uses not_in_list alias', function(): void {
        $template = [
            'status' => '{{ item.status | not_in_list:[DELETED] }}',
        ];

        $sources = ['item' => ['status' => 'ACTIVE']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['status'])->toBe('ACTIVE');
    });
});

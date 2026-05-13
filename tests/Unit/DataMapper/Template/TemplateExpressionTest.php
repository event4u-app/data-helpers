<?php

declare(strict_types=1);

namespace Tests\Unit\DataMapper\Template;

use DateTimeImmutable;
use DateTimeZone;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\Support\MappingFacade;

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

        expect($result)->not->toHaveKey('type');
        expect(MapperExceptions::hasExceptions())->toBeTrue();
    });

    it('returns null and collects exception for empty string without optional', function(): void {
        $template = [
            'type' => '{{ item.type | in:[VEHICLE,ORDER] }}',
        ];

        $sources = ['item' => ['type' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('type');
        expect(MapperExceptions::hasExceptions())->toBeTrue();
    });

    it('returns null without error for empty string with optional flag', function(): void {
        $template = [
            'type' => '{{ item.type | in:[VEHICLE,ORDER]:optional }}',
        ];

        $sources = ['item' => ['type' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('type');
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

        expect($result)->not->toHaveKey('status');
        expect(MapperExceptions::hasExceptions())->toBeTrue();
    });

    it('returns null without error for empty string with optional flag', function(): void {
        $template = [
            'status' => '{{ item.status | not_in:[DELETED,ARCHIVED]:optional }}',
        ];

        $sources = ['item' => ['status' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('status');
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

    // ── required / not_required / optional meta-flags ──────────────────

    it('passes value through when required and value is present', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.name | required | string }}',
        ];

        $sources = ['user' => ['name' => 'Alice']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['name'])->toBe('Alice');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('throws exception when required and value is null', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.name | required | string }}',
        ];

        $sources = ['user' => ['name' => null]];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('name');
        expect(MapperExceptions::hasExceptions())->toBeTrue();
        expect(MapperExceptions::getExceptions()[0]->getMessage())
            ->toContain('required');

        MapperExceptions::clearExceptions();
    });

    it('throws exception when required and value is empty string', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.name | required | string }}',
        ];

        $sources = ['user' => ['name' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('name');
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        MapperExceptions::clearExceptions();
    });

    it('returns null without error when not_required and value is null', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.name | not_required | string }}',
        ];

        $sources = ['user' => ['name' => null]];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('name');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('returns null without error when optional and value is empty string', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.name | optional | string }}',
        ];

        $sources = ['user' => ['name' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('name');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('handles required at end of filter chain (position-independent)', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.name | string | upper | required }}',
        ];

        $sources = ['user' => ['name' => 'Alice']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['name'])->toBe('ALICE');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('handles not_required at end of filter chain (position-independent)', function(): void {
        MapperExceptions::reset();

        $template = [
            'type' => '{{ item.type | string | upper | in:[VEHICLE,ORDER] | not_required }}',
        ];

        $sources = ['item' => ['type' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('type');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('combines required with in filter', function(): void {
        MapperExceptions::reset();

        $template = [
            'type' => '{{ item.type | in:[VEHICLE,ORDER] | required }}',
        ];

        $sources = ['item' => ['type' => null]];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('type');
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        MapperExceptions::clearExceptions();
    });

    it('combines not_required with in filter and valid value', function(): void {
        MapperExceptions::reset();

        $template = [
            'type' => '{{ item.type | string | upper | in:[VEHICLE,ORDER] | not_required }}',
        ];

        $sources = ['item' => ['type' => 'vehicle']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['type'])->toBe('VEHICLE');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('treats whitespace-only string as empty when required', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.name | required }}',
        ];

        $sources = ['user' => ['name' => '   ']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('name');
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        MapperExceptions::clearExceptions();
    });

    it('treats undefined source path as null when required', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.missing_field | required }}',
        ];

        $sources = ['user' => ['name' => 'Alice']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('name');
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        MapperExceptions::clearExceptions();
    });

    it('works with required as only filter', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.name | required }}',
        ];

        $sources = ['user' => ['name' => 'Alice']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['name'])->toBe('Alice');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('passes integer 0 through when required', function(): void {
        MapperExceptions::reset();

        $template = [
            'count' => '{{ item.count | required }}',
        ];

        $sources = ['item' => ['count' => 0]];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['count'])->toBe(0);
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('passes boolean false through when required', function(): void {
        MapperExceptions::reset();

        $template = [
            'active' => '{{ item.active | required }}',
        ];

        $sources = ['item' => ['active' => false]];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['active'])->toBeFalse();
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('last meta-flag wins when both required and not_required are present', function(): void {
        MapperExceptions::reset();

        // not_required comes last → should win
        $template = [
            'name' => '{{ user.name | required | string | not_required }}',
        ];

        $sources = ['user' => ['name' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('name');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('uses optional alias with null value', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.name | optional }}',
        ];

        $sources = ['user' => ['name' => null]];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('name');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('throws exception for required with in filter and invalid value', function(): void {
        MapperExceptions::reset();

        $template = [
            'type' => '{{ item.type | string | upper | in:[VEHICLE,ORDER] | required }}',
        ];

        // INVALID is not in the allowed list → in filter throws, required is irrelevant here
        $sources = ['item' => ['type' => 'INVALID']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('type');
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        MapperExceptions::clearExceptions();
    });

    // ── in / not_in edge cases ─────────────────────────────────────────

    it('in filter is case-sensitive', function(): void {
        MapperExceptions::reset();

        $template = [
            'type' => '{{ item.type | in:[VEHICLE,ORDER] }}',
        ];

        // lowercase "vehicle" is not in [VEHICLE,ORDER]
        $sources = ['item' => ['type' => 'vehicle']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('type');
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        MapperExceptions::clearExceptions();
    });

    it('in filter with empty list rejects any value', function(): void {
        MapperExceptions::reset();

        $template = [
            'type' => '{{ item.type | in:[] }}',
        ];

        $sources = ['item' => ['type' => 'VEHICLE']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('type');
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        MapperExceptions::clearExceptions();
    });

    it('in filter with single value', function(): void {
        MapperExceptions::reset();

        $template = [
            'type' => '{{ item.type | in:[VEHICLE] }}',
        ];

        $sources = ['item' => ['type' => 'VEHICLE']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['type'])->toBe('VEHICLE');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('in filter with numeric values', function(): void {
        MapperExceptions::reset();

        $template = [
            'status' => '{{ item.status | in:[1,2,3] }}',
        ];

        $sources = ['item' => ['status' => 2]];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['status'])->toBe(2);
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('not_in filter supports not_required alias', function(): void {
        MapperExceptions::reset();

        $template = [
            'status' => '{{ item.status | not_in:[DELETED,ARCHIVED]:not_required }}',
        ];

        $sources = ['item' => ['status' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('status');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('in filter with global not_required and filter-level optional together', function(): void {
        MapperExceptions::reset();

        $template = [
            'type' => '{{ item.type | in:[VEHICLE,ORDER]:optional | not_required }}',
        ];

        $sources = ['item' => ['type' => '']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('type');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('in filter treats whitespace-only string as empty without optional', function(): void {
        MapperExceptions::reset();

        $template = [
            'type' => '{{ item.type | in:[VEHICLE,ORDER] }}',
        ];

        $sources = ['item' => ['type' => '   ']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result)->not->toHaveKey('type');
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        MapperExceptions::clearExceptions();
    });

    // ── additional required / not_required edge cases ──────────────────

    it('passes empty array through when required', function(): void {
        MapperExceptions::reset();

        $template = [
            'tags' => '{{ item.tags | required }}',
        ];

        // Empty array is not null and not a string → should pass through
        $sources = ['item' => ['tags' => []]];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['tags'])->toBe([]);
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('passes value through with not_required when value is present', function(): void {
        MapperExceptions::reset();

        $template = [
            'name' => '{{ user.name | not_required | string | upper }}',
        ];

        $sources = ['user' => ['name' => 'Alice']];
        $result = DataMapper::source($sources)->template($template)->map()->getTarget();

        expect($result['name'])->toBe('ALICE');
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    // ── wildcard mapping with required / not_required ──────────────────
    // Note: These tests use MappingFacade::mapFromTemplate() directly because
    // the FluentDataMapper only routes through the template engine for
    // template-based wildcard mappings ('key.*' => [...]) not for simple
    // value wildcards ('*' => 'expression').

    it('validates each wildcard element with required', function(): void {
        MapperExceptions::reset();

        $template = [
            'ids' => [
                '*' => '{{ items.* | required | integer }}',
            ],
        ];

        $sources = ['items' => [10, 20, 30]];
        $result = MappingFacade::mapFromTemplate(
            $template,
            $sources,
            false,
        );

        expect($result['ids'])->toBe([10, 20, 30]);
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('collects exceptions for null elements in wildcard with required', function(): void {
        MapperExceptions::reset();

        $template = [
            'ids' => [
                '*' => '{{ items.* | required | integer }}',
            ],
        ];

        $sources = ['items' => [10, null, 30]];
        $result = MappingFacade::mapFromTemplate(
            $template,
            $sources,
            false,
        );

        expect($result['ids'])->toHaveCount(3);
        expect(MapperExceptions::hasExceptions())->toBeTrue();

        MapperExceptions::clearExceptions();
    });

    it('skips null elements silently in wildcard with not_required', function(): void {
        MapperExceptions::reset();

        $template = [
            'ids' => [
                '*' => '{{ items.* | not_required | integer }}',
            ],
        ];

        $sources = ['items' => [10, null, 30]];
        MappingFacade::mapFromTemplate(
            $template,
            $sources,
            false,
        );

        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('validates wildcard elements with in filter and required', function(): void {
        MapperExceptions::reset();

        $template = [
            'types' => [
                '*' => '{{ items.* | string | upper | in:[VEHICLE,ORDER,PROJECT] | required }}',
            ],
        ];

        $sources = ['items' => ['vehicle', 'order', 'project']];
        $result = MappingFacade::mapFromTemplate(
            $template,
            $sources,
            false,
        );

        expect($result['types'])->toBe(['VEHICLE', 'ORDER', 'PROJECT']);
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('collects exception for invalid wildcard element with in filter', function(): void {
        MapperExceptions::reset();

        $template = [
            'types' => [
                '*' => '{{ items.* | string | upper | in:[VEHICLE,ORDER] }}',
            ],
        ];

        $sources = ['items' => ['vehicle', 'INVALID', 'order']];
        MappingFacade::mapFromTemplate(
            $template,
            $sources,
            false,
        );

        expect(MapperExceptions::hasExceptions())->toBeTrue();
        expect(MapperExceptions::getExceptionCount())->toBe(1);

        MapperExceptions::clearExceptions();
    });

    it('allows mixed null values in wildcard with in filter and not_required', function(): void {
        MapperExceptions::reset();

        $template = [
            'types' => [
                '*' => '{{ items.* | string | upper | in:[VEHICLE,ORDER] | not_required }}',
            ],
        ];

        $sources = ['items' => ['vehicle', '', null, 'order']];
        MappingFacade::mapFromTemplate(
            $template,
            $sources,
            false,
        );

        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    // ── null coalescing with path fallback ─────────────────────────────

    it('resolves right side of ?? as source path', function(): void {
        $template = [
            'item_number' => '{{ item.inventoryNumber ?? item.externalId }}',
        ];

        $sources = ['item' => ['inventoryNumber' => null, 'externalId' => 'EXT-123']];
        $result = MappingFacade::mapFromTemplate($template, $sources, false);

        expect($result['item_number'])->toBe('EXT-123');
    });

    it('returns null when both sides of ?? are null paths', function(): void {
        $template = [
            'item_number' => '{{ item.inventoryNumber ?? item.externalId }}',
        ];

        $sources = ['item' => ['inventoryNumber' => null, 'externalId' => null]];
        $result = MappingFacade::mapFromTemplate($template, $sources, false);

        expect($result['item_number'])->toBeNull();
    });

    it('resolves ?? path fallback in wildcard mapping', function(): void {
        $template = [
            'items' => [
                '*' => '{{ equipment.*.inventoryNumber ?? equipment.*.externalId }}',
            ],
        ];

        $sources = [
            'equipment' => [
                ['inventoryNumber' => 'INV-001', 'externalId' => 'EXT-001'],
                ['inventoryNumber' => null, 'externalId' => 'EXT-002'],
                ['inventoryNumber' => null, 'externalId' => null],
            ],
        ];
        $result = MappingFacade::mapFromTemplate($template, $sources, false);

        expect($result['items'][0])->toBe('INV-001');
        expect($result['items'][1])->toBe('EXT-002');
        expect($result['items'][2])->toBeNull();
    });

    it('still uses literal fallback with ?? when quoted', function(): void {
        $template = [
            'name' => '{{ item.name ?? "UNKNOWN" }}',
        ];

        $sources = ['item' => ['name' => null]];
        $result = MappingFacade::mapFromTemplate($template, $sources, false);

        expect($result['name'])->toBe('UNKNOWN');
    });
});

describe('Multi-Expression String Interpolation', function(): void {
    it('evaluates two expressions with filters in one value', function(): void {
        $source = ['person' => ['firstName' => ' john ', 'lastName' => ' doe ']];
        $template = [
            'name' => '{{ person.firstName | trim | ucfirst }} {{ person.lastName | trim | ucfirst }}',
        ];

        $result = DataMapper::source($source)->template($template)->map()->getTarget();

        expect($result['name'])->toBe('John Doe');
    });

    it('evaluates expressions mixed with literal text', function(): void {
        $source = ['user' => ['name' => 'Alice']];
        $template = [
            'greeting' => 'Hello {{ user.name }}, welcome!',
        ];

        $result = DataMapper::source($source)->template($template)->map()->getTarget();

        expect($result['greeting'])->toBe('Hello Alice, welcome!');
    });

    it('evaluates multiple expressions with separator', function(): void {
        $source = ['person' => ['firstName' => ' john ', 'lastName' => ' doe ']];
        $template = [
            'info' => '{{ person.firstName | trim }} - {{ person.lastName | trim }}',
        ];

        $result = DataMapper::source($source)->template($template)->map()->getTarget();

        expect($result['info'])->toBe('john - doe');
    });

    it('evaluates three expressions in one value', function(): void {
        $source = [
            'user' => ['first' => 'john', 'middle' => 'james', 'last' => 'doe'],
        ];
        $template = [
            'full' => '{{ user.first | ucfirst }} {{ user.middle | ucfirst }} {{ user.last | ucfirst }}',
        ];

        $result = DataMapper::source($source)->template($template)->map()->getTarget();

        expect($result['full'])->toBe('John James Doe');
    });

    it('handles null values in multi-expression gracefully', function(): void {
        $source = ['person' => ['firstName' => 'Alice', 'lastName' => null]];
        $template = [
            'name' => '{{ person.firstName }} {{ person.lastName }}',
        ];

        $result = DataMapper::source($source)->template($template)->map()->getTarget();

        expect($result['name'])->toBe('Alice ');
    });

    it('does not break single expressions with filters', function(): void {
        $source = ['user' => ['email' => '  ALICE@EXAMPLE.COM  ']];
        $template = [
            'email' => '{{ user.email | trim | lower }}',
        ];

        $result = DataMapper::source($source)->template($template)->map()->getTarget();

        expect($result['email'])->toBe('alice@example.com');
    });

    it('evaluates multi-expression with default values', function(): void {
        $source = ['user' => ['firstName' => 'Alice']];
        $template = [
            'name' => '{{ user.firstName }} {{ user.lastName ?? "Unknown" }}',
        ];

        $result = DataMapper::source($source)->template($template)->map()->getTarget();

        expect($result['name'])->toBe('Alice Unknown');
    });

    it('works in nested template structure', function(): void {
        $source = ['person' => ['firstName' => ' john ', 'lastName' => ' doe ']];
        $template = [
            'profile' => [
                'displayName' => '{{ person.firstName | trim | ucfirst }} {{ person.lastName | trim | ucfirst }}',
            ],
        ];

        $result = DataMapper::source($source)->template($template)->map()->getTarget();

        expect($result['profile']['displayName'])->toBe('John Doe');
    });
});

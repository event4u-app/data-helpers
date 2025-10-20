<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\Support\Lazy;
use event4u\DataHelpers\Support\Optional;

// Test DTOs
class TestComboDTO1 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly Optional|Lazy|string $content,
    ) {}
}

class TestComboDTO2 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly Optional|Lazy|string|null $metadata,
    ) {}
}

class TestComboDTO3 extends SimpleDTO
{
    public function __construct(
        public readonly Optional|string $name,
        public readonly Optional|string $email,
        public readonly Optional|Lazy|string $bio,
    ) {}
}

describe('Optional + Lazy Combinations', function(): void {
    it('handles missing optional lazy property', function(): void {
        $dto = TestComboDTO1::fromArray(['title' => 'Test']);

        expect($dto->title)->toBe('Test')
            ->and($dto->content)->toBeInstanceOf(Optional::class)
            ->and($dto->content->isEmpty())->toBeTrue();
    });

    it('handles present optional lazy property', function(): void {
        $dto = TestComboDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);

        expect($dto->title)->toBe('Test')
            ->and($dto->content)->toBeInstanceOf(Optional::class)
            ->and($dto->content->isPresent())->toBeTrue();

        $value = $dto->content->get();
        expect($value)->toBeInstanceOf(Lazy::class)
            ->and($value->get())->toBe('Content...');
    });

    it('excludes optional lazy from toArray when missing', function(): void {
        $dto = TestComboDTO1::fromArray(['title' => 'Test']);
        $array = $dto->toArray();

        // Optional properties that are missing are excluded (not included as null)
        expect($array)->toBe(['title' => 'Test']);
    });

    it('excludes optional lazy from toArray when present but lazy', function(): void {
        $dto = TestComboDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $array = $dto->toArray();

        expect($array)->toBe(['title' => 'Test']);
    });

    it('includes optional lazy in toArray when explicitly requested', function(): void {
        $dto = TestComboDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $array = $dto->include(['content'])->toArray();

        expect($array)->toBe([
            'title' => 'Test',
            'content' => 'Content...',
        ]);
    });

    it('handles nullable optional lazy property', function(): void {
        $dto = TestComboDTO2::fromArray(['title' => 'Test', 'metadata' => null]);

        expect($dto->metadata)->toBeInstanceOf(Optional::class)
            ->and($dto->metadata->isPresent())->toBeTrue();

        $value = $dto->metadata->get();
        expect($value)->toBeInstanceOf(Lazy::class)
            ->and($value->get())->toBeNull();
    });

    it('supports partial updates with optional lazy', function(): void {
        $dto = TestComboDTO3::fromArray(['email' => 'test@example.com', 'bio' => 'Bio...']);
        $partial = $dto->partial();

        expect($partial)->toBe([
            'email' => 'test@example.com',
            'bio' => 'Bio...',
        ])
            ->and(array_key_exists('name', $partial))->toBeFalse();
    });

    it('unwraps optional lazy in partial updates', function(): void {
        $dto = TestComboDTO3::fromArray(['name' => 'John', 'bio' => 'Bio...']);
        $partial = $dto->partial();

        expect($partial['name'])->toBe('John')
            ->and($partial['bio'])->toBe('Bio...')
            ->and($partial['bio'])->not->toBeInstanceOf(Lazy::class)
            ->and($partial['bio'])->not->toBeInstanceOf(Optional::class);
    });
});

describe('Multiple Optional Properties', function(): void {
    it('handles multiple missing optional properties', function(): void {
        $dto = TestComboDTO3::fromArray([]);

        expect($dto->name->isEmpty())->toBeTrue()
            ->and($dto->email->isEmpty())->toBeTrue()
            ->and($dto->bio->isEmpty())->toBeTrue();
    });

    it('handles mixed present and missing optional properties', function(): void {
        $dto = TestComboDTO3::fromArray(['name' => 'John', 'bio' => 'Bio...']);

        expect($dto->name->isPresent())->toBeTrue()
            ->and($dto->name->get())->toBe('John')
            ->and($dto->email->isEmpty())->toBeTrue()
            ->and($dto->bio->isPresent())->toBeTrue();
    });

    it('partial returns only present optional properties', function(): void {
        $dto = TestComboDTO3::fromArray(['name' => 'John']);
        $partial = $dto->partial();

        expect($partial)->toBe(['name' => 'John'])
            ->and(array_key_exists('email', $partial))->toBeFalse()
            ->and(array_key_exists('bio', $partial))->toBeFalse();
    });
});

describe('JSON Serialization with Combinations', function(): void {
    it('serializes optional lazy correctly when missing', function(): void {
        $dto = TestComboDTO1::fromArray(['title' => 'Test']);
        $json = json_encode($dto);

        // Optional properties that are missing are excluded from JSON
        expect($json)->toBe('{"title":"Test"}');
    });

    it('serializes optional lazy correctly when present but lazy excluded', function(): void {
        $dto = TestComboDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $json = json_encode($dto);

        expect($json)->toBe('{"title":"Test"}');
    });

    it('serializes optional lazy correctly when present and lazy included', function(): void {
        $dto = TestComboDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $json = json_encode($dto->includeAll());

        expect($json)->toBe('{"title":"Test","content":"Content..."}');
    });

    it('serializes multiple optional properties correctly', function(): void {
        $dto = TestComboDTO3::fromArray(['name' => 'John', 'bio' => 'Bio...']);
        $json = json_encode($dto);

        expect($json)->toBe('{"name":"John","email":null}');
    });

    it('serializes multiple optional properties with includeAll', function(): void {
        $dto = TestComboDTO3::fromArray(['name' => 'John', 'bio' => 'Bio...']);
        $json = json_encode($dto->includeAll());

        expect($json)->toBe('{"name":"John","email":null,"bio":"Bio..."}');
    });
});

describe('Edge Cases', function(): void {
    it('handles empty array input', function(): void {
        $dto = TestComboDTO3::fromArray([]);

        expect($dto->name->isEmpty())->toBeTrue()
            ->and($dto->email->isEmpty())->toBeTrue()
            ->and($dto->bio->isEmpty())->toBeTrue();

        $partial = $dto->partial();
        expect($partial)->toBe([]);
    });

    it('handles all properties present', function(): void {
        $dto = TestComboDTO3::fromArray([
            'name' => 'John',
            'email' => 'john@example.com',
            'bio' => 'Bio...',
        ]);

        expect($dto->name->isPresent())->toBeTrue()
            ->and($dto->email->isPresent())->toBeTrue()
            ->and($dto->bio->isPresent())->toBeTrue();

        $partial = $dto->partial();
        expect($partial)->toBe([
            'name' => 'John',
            'email' => 'john@example.com',
            'bio' => 'Bio...',
        ]);
    });

    it('handles null values correctly', function(): void {
        $dto = TestComboDTO2::fromArray(['title' => 'Test', 'metadata' => null]);

        expect($dto->metadata->isPresent())->toBeTrue();

        $value = $dto->metadata->get();
        expect($value)->toBeInstanceOf(Lazy::class)
            ->and($value->get())->toBeNull();
    });
});


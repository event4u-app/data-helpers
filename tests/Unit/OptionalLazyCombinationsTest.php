<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\Support\Lazy;
use event4u\DataHelpers\Support\Optional;

// Test DTOs
class TestComboDTO1 extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $title,
        public readonly Optional|Lazy|string $content,
    ) {}
}

class TestComboDTO2 extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $title,
        public readonly Optional|Lazy|string|null $metadata,
    ) {}
}

class TestComboDTO3 extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly Optional|string $name,
        public readonly Optional|string $email,
        public readonly Optional|Lazy|string $bio,
    ) {}
}

describe('Optional + Lazy Combinations', function(): void {
    it('handles missing optional lazy property', function(): void {
        $dto = TestComboDTO1::fromArray(['title' => 'Test']);

        expect($dto->title)->toBe('Test');
        expect($dto->content)->toBeInstanceOf(Optional::class);
        /** @phpstan-ignore-next-line unknown */
        expect($dto->content->isEmpty())->toBeTrue();
    });

    it('handles present optional lazy property', function(): void {
        $dto = TestComboDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);

        expect($dto->title)->toBe('Test');
        expect($dto->content)->toBeInstanceOf(Optional::class);
        /** @phpstan-ignore-next-line unknown */
        expect($dto->content->isPresent())->toBeTrue();

        /** @phpstan-ignore-next-line unknown */
        $value = $dto->content->get();
        expect($value)->toBeInstanceOf(Lazy::class);
        /** @phpstan-ignore-next-line unknown */
        expect($value->get())->toBe('Content...');
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

        expect($dto->metadata)->toBeInstanceOf(Optional::class);
        /** @phpstan-ignore-next-line unknown */
        expect($dto->metadata->isPresent())->toBeTrue();

        /** @phpstan-ignore-next-line unknown */
        $value = $dto->metadata->get();
        expect($value)->toBeInstanceOf(Lazy::class);
        /** @phpstan-ignore-next-line unknown */
        expect($value->get())->toBeNull();
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

        expect($partial['name'])->toBe('John');
        expect($partial['bio'])->toBe('Bio...');
        expect($partial['bio'])->not->toBeInstanceOf(Lazy::class);
        expect($partial['bio'])->not->toBeInstanceOf(Optional::class);
    });
});

describe('Multiple Optional Properties', function(): void {
    it('handles multiple missing optional properties', function(): void {
        $dto = TestComboDTO3::fromArray([]);

        /** @phpstan-ignore-next-line unknown */
        expect($dto->name->isEmpty())->toBeTrue();
        /** @phpstan-ignore-next-line unknown */
        expect($dto->email->isEmpty())->toBeTrue();
        /** @phpstan-ignore-next-line unknown */
        expect($dto->bio->isEmpty())->toBeTrue();
    });

    it('handles mixed present and missing optional properties', function(): void {
        $dto = TestComboDTO3::fromArray(['name' => 'John', 'bio' => 'Bio...']);

        /** @phpstan-ignore-next-line unknown */
        expect($dto->name->isPresent())->toBeTrue();
        /** @phpstan-ignore-next-line unknown */
        expect($dto->name->get())->toBe('John');
        /** @phpstan-ignore-next-line unknown */
        expect($dto->email->isEmpty())->toBeTrue();
        /** @phpstan-ignore-next-line unknown */
        expect($dto->bio->isPresent())->toBeTrue();
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

        /** @phpstan-ignore-next-line unknown */
        expect($dto->name->isEmpty())->toBeTrue();
        /** @phpstan-ignore-next-line unknown */
        expect($dto->email->isEmpty())->toBeTrue();
        /** @phpstan-ignore-next-line unknown */
        expect($dto->bio->isEmpty())->toBeTrue();

        $partial = $dto->partial();
        expect($partial)->toBe([]);
    });

    it('handles all properties present', function(): void {
        $dto = TestComboDTO3::fromArray([
            'name' => 'John',
            'email' => 'john@example.com',
            'bio' => 'Bio...',
        ]);

        /** @phpstan-ignore-next-line unknown */
        expect($dto->name->isPresent())->toBeTrue();
        /** @phpstan-ignore-next-line unknown */
        expect($dto->email->isPresent())->toBeTrue();
        /** @phpstan-ignore-next-line unknown */
        expect($dto->bio->isPresent())->toBeTrue();

        $partial = $dto->partial();
        expect($partial)->toBe([
            'name' => 'John',
            'email' => 'john@example.com',
            'bio' => 'Bio...',
        ]);
    });

    it('handles null values correctly', function(): void {
        $dto = TestComboDTO2::fromArray(['title' => 'Test', 'metadata' => null]);

        /** @phpstan-ignore-next-line unknown */
        expect($dto->metadata->isPresent())->toBeTrue();

        /** @phpstan-ignore-next-line unknown */
        $value = $dto->metadata->get();
        expect($value)->toBeInstanceOf(Lazy::class);
        /** @phpstan-ignore-next-line unknown */
        expect($value->get())->toBeNull();
    });
});


<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy as LazyAttribute;
use event4u\DataHelpers\Support\Lazy;

// Test DTOs
class TestLazyDTO1 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly Lazy|string $content,
    ) {}
}

class TestLazyDTO2 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        #[LazyAttribute]
        public readonly Lazy|string $content,
    ) {}
}

class TestLazyDTO3 extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly Lazy|string|null $content,
    ) {}
}

describe('Lazy Union Types', function(): void {
    it('wraps lazy properties with union type syntax', function(): void {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        
        expect($dto->title)->toBe('Test')
            ->and($dto->content)->toBeInstanceOf(Lazy::class)
            ->and($dto->content->isLoaded())->toBeTrue()
            ->and($dto->content->get())->toBe('Content...');
    });

    it('wraps lazy properties with attribute syntax', function(): void {
        $dto = TestLazyDTO2::fromArray(['title' => 'Test', 'content' => 'Content...']);
        
        expect($dto->title)->toBe('Test')
            ->and($dto->content)->toBeInstanceOf(Lazy::class)
            ->and($dto->content->isLoaded())->toBeTrue()
            ->and($dto->content->get())->toBe('Content...');
    });

    it('excludes lazy properties from toArray by default', function(): void {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $array = $dto->toArray();
        
        expect($array)->toBe(['title' => 'Test'])
            ->and(array_key_exists('content', $array))->toBeFalse();
    });

    it('includes lazy properties when explicitly requested', function(): void {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $array = $dto->include(['content'])->toArray();
        
        expect($array)->toBe([
            'title' => 'Test',
            'content' => 'Content...',
        ]);
    });

    it('includes all lazy properties with includeAll', function(): void {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $array = $dto->includeAll()->toArray();
        
        expect($array)->toBe([
            'title' => 'Test',
            'content' => 'Content...',
        ]);
    });

    it('handles nullable lazy properties', function(): void {
        $dto = TestLazyDTO3::fromArray(['title' => 'Test', 'content' => null]);
        
        expect($dto->content)->toBeInstanceOf(Lazy::class)
            ->and($dto->content->get())->toBeNull();
    });

    it('excludes lazy properties from JSON by default', function(): void {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $json = json_encode($dto);
        
        expect($json)->toBe('{"title":"Test"}');
    });

    it('includes lazy properties in JSON when requested', function(): void {
        $dto = TestLazyDTO1::fromArray(['title' => 'Test', 'content' => 'Content...']);
        $json = json_encode($dto->includeAll());
        
        expect($json)->toBe('{"title":"Test","content":"Content..."}');
    });
});

describe('Lazy Wrapper', function(): void {
    it('creates lazy with value', function(): void {
        $lazy = Lazy::value('test');
        
        expect($lazy->isLoaded())->toBeTrue()
            ->and($lazy->get())->toBe('test');
    });

    it('creates lazy with loader', function(): void {
        $lazy = Lazy::of(fn(): string => 'computed');
        
        expect($lazy->isLoaded())->toBeFalse();
        
        $value = $lazy->get();
        
        expect($value)->toBe('computed')
            ->and($lazy->isLoaded())->toBeTrue();
    });

    it('caches loaded value', function(): void {
        $counter = 0;
        $lazy = Lazy::of(function() use (&$counter): string {
            $counter++;
            return 'value';
        });
        
        $lazy->get();
        $lazy->get();
        $lazy->get();
        
        expect($counter)->toBe(1);
    });

    it('maps lazy value', function(): void {
        $lazy = Lazy::value('hello');
        $mapped = $lazy->map(fn($v) => strtoupper($v));
        
        expect($mapped->get())->toBe('HELLO');
    });

    it('executes callback if loaded', function(): void {
        $lazy = Lazy::value('test');
        $executed = false;
        
        $lazy->ifLoaded(function($value) use (&$executed): void {
            $executed = true;
        });
        
        expect($executed)->toBeTrue();
    });

    it('does not execute callback if not loaded', function(): void {
        $lazy = Lazy::of(fn(): string => 'test');
        $executed = false;
        
        $lazy->ifLoaded(function($value) use (&$executed): void {
            $executed = true;
        });
        
        expect($executed)->toBeFalse();
    });

    it('executes callback if not loaded', function(): void {
        $lazy = Lazy::of(fn(): string => 'test');
        $executed = false;
        
        $lazy->ifNotLoaded(function() use (&$executed): void {
            $executed = true;
        });
        
        expect($executed)->toBeTrue();
    });

    it('loads value explicitly', function(): void {
        $lazy = Lazy::of(fn(): string => 'test');
        
        expect($lazy->isLoaded())->toBeFalse();
        
        $lazy->load();
        
        expect($lazy->isLoaded())->toBeTrue()
            ->and($lazy->get())->toBe('test');
    });
});

